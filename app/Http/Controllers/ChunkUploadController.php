<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChunkUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Require auth check to prevent unauthenticated chunk uploads
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Close session lock immediately so parallel/rapid requests don't lock each other out
        session_write_close();

        $request->validate([
            'file' => 'required|file',
            'resumableIdentifier' => 'required|string',
            'resumableChunkNumber' => 'required|integer',
            'resumableTotalChunks' => 'required|integer',
            'resumableFilename' => 'required|string',
        ]);

        $file = $request->file('file');
        $identifier = $request->input('resumableIdentifier');
        $chunkNumber = (int) $request->input('resumableChunkNumber');
        $totalChunks = (int) $request->input('resumableTotalChunks');
        $filename = $request->input('resumableFilename');

        // Sanitize inputs
        $identifier = preg_replace('/[^a-zA-Z0-9_\-]/', '', $identifier);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $safeBasename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $basename);
        $safeFilename = $safeBasename . '.' . $extension;

        $tempPath = "chunks/{$identifier}";
        
        // Store the chunk file
        $file->storeAs($tempPath, "chunk_{$chunkNumber}", 'local');

        // Fast chunk count check using single glob scan
        $chunkDir = storage_path("app/{$tempPath}");
        $chunksUploaded = is_dir($chunkDir) ? count(glob("{$chunkDir}/chunk_*")) : 0;

        if ($chunksUploaded === $totalChunks) {
            // All chunks are present, let's merge them!
            $finalFilename = "merged_" . time() . "_" . $safeFilename;
            $finalPath = "stage-attachments/{$finalFilename}";
            $finalFullPath = storage_path("app/public/{$finalPath}");

            // Ensure destination directory exists
            $dir = dirname($finalFullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $out = fopen($finalFullPath, "wb");
            if ($out) {
                for ($i = 1; $i <= $totalChunks; $i++) {
                    $chunkFile = storage_path("app/{$tempPath}/chunk_{$i}");
                    $in = fopen($chunkFile, "rb");
                    if ($in) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                        fclose($in);
                    }
                    if (file_exists($chunkFile)) {
                        unlink($chunkFile); // Clean up chunk
                    }
                }
                fclose($out);
                
                // Clean up directory
                $chunkDir = storage_path("app/{$tempPath}");
                if (is_dir($chunkDir)) {
                    rmdir($chunkDir);
                }
            }

            return response()->json([
                'completed' => true,
                'path' => $finalPath,
                'filename' => $filename,
            ]);
        }

        return response()->json([
            'completed' => false,
            'chunk' => $chunkNumber,
        ]);
    }
}
