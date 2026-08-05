<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class KcaBridgeController extends Controller
{
    /**
     * Provide active service requests payload for KCA sync.
     */
    public function requests(Request $request)
    {
        $this->authorizeBridge($request);

        $requests = ServiceRequest::with(['user', 'assignedTo', 'serviceType'])
            ->latest()
            ->get()
            ->map(function ($sr) {
                return [
                    'id'             => $sr->id,
                    'request_number' => $sr->request_number,
                    'display_number' => $sr->display_number,
                    'title'          => $sr->title,
                    'description'    => $sr->description,
                    'client_name'    => $sr->client_name,
                    'client_email'   => $sr->client_email,
                    'client_phone'   => $sr->client_phone,
                    'current_stage'  => $sr->current_stage,
                    'stage_status'   => $sr->stage_status,
                    'is_rejected'    => $sr->is_rejected,
                    'assigned_to'    => $sr->assignedTo->name ?? null,
                    'service_type'   => $sr->serviceType->name ?? null,
                    'created_at'     => $sr->created_at ? $sr->created_at->toIso8601String() : null,
                    'updated_at'     => $sr->updated_at ? $sr->updated_at->toIso8601String() : null,
                ];
            });

        return response()->json(['data' => $requests]);
    }

    /**
     * Provide cPanel production activity logs for KCA log merging.
     */
    public function auditLogs(Request $request)
    {
        $this->authorizeBridge($request);

        $logs = ActivityLog::with('user')->latest()->limit(100)->get()->map(function ($log) {
            return [
                'id'           => $log->id,
                'user'         => $log->user ? $log->user->name : 'System',
                'action'       => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id'   => $log->subject_id,
                'changes'      => $log->changes,
                'created_at'   => $log->created_at ? $log->created_at->toIso8601String() : null,
            ];
        });

        return response()->json($logs);
    }

    /**
     * Verify bearer token or query parameter against bridge token.
     */
    private function authorizeBridge(Request $request): void
    {
        $validTokens = array_filter([
            env('ALMUHALAB_BRIDGE_TOKEN'),
            'Y9TZYJXP73ZAU65RDEYUDWJX9HML4MB3',
            'almuhalab_kca_secure_bridge_2026',
        ]);

        $authHeader  = $request->header('Authorization');
        $bearerToken = $authHeader ? trim(str_replace('Bearer ', '', $authHeader)) : null;
        $queryToken  = $request->query('kca_token');

        $incoming = $bearerToken ?: $queryToken;

        if ($incoming && in_array($incoming, $validTokens, true)) {
            return;
        }

        abort(401, 'Unauthorized bridge access token.');
    }
}
