<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\StageAttachment;
use App\Models\User;
use App\Notifications\AssignedToRequestNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function advance(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        WorkflowService::advance($serviceRequest, auth()->user(), $request->input('notes'));

        $stage = WorkflowService::stage($serviceRequest->fresh()->current_stage);

        return back()->with('success', "Request advanced to: {$stage['label']}");
    }

    public function returnStage(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        WorkflowService::returnToPreviousStage($serviceRequest, auth()->user(), $request->input('notes'));

        $stage = WorkflowService::stage($serviceRequest->fresh()->current_stage);

        return back()->with('success', "Request returned to: {$stage['label']}");
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $stageCfg = WorkflowService::stage($serviceRequest->current_stage);

        $request->validate([
            'stage_status' => 'required|string|in:' . implode(',', $stageCfg['statuses']),
            'notes'        => 'nullable|string|max:500',
        ]);

        WorkflowService::updateStatus(
            $serviceRequest,
            auth()->user(),
            $request->input('stage_status'),
            $request->input('notes')
        );

        return back()->with('success', "Status updated to: {$request->input('stage_status')}");
    }

    public function forceTransition(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'to_stage' => 'required|integer|min:1|max:' . WorkflowService::stageCount(),
            'notes'    => 'nullable|string|max:500',
        ]);

        WorkflowService::forceTransition(
            $serviceRequest,
            auth()->user(),
            (int) $request->input('to_stage'),
            $request->input('notes')
        );

        $stage = WorkflowService::stage((int) $request->input('to_stage'));

        return back()->with('success', "Force moved to: {$stage['label']}");
    }

    public function confirmPayment(Request $request, ServiceRequest $serviceRequest)
    {
        $user = auth()->user();

        // Only the request owner at stage 5 (Client Approval) can confirm payment
        if ($serviceRequest->user_id !== $user->id || $serviceRequest->current_stage !== 5) {
            abort(403, 'You cannot perform this action.');
        }
        if ($serviceRequest->is_rejected) {
            abort(403, 'This request has been rejected.');
        }

        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ], [
            'receipt.required' => 'Please upload your payment receipt.',
            'receipt.mimes'    => 'Receipt must be a JPG, PNG, or PDF file.',
            'receipt.max'      => 'Receipt file must not exceed 20 MB.',
        ]);

        $file = $request->file('receipt');
        $path = $file->store("stage-attachments/{$serviceRequest->id}", 'public');

        StageAttachment::create([
            'service_request_id' => $serviceRequest->id,
            'stage'              => 5,
            'uploaded_by'        => $user->id,
            'file_path'          => $path,
            'original_name'      => $file->getClientOriginalName(),
            'mime_type'          => $file->getMimeType(),
            'size'               => $file->getSize(),
            'visibility'         => 'admin',
        ]);

        WorkflowService::updateStatus($serviceRequest, $user, 'Awaiting Payment');

        return back()->with('success', 'Payment receipt submitted. Awaiting review by our team.');
    }

    public function approvePayment(Request $request, ServiceRequest $serviceRequest)
    {
        $user = auth()->user();

        if (! $user->hasPermission('transition_stage') && ! $user->hasPermission('update_status') && ! $user->hasPermission('force_transition')) {
            abort(403);
        }

        if ($serviceRequest->current_stage !== 5 || $serviceRequest->stage_status !== 'Awaiting Payment') {
            return back()->with('error', 'Payment cannot be approved at this stage.');
        }

        WorkflowService::updateStatus($serviceRequest, $user, 'Paid', $request->input('notes'));

        return back()->with('success', 'Payment approved. Status set to Paid.');
    }

    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $previousAssignee = $serviceRequest->assigned_to;
        $newAssigneeId    = $request->input('assigned_to') ?: null;

        $serviceRequest->update(['assigned_to' => $newAssigneeId]);

        // Notify the newly assigned employee (only if it changed and is not self-assignment)
        if ($newAssigneeId && $newAssigneeId !== $previousAssignee) {
            $assignee = User::find($newAssigneeId);
            if ($assignee && $assignee->id !== auth()->id()) {
                $assignee->notify(new AssignedToRequestNotification($serviceRequest, auth()->user()));
            }
        }

        return back()->with('success', 'Assignment updated.');
    }
}
