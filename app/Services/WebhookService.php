<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch an event webhook to KCA (Knowledge Capital Atlas).
     */
    public static function dispatchEvent(string $eventName, array $data): bool
    {
        $kcaUrl = config('services.kca.webhook_url', 'https://app.kazma.ai/api/v1/integrations/almuhalab/webhook');
        $token  = config('services.kca.bridge_token', env('ALMUHALAB_BRIDGE_TOKEN', 'almuhalab_kca_secure_bridge_2026'));

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->post($kcaUrl, [
                    'event'     => $eventName,
                    'timestamp' => now()->toIso8601String(),
                    'data'      => $data,
                ]);

            if ($response->successful()) {
                Log::info("KCA Webhook dispatched successfully: {$eventName}");
                return true;
            } else {
                Log::warning("KCA Webhook returned status {$response->status()}: " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("KCA Webhook dispatch failed: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Dispatch service request event helper.
     */
    public static function dispatchServiceRequestEvent(string $eventName, ServiceRequest $sr): void
    {
        $payload = [
            'id'                 => $sr->id,
            'request_number'     => $sr->request_number,
            'display_number'     => $sr->display_number,
            'title'              => $sr->title,
            'description'        => $sr->description,
            'client_name'        => $sr->client_name,
            'client_email'       => $sr->client_email,
            'client_phone'       => $sr->client_phone,
            'current_stage'      => $sr->current_stage,
            'stage_status'       => $sr->stage_status,
            'is_rejected'        => $sr->is_rejected,
            'assigned_to'        => $sr->assignedTo->name ?? null,
            'service_type'       => $sr->serviceType->name ?? null,
            'created_at'         => $sr->created_at ? $sr->created_at->toIso8601String() : null,
            'updated_at'         => $sr->updated_at ? $sr->updated_at->toIso8601String() : null,
        ];

        self::dispatchEvent($eventName, $payload);
    }
}
