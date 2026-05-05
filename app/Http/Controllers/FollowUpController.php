<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FollowUp;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:2000',
            'status_type'         => 'required|string|in:' . implode(',', array_keys(FollowUp::statusTypes())),
            'scheduled_at'        => 'nullable|date',
            'is_visible_to_client'=> 'boolean',
            'extra_data'          => 'nullable|array',
        ]);

        $data['service_request_id']   = $serviceRequest->id;
        $data['created_by']           = auth()->id();
        $data['is_visible_to_client'] = $request->boolean('is_visible_to_client', true);
        $data['is_completed']         = false;

        $followUp = FollowUp::create($data);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'follow_up_created',
            'subject_type' => FollowUp::class,
            'subject_id'   => $followUp->id,
            'changes'      => ['service_request_id' => $serviceRequest->id] + $followUp->toArray(),
        ]);

        return back()->with('success', 'Follow-up added to the timeline.');
    }

    public function edit(ServiceRequest $serviceRequest, FollowUp $followUp)
    {
        abort_unless($followUp->service_request_id === $serviceRequest->id, 404);

        return view('follow_ups.edit', compact('serviceRequest', 'followUp'));
    }

    public function update(Request $request, ServiceRequest $serviceRequest, FollowUp $followUp)
    {
        abort_unless($followUp->service_request_id === $serviceRequest->id, 404);

        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:2000',
            'status_type'         => 'required|string|in:' . implode(',', array_keys(FollowUp::statusTypes())),
            'scheduled_at'        => 'nullable|date',
            'is_visible_to_client'=> 'boolean',
        ]);

        $data['is_visible_to_client'] = $request->boolean('is_visible_to_client', true);

        $before = $followUp->toArray();
        $followUp->update($data);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'follow_up_updated',
            'subject_type' => FollowUp::class,
            'subject_id'   => $followUp->id,
            'changes'      => [
                'service_request_id' => $serviceRequest->id,
                'before' => $before,
                'after'  => $followUp->fresh()->toArray(),
            ],
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)
            ->with('success', 'Follow-up updated.');
    }

    public function destroy(ServiceRequest $serviceRequest, FollowUp $followUp)
    {
        abort_unless($followUp->service_request_id === $serviceRequest->id, 404);

        $data = $followUp->toArray();
        $followUp->delete();

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'follow_up_deleted',
            'subject_type' => FollowUp::class,
            'subject_id'   => $followUp->id,
            'changes'      => ['service_request_id' => $serviceRequest->id] + $data,
        ]);

        return back()->with('success', 'Follow-up removed from timeline.');
    }

    public function toggle(ServiceRequest $serviceRequest, FollowUp $followUp)
    {
        abort_unless($followUp->service_request_id === $serviceRequest->id, 404);

        $nowCompleted = ! $followUp->is_completed;

        $followUp->update([
            'is_completed' => $nowCompleted,
            'completed_at' => $nowCompleted ? now() : null,
        ]);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => $nowCompleted ? 'follow_up_completed' : 'follow_up_reopened',
            'subject_type' => FollowUp::class,
            'subject_id'   => $followUp->id,
            'changes'      => [
                'service_request_id' => $serviceRequest->id,
                'title'              => $followUp->title,
                'is_completed'       => $nowCompleted,
            ],
        ]);

        return back()->with('success', $nowCompleted ? 'Step marked as completed.' : 'Step reopened.');
    }
}
