<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\StageComment;
use App\Models\User;
use App\Notifications\CommentAddedNotification;
use Illuminate\Http\Request;

class StageCommentController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'content'    => 'required|string|max:2000',
            'visibility' => 'required|in:all,employee,admin',
            'parent_id'  => 'nullable|exists:stage_comments,id',
        ]);

        $user = auth()->user();
        $isOverseasAgent = $user->role && str_contains(strtolower($user->role->name), 'overseas');

        if ($isOverseasAgent) {
            $data['visibility'] = 'admin';
        } elseif (! $user->hasPermission('transition_stage') && ! $user->hasPermission('manage_users')) {
            $data['visibility'] = 'all';
        }

        $comment = StageComment::create([
            'service_request_id' => $serviceRequest->id,
            'stage_number'       => $serviceRequest->current_stage,
            'parent_id'          => $data['parent_id'] ?? null,
            'content'            => $data['content'],
            'visibility'         => $data['visibility'],
            'created_by'         => $user->id,
        ]);

        // Notify relevant parties
        $recipients = collect();
        if ($serviceRequest->user && $serviceRequest->user->id !== $user->id) {
            $recipients->push($serviceRequest->user);
        }
        if ($serviceRequest->assignedTo?->id && $serviceRequest->assignedTo->id !== $user->id) {
            $recipients->push($serviceRequest->assignedTo);
        }

        foreach ($recipients->unique('id') as $recipient) {
            // Don't notify client about internal-only comments
            if ($data['visibility'] !== 'all' && $recipient->id === $serviceRequest->user_id) {
                continue;
            }
            try {
                $recipient->notify(new CommentAddedNotification($serviceRequest, $comment, $user));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending comment notification email to user {$recipient->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Comment added.');
    }

    public function destroy(ServiceRequest $serviceRequest, StageComment $comment)
    {
        abort_unless(
            $comment->service_request_id === $serviceRequest->id &&
            ($comment->created_by === auth()->id() || auth()->user()->hasPermission('manage_users')),
            403
        );

        $comment->delete();

        return back()->with('success', 'Comment removed.');
    }
}
