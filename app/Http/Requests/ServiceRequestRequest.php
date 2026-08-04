<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Client info
            'client_name'         => 'nullable|string|max:150',
            'client_phone_code'   => 'nullable|string|max:10',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',

            // Request core
            'title'               => 'required|string|max:255',
            'service_type_id'     => 'nullable|exists:service_types,id',
            'description'         => 'required|string',
            'status'              => 'nullable|string|in:New,Under Review,Approved,Rejected,Completed',
            'assigned_to'         => 'nullable|exists:users,id',

            // Travel info
            'client_country'      => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100',
            'destination_city'    => 'nullable|string|max:100',
            'travel_date_start'   => 'nullable|date',
            'travel_date_end'     => 'nullable|date|after_or_equal:travel_date_start',

            // Companions
            'companions_count'          => 'nullable|integer|min:0|max:99',
            'companions_data'           => 'nullable|array',
            'companions_data.*.name'    => 'nullable|string|max:150',
            'companions_data.*.phone_code' => 'nullable|string|max:10',
            'companions_data.*.phone'   => 'nullable|string|max:30',
            'companions_data.*.email'   => 'nullable|email|max:150',

            // Notes & attachments
            'additional_notes'    => 'nullable|string|max:2000',
            'attachments'         => 'nullable|array',
            'attachments.*'       => 'file|max:1048576',
        ];
    }

    public function messages(): array
    {
        return [
            'travel_date_end.after_or_equal' => 'End date must be on or after the start date.',
            'attachments.*.max'              => 'Each file must not exceed 1 GB.',
        ];
    }

    public function validatedPayload(): array
    {
        $data = $this->safe()->except(['attachments']);

        if (! auth()->user()->hasPermission('update_status')) {
            if ($this->isMethod('post')) {
                $data['status'] = 'New';
            } else {
                unset($data['status']);
            }
        } elseif (! $this->filled('status')) {
            if ($this->isMethod('post')) {
                $data['status'] = 'New';
            } else {
                unset($data['status']);
            }
        }

        // Only staff can assign requests
        if (! auth()->user()->hasPermission('edit_request')) {
            unset($data['assigned_to']);
        }

        if (! $this->filled('companions_count')) {
            $data['companions_count'] = 0;
        }

        // Keep only companions up to companions_count, remove empty rows
        $count = (int) ($data['companions_count'] ?? 0);
        if ($count > 0 && ! empty($data['companions_data'])) {
            $data['companions_data'] = collect($data['companions_data'])
                ->take($count)
                ->filter(fn($c) => ! empty($c['name']) || ! empty($c['phone']))
                ->values()
                ->toArray();
        } else {
            $data['companions_data'] = null;
        }

        return $data;
    }
}
