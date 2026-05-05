<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\RequestService;
use App\Models\ServiceCatalog;
use App\Models\ServiceRequest;
use App\Notifications\ServiceStatusUpdatedNotification;
use Illuminate\Http\Request;

class RequestServiceController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'service_catalog_id' => 'required|exists:service_catalog,id',
            'status'             => 'required|in:pending,booked,completed,cancelled',
            'scheduled_at'       => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
            'reference'          => 'nullable|string|max:100',
        ]);

        $data['service_request_id'] = $serviceRequest->id;
        $data['created_by']         = auth()->id();

        $rs = RequestService::create($data);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'service_added',
            'subject_type' => RequestService::class,
            'subject_id'   => $rs->id,
            'changes'      => ['service_request_id' => $serviceRequest->id] + $rs->toArray(),
        ]);

        return back()->with('success', 'Service added successfully.');
    }

    public function update(Request $request, ServiceRequest $serviceRequest, RequestService $requestService)
    {
        abort_unless($requestService->service_request_id === $serviceRequest->id, 404);

        $data = $request->validate([
            'status'       => 'required|in:pending,booked,completed,cancelled',
            'scheduled_at' => 'nullable|date',
            'notes'        => 'nullable|string|max:1000',
            'reference'    => 'nullable|string|max:100',
        ]);

        $before = $requestService->toArray();
        $requestService->update($data);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'service_updated',
            'subject_type' => RequestService::class,
            'subject_id'   => $requestService->id,
            'changes'      => [
                'service_request_id' => $serviceRequest->id,
                'before' => $before,
                'after'  => $requestService->fresh()->toArray(),
            ],
        ]);

        if ($before['status'] !== $requestService->status && $serviceRequest->user?->id) {
            $serviceRequest->user->notify(
                new ServiceStatusUpdatedNotification($serviceRequest, $requestService, $before['status'], auth()->user())
            );
        }

        return back()->with('success', 'Service updated.');
    }

    public function destroy(ServiceRequest $serviceRequest, RequestService $requestService)
    {
        abort_unless($requestService->service_request_id === $serviceRequest->id, 404);

        $data = $requestService->toArray();
        $requestService->delete();

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'service_removed',
            'subject_type' => RequestService::class,
            'subject_id'   => $requestService->id,
            'changes'      => ['service_request_id' => $serviceRequest->id] + $data,
        ]);

        return back()->with('success', 'Service removed.');
    }
}
