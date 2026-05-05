<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\StageServiceMapping;
use App\Models\ActivityLog;
use App\Http\Requests\ServiceRequestRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasPermission('edit_request');

        $baseQuery = $isAdmin
            ? ServiceRequest::query()
            : ServiceRequest::where('user_id', $user->id);

        // Stats always reflect full scope (no filters)
        $stats = [
            'total'        => (clone $baseQuery)->count(),
            'new'          => (clone $baseQuery)->where('status', 'New')->count(),
            'under_review' => (clone $baseQuery)->where('status', 'Under Review')->count(),
            'approved'     => (clone $baseQuery)->where('status', 'Approved')->count(),
            'completed'    => (clone $baseQuery)->where('status', 'Completed')->count(),
        ];

        // Filtered query for the table
        $query = (clone $baseQuery)->orderBy('created_at', 'desc');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search, $isAdmin) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('client_country', 'like', "%{$search}%")
                  ->orWhere('destination_country', 'like', "%{$search}%");
                if ($isAdmin) {
                    $q->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
                }
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($typeId = $request->input('service_type_id')) {
            $query->where('service_type_id', $typeId);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $items        = $query->with(['user', 'serviceType'])->paginate(15)->withQueryString();
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();

        return view('service_requests.index', compact('items', 'stats', 'isAdmin', 'serviceTypes'));
    }

    public function create()
    {
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();
        return view('service_requests.create', compact('serviceTypes'));
    }

    public function store(ServiceRequestRequest $request)
    {
        $data = $request->validatedPayload();
        $data['user_id'] = auth()->id();

        $sr = ServiceRequest::create($data);

        // Store multiple attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('service_requests', 'public');
                $sr->attachments()->create([
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'created',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $sr->id,
            'changes'      => $sr->toArray(),
        ]);

        return redirect()->route('service-requests.show', $sr)->with('success', 'Request created successfully.');
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorizeAccess($serviceRequest);

        $serviceRequest->load(['attachments', 'serviceType', 'user', 'requestServices.service']);

        // Determine current stage from follow-ups
        $currentStage = $serviceRequest->followUps()
            ->where('is_completed', false)
            ->orderBy('scheduled_at')->orderBy('created_at')
            ->value('status_type');

        // Suggested services for this stage (not already added)
        $addedServiceIds  = $serviceRequest->requestServices->pluck('service_catalog_id')->toArray();
        $suggestedServices = $currentStage
            ? StageServiceMapping::where('status_type', $currentStage)
                ->with('service')
                ->get()
                ->map(fn($m) => $m->service)
                ->filter(fn($s) => $s && $s->is_active && ! in_array($s->id, $addedServiceIds))
                ->values()
            : collect();

        // All catalog services (for the "add any service" dropdown)
        $allCatalogServices = \App\Models\ServiceCatalog::where('is_active', true)->orderBy('name')->get();

        return view('service_requests.show', compact(
            'serviceRequest', 'suggestedServices', 'allCatalogServices', 'currentStage'
        ));
    }

    public function edit(ServiceRequest $serviceRequest)
    {
        $this->authorizeAccess($serviceRequest);
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();
        return view('service_requests.edit', compact('serviceRequest', 'serviceTypes'));
    }

    public function update(ServiceRequestRequest $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeAccess($serviceRequest);

        $data = $request->validatedPayload();

        // Append new attachments (keep existing ones)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('service_requests', 'public');
                $serviceRequest->attachments()->create([
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size'     => $file->getSize(),
                ]);
            }
        }

        $original = $serviceRequest->getOriginal();
        $serviceRequest->update($data);

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'updated',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $serviceRequest->id,
            'changes'      => ['before' => $original, 'after' => $serviceRequest->toArray()],
        ]);

        return redirect()->route('service-requests.show', $serviceRequest)->with('success', 'Request updated.');
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $this->authorizeAccess($serviceRequest);

        $data = $serviceRequest->toArray();
        $serviceRequest->delete();

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'deleted',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $serviceRequest->id,
            'changes'      => $data,
        ]);

        return redirect()->route('service-requests.index')->with('success', 'Request moved to trash.');
    }

    public function trash()
    {
        $items = ServiceRequest::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        return view('service_requests.trash', compact('items'));
    }

    public function restore($id)
    {
        $sr = ServiceRequest::onlyTrashed()->findOrFail($id);
        $sr->restore();

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'restored',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $sr->id,
            'changes'      => $sr->toArray(),
        ]);

        return redirect()->route('service-requests.trash')->with('success', 'Request restored.');
    }

    public function forceDelete($id)
    {
        $sr = ServiceRequest::onlyTrashed()->findOrFail($id);
        $data = $sr->toArray();

        // Delete legacy single attachment
        if ($sr->attachment_path) {
            try { \Storage::disk('public')->delete($sr->attachment_path); } catch (\Exception $e) {}
        }

        // Delete all attachments from attachments table
        foreach ($sr->attachments as $attachment) {
            try { \Storage::disk('public')->delete($attachment->file_path); } catch (\Exception $e) {}
        }

        $sr->forceDelete();

        ActivityLog::create([
            'user'         => auth()->id(),
            'action'       => 'permanently_deleted',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $id,
            'changes'      => $data,
        ]);

        return redirect()->route('service-requests.trash')->with('success', 'Request permanently deleted.');
    }

    public function showTrashed($id)
    {
        $serviceRequest = ServiceRequest::withTrashed()->findOrFail($id);
        return view('service_requests.show_trashed', compact('serviceRequest'));
    }

    public function deleteAttachment(ServiceRequest $serviceRequest, Attachment $attachment)
    {
        $this->authorizeAccess($serviceRequest);

        try { \Storage::disk('public')->delete($attachment->file_path); } catch (\Exception $e) {}
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    // Ensure non-admin users can only access their own requests
    private function authorizeAccess(ServiceRequest $serviceRequest): void
    {
        $user = auth()->user();
        if (!$user->hasPermission('edit_request') && $serviceRequest->user_id !== $user->id) {
            abort(403, 'You do not have access to this request.');
        }
    }
}
