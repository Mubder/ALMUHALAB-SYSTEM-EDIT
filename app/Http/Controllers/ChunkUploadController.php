<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChunkUploadController extends Controller
{
    public function upload(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

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

            $identifier = preg_replace('/[^a-zA-Z0-9_\-]/', '', $identifier);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $safeBasename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $basename);
            $safeFilename = $safeBasename . '.' . $extension;

            $tempPath = "chunks/{$identifier}";
            
            // Store the chunk file
            $file->storeAs($tempPath, "chunk_{$chunkNumber}", 'local');

            // Check if all chunks from 1 to totalChunks exist on local disk
            $allExist = true;
            for ($i = 1; $i <= $totalChunks; $i++) {
                if (!Storage::disk('local')->exists("{$tempPath}/chunk_{$i}")) {
                    $allExist = false;
                    break;
                }
            }

            if ($allExist) {
                @set_time_limit(300);

                $finalFilename = "merged_" . time() . "_" . $safeFilename;
                $finalPath = "stage-attachments/{$finalFilename}";
                $fullPublicPath = storage_path("app/public/{$finalPath}");

                $dir = dirname($fullPublicPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $outStream = fopen($fullPublicPath, "wb");
                if ($outStream) {
                    for ($i = 1; $i <= $totalChunks; $i++) {
                        $chunkRelPath = "{$tempPath}/chunk_{$i}";
                        $chunkFullPath = Storage::disk('local')->path($chunkRelPath);
                        
                        if (file_exists($chunkFullPath)) {
                            $inStream = fopen($chunkFullPath, "rb");
                            if ($inStream) {
                                stream_copy_to_stream($inStream, $outStream);
                                fclose($inStream);
                            }
                        }
                    }
                    fclose($outStream);

                    // Clean up local temp chunk files
                    Storage::disk('local')->deleteDirectory($tempPath);
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("ChunkUploadController failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
