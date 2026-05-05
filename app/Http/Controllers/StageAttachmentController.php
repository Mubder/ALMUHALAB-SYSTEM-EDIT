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
            'files'        => 'required|array|min:1',
            'files.*'      => 'required|file|max:20480',
            'stage'        => 'required|integer|between:1,7',
            'visibility'   => 'required|in:admin,employee,client',
        ]);

        foreach ($request->file('files') as $file) {
            $path = $file->store("stage-attachments/{$serviceRequest->id}", 'public');

            $attachment = StageAttachment::create([
                'service_request_id' => $serviceRequest->id,
                'stage'              => $request->stage,
                'uploaded_by'        => auth()->id(),
                'file_path'          => $path,
                'original_name'      => $file->getClientOriginalName(),
                'mime_type'          => $file->getMimeType(),
                'size'               => $file->getSize(),
                'visibility'         => $request->visibility,
            ]);

            ActivityLog::create([
                'user'         => auth()->id(),
                'action'       => 'attachment_uploaded',
                'subject_type' => ServiceRequest::class,
                'subject_id'   => $serviceRequest->id,
                'changes'      => [
                    'file'       => $file->getClientOriginalName(),
                    'stage'      => $request->stage,
                    'visibility' => $request->visibility,
                ],
            ]);
        }

        return back()->with('success', count($request->file('files')) . ' file(s) uploaded.');
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
