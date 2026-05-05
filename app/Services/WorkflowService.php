<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStageHistory;
use App\Models\User;
use App\Notifications\StageTransitionNotification;
use App\Notifications\StageStatusChangedNotification;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    // ── Stage Definitions ────────────────────────────────────────────
    const STAGES = [
        1 => [
            'key'     => 'new_request',
            'label'   => 'New Request',
            'icon'    => 'bi-inbox',
            'color'   => 'secondary',
            'statuses'=> ['New'],
            'default' => 'New',
            'client_actionable' => false,
        ],
        2 => [
            'key'     => 'review',
            'label'   => 'Review',
            'icon'    => 'bi-search',
            'color'   => 'info',
            'statuses'=> ['Pending', 'Reviewed', 'Returned'],
            'default' => 'Pending',
            'client_actionable' => false,
        ],
        3 => [
            'key'     => 'preparation',
            'label'   => 'Preparation',
            'icon'    => 'bi-clipboard2-check',
            'color'   => 'primary',
            'statuses'=> ['Pending', 'Ready for Client', 'Returned'],
            'default' => 'Pending',
            'client_actionable' => false,
        ],
        4 => [
            'key'     => 'client_approval',
            'label'   => 'Client Approval',
            'icon'    => 'bi-person-check',
            'color'   => 'warning',
            'statuses'=> ['Awaiting Payment', 'Paid', 'Rejected', 'Returned'],
            'default' => 'Awaiting Payment',
            'client_actionable' => true, // client can set Paid or Rejected
        ],
        5 => [
            'key'     => 'execution',
            'label'   => 'Execution',
            'icon'    => 'bi-play-circle',
            'color'   => 'primary',
            'statuses'=> ['Pending', 'In Progress', 'Approved', 'Returned'],
            'default' => 'Pending',
            'client_actionable' => false,
        ],
        6 => [
            'key'     => 'monitoring',
            'label'   => 'Monitoring',
            'icon'    => 'bi-activity',
            'color'   => 'success',
            'statuses'=> ['In Progress', 'Partially Completed', 'Completed', 'Issues Detected'],
            'default' => 'In Progress',
            'client_actionable' => false,
        ],
        7 => [
            'key'     => 'closure',
            'label'   => 'Closure',
            'icon'    => 'bi-check-circle',
            'color'   => 'success',
            'statuses'=> ['Pending', 'Closed', 'Returned'],
            'default' => 'Pending',
            'client_actionable' => false,
        ],
    ];

    // ── Queries ──────────────────────────────────────────────────────

    public static function stage(int $n): array
    {
        return self::STAGES[$n] ?? self::STAGES[1];
    }

    public static function stageCount(): int
    {
        return count(self::STAGES);
    }

    public static function currentStageConfig(ServiceRequest $sr): array
    {
        return self::stage($sr->current_stage);
    }

    // ── Permission Checks ────────────────────────────────────────────

    public static function canAdvance(ServiceRequest $sr, User $user): bool
    {
        if ($sr->is_rejected) return false;
        if ($sr->current_stage >= self::stageCount()) return false;
        return $user->hasPermission('transition_stage') || $user->hasPermission('force_transition');
    }

    public static function canReturn(ServiceRequest $sr, User $user): bool
    {
        if ($sr->is_rejected) return false;
        if ($sr->current_stage <= 1) return false;
        return $user->hasPermission('transition_stage') || $user->hasPermission('force_transition');
    }

    public static function canForceTransition(User $user): bool
    {
        return $user->hasPermission('force_transition');
    }

    public static function canUpdateStatus(ServiceRequest $sr, User $user): bool
    {
        // Force-transition users can always override status, including on rejected requests
        if ($user->hasPermission('force_transition')) return true;

        // Nobody else can update status on a rejected request
        if ($sr->is_rejected) return false;

        // Client can take actions in client_approval stage (Paid / Rejected)
        if ($sr->current_stage === 4 && $sr->user_id === $user->id) return true;

        return $user->hasPermission('transition_stage') || $user->hasPermission('update_status');
    }

    // ── Transition Actions ───────────────────────────────────────────

    public static function advance(ServiceRequest $sr, User $actor, ?string $notes = null): void
    {
        if (! self::canAdvance($sr, $actor)) {
            abort(403, 'You are not allowed to advance this request.');
        }

        $fromStage = $sr->current_stage;
        $toStage   = $fromStage + 1;
        $stageCfg  = self::stage($toStage);

        DB::transaction(function () use ($sr, $actor, $fromStage, $toStage, $stageCfg, $notes) {
            $sr->update([
                'current_stage'    => $toStage,
                'stage_status'     => $stageCfg['default'],
                'stage_entered_at' => now(),
            ]);

            self::logHistory($sr, $fromStage, $toStage, $stageCfg['default'], 'advanced', $actor, $notes);
            self::logActivity($sr, 'stage_advanced', $actor, [
                'from_stage' => $fromStage, 'to_stage' => $toStage, 'notes' => $notes,
            ]);
        });

        self::notifyTransition($sr, $fromStage, $toStage, 'advanced', $actor);
    }

    public static function returnToPreviousStage(ServiceRequest $sr, User $actor, ?string $notes = null): void
    {
        if (! self::canReturn($sr, $actor)) {
            abort(403, 'You are not allowed to return this request.');
        }

        $fromStage = $sr->current_stage;
        $toStage   = $fromStage - 1;
        $stageCfg  = self::stage($toStage);

        DB::transaction(function () use ($sr, $actor, $fromStage, $toStage, $stageCfg, $notes) {
            $sr->update([
                'current_stage'    => $toStage,
                'stage_status'     => 'Returned',
                'stage_entered_at' => now(),
            ]);

            self::logHistory($sr, $fromStage, $toStage, 'Returned', 'returned', $actor, $notes);
            self::logActivity($sr, 'stage_returned', $actor, [
                'from_stage' => $fromStage, 'to_stage' => $toStage, 'notes' => $notes,
            ]);
        });

        self::notifyTransition($sr, $fromStage, $toStage, 'returned', $actor);
    }

    public static function forceTransition(ServiceRequest $sr, User $actor, int $toStage, ?string $notes = null): void
    {
        if (! self::canForceTransition($actor)) {
            abort(403, 'Force transition requires elevated permissions.');
        }

        if (! isset(self::STAGES[$toStage])) {
            abort(422, 'Invalid stage.');
        }

        $fromStage = $sr->current_stage;
        $stageCfg  = self::stage($toStage);

        DB::transaction(function () use ($sr, $actor, $fromStage, $toStage, $stageCfg, $notes) {
            $sr->update([
                'current_stage'    => $toStage,
                'stage_status'     => $stageCfg['default'],
                'is_rejected'      => false,
                'stage_entered_at' => now(),
            ]);

            self::logHistory($sr, $fromStage, $toStage, $stageCfg['default'], 'force_transitioned', $actor, $notes);
            self::logActivity($sr, 'stage_force_transitioned', $actor, [
                'from_stage' => $fromStage, 'to_stage' => $toStage, 'notes' => $notes,
            ]);
        });
    }

    public static function updateStatus(ServiceRequest $sr, User $actor, string $newStatus, ?string $notes = null): void
    {
        if (! self::canUpdateStatus($sr, $actor)) {
            abort(403, 'You cannot change status at this stage.');
        }

        $stageCfg = self::stage($sr->current_stage);

        if (! in_array($newStatus, $stageCfg['statuses'])) {
            abort(422, "Invalid status \"{$newStatus}\" for this stage.");
        }

        $oldStatus = $sr->stage_status;
        $isRejected = ($sr->current_stage === 4 && $newStatus === 'Rejected');

        DB::transaction(function () use ($sr, $actor, $newStatus, $oldStatus, $isRejected, $notes) {
            $sr->update([
                'stage_status' => $newStatus,
                'is_rejected'  => $isRejected,
            ]);

            self::logHistory($sr, $sr->current_stage, $sr->current_stage, $newStatus, 'status_changed', $actor, $notes);
            self::logActivity($sr, $isRejected ? 'request_rejected' : 'stage_status_changed', $actor, [
                'from_status' => $oldStatus, 'to_status' => $newStatus, 'notes' => $notes,
            ]);
        });

        self::notifyStatusChange($sr, $oldStatus, $newStatus, $actor);
    }

    // ── Internal Helpers ─────────────────────────────────────────────

    private static function logHistory(
        ServiceRequest $sr, ?int $fromStage, int $toStage,
        string $status, string $action, User $actor, ?string $notes
    ): void {
        ServiceRequestStageHistory::create([
            'service_request_id' => $sr->id,
            'from_stage'         => $fromStage,
            'to_stage'           => $toStage,
            'stage_key'          => self::STAGES[$toStage]['key'],
            'status'             => $status,
            'action'             => $action,
            'notes'              => $notes,
            'performed_by'       => $actor->id,
        ]);
    }

    private static function logActivity(ServiceRequest $sr, string $action, User $actor, array $changes): void
    {
        ActivityLog::create([
            'user'         => $actor->id,
            'action'       => $action,
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $sr->id,
            'changes'      => $changes,
        ]);
    }

    private static function notifyTransition(ServiceRequest $sr, int $fromStage, int $toStage, string $action, User $actor): void
    {
        $recipients = self::getRecipients($sr, $actor);

        foreach ($recipients as $user) {
            $user->notify(new StageTransitionNotification($sr, $fromStage, $toStage, $action, $actor));
        }
    }

    private static function notifyStatusChange(ServiceRequest $sr, string $from, string $to, User $actor): void
    {
        $recipients = self::getRecipients($sr, $actor);

        foreach ($recipients as $user) {
            $user->notify(new StageStatusChangedNotification($sr, $from, $to, $actor));
        }
    }

    private static function getRecipients(ServiceRequest $sr, User $actor): array
    {
        $recipients = collect();

        // Request owner (client)
        if ($sr->user?->id && $sr->user->id !== $actor->id) {
            $recipients->push($sr->user);
        }

        // Assigned employee — check the FK column directly so withDefault() can't sneak in a null-ID model
        if ($sr->assigned_to && $sr->assignedTo?->id && $sr->assignedTo->id !== $actor->id) {
            $recipients->push($sr->assignedTo);
        }

        return $recipients->unique('id')->all();
    }
}
