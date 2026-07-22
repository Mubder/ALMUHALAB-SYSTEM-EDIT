<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\StageComment;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KcaApiController extends Controller
{
    /**
     * Get list of service requests with pagination and optional filters.
     */
    public function getRequests(Request $request)
    {
        $status = $request->query('status');
        $stage = $request->query('stage');
        
        $query = ServiceRequest::with(['user', 'serviceType', 'assignedTo']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($stage) {
            $query->where('current_stage', $stage);
        }

        $requests = $query->latest('updated_at')->paginate($request->query('limit', 50));
        
        return response()->json($requests);
    }

    /**
     * Get detailed single service request info.
     */
    public function getRequestDetails($id)
    {
        $sr = ServiceRequest::with([
            'user', 
            'serviceType', 
            'assignedTo', 
            'requestServices.service', 
            'followUps', 
            'stageHistory', 
            'comments', 
            'fieldVisibilities'
        ])->findOrFail($id);
        
        return response()->json($sr);
    }

    /**
     * Add a stage comment on a service request.
     */
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
            'visibility' => 'nullable|string|in:all,employee,admin',
            'actor_email' => 'nullable|email'
        ]);

        $sr = ServiceRequest::findOrFail($id);
        
        // Resolve actor
        $actor = null;
        if ($request->actor_email) {
            $actor = User::where('email', $request->actor_email)->first();
        }
        if (!$actor) {
            $actor = User::first();
        }

        $comment = StageComment::create([
            'service_request_id' => $sr->id,
            'stage_number' => $sr->current_stage ?? 1,
            'content' => $request->content,
            'visibility' => $request->visibility ?? 'all',
            'created_by' => $actor ? $actor->id : null,
        ]);

        // Trigger native Laravel activity log
        $this->logActivity($sr, 'comment_added', "KCA added comment on stage {$sr->current_stage}: " . substr($request->content, 0, 50) . "...", $actor);

        return response()->json([
            'success' => true,
            'message' => 'Comment successfully posted.',
            'comment' => $comment->load('creator')
        ]);
    }

    /**
     * Advance the service request stage.
     */
    public function advanceStage(Request $request, $id)
    {
        $sr = ServiceRequest::findOrFail($id);
        
        // Resolve admin actor to bypass normal client restrictions
        $actor = User::first(); // Fallback actor

        if ($request->actor_email) {
            $resolvedActor = User::where('email', $request->actor_email)->first();
            if ($resolvedActor) {
                $actor = $resolvedActor;
            }
        }

        try {
            WorkflowService::advance($sr, $actor, $request->input('notes'));
            
            $this->logActivity($sr, 'stage_advanced', "KCA advanced request to stage {$sr->current_stage}.", $actor);
            
            return response()->json([
                'success' => true,
                'current_stage' => $sr->current_stage,
                'stage_status' => $sr->stage_status,
                'message' => 'Stage advanced successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Workflow transition failed.',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Assign a service request to an employee.
     */
    public function assignRequest(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|integer',
            'actor_email' => 'nullable|email'
        ]);

        $sr = ServiceRequest::findOrFail($id);
        $employee = User::findOrFail($request->assigned_to);

        $actor = null;
        if ($request->actor_email) {
            $actor = User::where('email', $request->actor_email)->first();
        }
        if (!$actor) {
            $actor = User::first();
        }

        $sr->update([
            'assigned_to' => $employee->id
        ]);

        $this->logActivity($sr, 'assigned', "KCA assigned request to {$employee->name}.", $actor);

        return response()->json([
            'success' => true,
            'assigned_to' => $employee->id,
            'message' => 'Request successfully assigned.'
        ]);
    }

    /**
     * Get system users to map list inside KCA.
     */
    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Get recent Almuhalab audit logs to stream to KCA founder dashboard.
     */
    public function getAuditLogs(Request $request)
    {
        $limit = $request->query('limit', 100);
        $logs = ActivityLog::latest()->take($limit)->get();
        return response()->json($logs);
    }

    /**
     * Helper to log activity natively inside Almuhalab.
     */
    private function logActivity($subject, $action, $description, $user = null)
    {
        try {
            ActivityLog::create([
                'user' => $user ? (string)$user->id : '1',
                'action' => $action . ': ' . $description,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'changes' => null
            ]);
        } catch (\Exception $e) {
            // Quiet fallback
        }
    }
}
