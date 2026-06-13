<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\StageServiceMapping;
use App\Models\ActivityLog;
use App\Models\User;
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

        // Filtered query for the table — ascending by display number (#1 first)
        $query = (clone $baseQuery)->orderByDesc('created_at');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search, $isAdmin) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
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

        if ($request->boolean('assigned_to_me')) {
            $query->where('assigned_to', $user->id);
        } elseif ($assignedTo = $request->input('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        $items        = $query->with(['user', 'serviceType', 'assignedTo'])->paginate(15)->withQueryString();
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();
        $staffUsers   = $isAdmin ? User::whereHas('role')->orderBy('name')->get() : collect();

        return view('service_requests.index', compact('items', 'stats', 'isAdmin', 'serviceTypes', 'staffUsers'));
    }

    public function create()
    {
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();
        $roles        = \App\Models\Role::orderBy('name')->get();
        $staffUsers   = User::whereHas('role')->orderBy('name')->get();
        return view('service_requests.create', compact('serviceTypes', 'roles', 'staffUsers'));
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

        // Save field visibility rules set by staff during creation
        $this->saveFieldVisibility($request, $sr);

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
        $roles        = \App\Models\Role::orderBy('name')->get();
        $staffUsers   = User::whereHas('role')->orderBy('name')->get();
        $serviceRequest->load('fieldVisibilities');
        $fieldVisMap = $serviceRequest->fieldVisibilityMap();
        return view('service_requests.edit', compact('serviceRequest', 'serviceTypes', 'roles', 'staffUsers', 'fieldVisMap'));
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

        $this->saveFieldVisibility($request, $serviceRequest);

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
        $items = ServiceRequest::onlyTrashed()->orderBy('display_number', 'asc')->get();
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

    public function downloadAttachment(Attachment $attachment)
    {
        $user = auth()->user();

        if (! $attachment->isVisibleTo($user)) {
            abort(403, 'You do not have access to this file.');
        }

        $path = storage_path('app/public/' . $attachment->file_path);

        if (! file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->download($path, $attachment->original_name);
    }

    public function updateFieldVisibility(Request $request, ServiceRequest $serviceRequest, string $field)
    {
        $allowedFields = array_keys(\App\Models\RequestFieldVisibility::FIELDS);

        if (! in_array($field, $allowedFields)) {
            abort(404);
        }

        $roleNames = \App\Models\Role::orderBy('name')->pluck('name')->toArray();

        $request->validate([
            'visibility' => 'required|in:all,' . implode(',', $roleNames),
        ]);

        $val = $request->input('visibility');

        if ($val === 'all') {
            \App\Models\RequestFieldVisibility::where('service_request_id', $serviceRequest->id)
                ->where('field_name', $field)
                ->delete();
        } else {
            \App\Models\RequestFieldVisibility::updateOrCreate(
                ['service_request_id' => $serviceRequest->id, 'field_name' => $field],
                ['visibility' => 'admin', 'required_permission' => $val]
            );
        }

        return back()->with('success', 'Field visibility updated.');
    }

    public function updateAttachmentVisibility(Request $request, ServiceRequest $serviceRequest, Attachment $attachment)
    {
        $roleNames = \App\Models\Role::orderBy('name')->pluck('name')->toArray();

        $request->validate([
            'visibility' => 'required|in:all,' . implode(',', $roleNames),
        ]);

        $val = $request->input('visibility');

        if ($val === 'all') {
            $attachment->update(['visibility' => 'all', 'required_permission' => null]);
        } else {
            $attachment->update(['visibility' => 'admin', 'required_permission' => $val]);
        }

        return back()->with('success', 'Attachment visibility updated.');
    }

    public function deleteAttachment(ServiceRequest $serviceRequest, Attachment $attachment)
    {
        $this->authorizeAccess($serviceRequest);

        try { \Storage::disk('public')->delete($attachment->file_path); } catch (\Exception $e) {}
        $attachment->delete();

        return back()->with('success', 'Attachment removed.');
    }

    public function export(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasPermission('edit_request');

        $query = $isAdmin
            ? ServiceRequest::query()
            : ServiceRequest::where('user_id', $user->id);

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }
        if ($status   = $request->input('status'))          { $query->where('status', $status); }
        if ($typeId   = $request->input('service_type_id')) { $query->where('service_type_id', $typeId); }
        if ($from     = $request->input('date_from'))       { $query->whereDate('created_at', '>=', $from); }
        if ($to       = $request->input('date_to'))         { $query->whereDate('created_at', '<=', $to); }

        $rows = $query->with(['user', 'serviceType', 'assignedTo'])
                      ->orderByDesc('created_at')
                      ->get();

        $format = $request->input('format', 'csv');

        if ($format === 'print') {
            return view('service_requests.export_print', compact('rows', 'isAdmin'));
        }

        // CSV export
        $filename = 'requests-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows, $isAdmin) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            $head = ['#', 'Request No.', 'Title', 'Service Type', 'Status', 'Date'];
            if ($isAdmin) { $head[] = 'Submitted By'; $head[] = 'Assigned To'; }
            fputcsv($handle, $head);

            foreach ($rows as $i => $r) {
                $row = [
                    $i + 1,
                    $r->request_number ?? '',
                    $r->title,
                    $r->serviceType->name ?? '',
                    $r->status,
                    $r->created_at->format('Y-m-d'),
                ];
                if ($isAdmin) {
                    $row[] = $r->user->name ?? '';
                    $row[] = $r->assignedTo->name ?? '';
                }
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function saveFieldVisibility(Request $request, ServiceRequest $sr): void
    {
        if (! auth()->user()->hasPermission('edit_request')) {
            return;
        }

        $allowed   = array_keys(\App\Models\RequestFieldVisibility::FIELDS);
        $roleNames = \App\Models\Role::pluck('name')->toArray();
        $visRules  = $request->input('fv', []);

        foreach ($allowed as $field) {
            $val = $visRules[$field] ?? 'all';

            if ($val === 'all' || ! in_array($val, $roleNames)) {
                \App\Models\RequestFieldVisibility::where('service_request_id', $sr->id)
                    ->where('field_name', $field)
                    ->delete();
                continue;
            }

            // $val is a role name → store as admin-restricted
            \App\Models\RequestFieldVisibility::updateOrCreate(
                ['service_request_id' => $sr->id, 'field_name' => $field],
                ['visibility' => 'admin', 'required_permission' => $val]
            );
        }
    }

    // Ensure non-admin users can only access their own requests
    private function authorizeAccess(ServiceRequest $serviceRequest): void
    {
        $user = auth()->user();
        // Staff with edit_request can access any request
        if ($user->hasPermission('edit_request')) {
            return;
        }
        // Clients can only access their own requests
        if ($serviceRequest->user_id !== $user->id) {
            abort(403, 'You do not have access to this request.');
        }
    }
}
