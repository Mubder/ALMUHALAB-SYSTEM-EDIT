<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
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

    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $serviceRequest->update(['assigned_to' => $request->input('assigned_to') ?: null]);

        return back()->with('success', 'Assignment updated.');
    }
}
