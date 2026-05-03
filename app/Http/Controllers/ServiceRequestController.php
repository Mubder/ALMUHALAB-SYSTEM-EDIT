<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\ActivityLog;
use App\Http\Requests\ServiceRequestRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $items = ServiceRequest::orderBy('created_at', 'desc')->get();
        return view('service_requests.index', ['items' => $items]);
    }

    public function create()
    {
        return view('service_requests.create');
    }

    public function store(ServiceRequestRequest $request)
    {
        $data = $request->validatedPayload();

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('service_requests', 'public');
            $data['attachment_path'] = $path;
        }

        $sr = ServiceRequest::create($data);

        ActivityLog::create([
            'user' => auth()->check() ? auth()->user()->id : null,
            'action' => 'created',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $sr->id,
            'changes' => $sr->toArray(),
        ]);

        return redirect()->route('service-requests.show', $sr)->with('success', 'Service request created.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        return view('service_requests.show', ['serviceRequest' => $serviceRequest]);
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        return view('service_requests.edit', ['serviceRequest' => $serviceRequest]);
    }

    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validatedPayload();

        if ($request->hasFile('attachment')) {
            // delete old file if exists
            if ($serviceRequest->attachment_path) {
                try { \Storage::disk('public')->delete($serviceRequest->attachment_path); } catch (\Exception $e) {}
            }
            $path = $request->file('attachment')->store('service_requests', 'public');
            $data['attachment_path'] = $path;
        }

        $original = $serviceRequest->getOriginal();
        $serviceRequest->update($data);

        ActivityLog::create([
            'user' => auth()->check() ? auth()->user()->id : null,
            'action' => 'updated',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $serviceRequest->id,
            'changes' => [
                'before' => $original,
                'after' => $serviceRequest->toArray(),
            ],
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Updated.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $data = $serviceRequest->toArray();

        if ($serviceRequest->attachment_path) {
            try { \Storage::disk('public')->delete($serviceRequest->attachment_path); } catch (\Exception $e) {}
        }

        $serviceRequest->delete();

        ActivityLog::create([
            'user' => auth()->check() ? auth()->user()->id : null,
            'action' => 'deleted',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $serviceRequest->id,
            'changes' => $data,
        ]);

        return redirect()->route('service-requests.index')->with('success', 'Deleted.');
    }
}
