<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use App\Models\StageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StageAttachmentController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless(auth()->user()->hasPermission('manage_attachments'), 403);

        $request->validate([
            'files'        => 'nullable|array',
            'files.*'      => 'nullable|file|max:1048576',
            'merged_files' => 'nullable|array',
            'stage'        => 'required|integer|between:1,7',
            'visibility'   => 'nullable|in:admin,employee,client',
        ]);

        $visibility = $request->input('visibility', 'employee');
        $uploadedCount = 0;

        // 1. Process regular files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!$file || !$file->isValid()) continue;

                $path = $file->store("stage-attachments/{$serviceRequest->id}", 'public');

                StageAttachment::create([
                    'service_request_id' => $serviceRequest->id,
                    'stage'              => $request->stage,
                    'uploaded_by'        => auth()->id(),
                    'file_path'          => $path,
                    'original_name'      => $file->getClientOriginalName(),
                    'mime_type'          => $file->getMimeType(),
                    'size'               => $file->getSize(),
                    'visibility'         => $visibility,
                ]);

                ActivityLog::create([
                    'user'         => auth()->id(),
                    'action'       => 'attachment_uploaded',
                    'subject_type' => ServiceRequest::class,
                    'subject_id'   => $serviceRequest->id,
                    'changes'      => [
                        'file'       => $file->getClientOriginalName(),
                        'stage'      => $request->stage,
                        'visibility' => $visibility,
                    ],
                ]);
                $uploadedCount++;
            }
        }

        // 2. Process pre-merged chunked files
        if ($request->has('merged_files')) {
            foreach ($request->input('merged_files') as $fileData) {
                if (is_string($fileData)) {
                    $fileData = json_decode($fileData, true);
                }
                
                $path = $fileData['path'] ?? '';
                $filename = $fileData['filename'] ?? '';
                
                if (empty($path)) continue;

                // Move file to the service request directory using public disk
                $targetPath = "stage-attachments/{$serviceRequest->id}/" . basename($path);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->move($path, $targetPath);
                    $path = $targetPath;
                }

                $fullPath = storage_path("app/public/{$path}");
                $size = 0;
                $mimeType = 'application/octet-stream';
                if (file_exists($fullPath) && !is_dir($fullPath)) {
                    $size = @filesize($fullPath) ?: 0;
                    $mimeType = @mime_content_type($fullPath) ?: 'application/octet-stream';
                }

                StageAttachment::create([
                    'service_request_id' => $serviceRequest->id,
                    'stage'              => $request->stage,
                    'uploaded_by'        => auth()->id(),
                    'file_path'          => $path,
                    'original_name'      => $filename,
                    'mime_type'          => $mimeType,
                    'size'               => $size,
                    'visibility'         => $visibility,
                ]);

                ActivityLog::create([
                    'user'         => auth()->id(),
                    'action'       => 'attachment_uploaded',
                    'subject_type' => ServiceRequest::class,
                    'subject_id'   => $serviceRequest->id,
                    'changes'      => [
                        'file'       => $filename,
                        'stage'      => $request->stage,
                        'visibility' => $visibility,
                    ],
                ]);
                $uploadedCount++;
            }
        }

        return back()->with('success', $uploadedCount . ' file(s) uploaded successfully.');
    }

    public function destroy(ServiceRequest $serviceRequest, StageAttachment $attachment)
    {
        abort_unless(auth()->user()->hasPermission('manage_attachments'), 403);
        abort_unless($attachment->service_request_id === $serviceRequest->id, 404);

        Storage::disk('public')->delete($attachment->file_path);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'attachment_deleted',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $serviceRequest->id,
            'changes'      => ['file' => $attachment->original_name, 'stage' => $attachment->stage],
        ]);

        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }
}
