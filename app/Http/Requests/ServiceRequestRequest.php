<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file|max:5120', // max 5MB
            // accept legacy 'open' but preferred values are the workflow states
            'status' => 'nullable|string|in:New,Under Review,Approved,Rejected,Completed',
        ];
    }

    public function validatedPayload()
    {
        $data = $this->validated();

        // If status not provided: on creation set default 'New', on update leave as-is (don't overwrite)
        if (!$this->filled('status')) {
            if ($this->isMethod('post')) {
                $data['status'] = 'New';
            } else {
                // ensure we don't unintentionally overwrite status on update
                unset($data['status']);
            }
        }

        // Convert legacy 'open' value to the new 'New' state
        if (isset($data['status']) && $data['status'] === 'open') {
            $data['status'] = 'New';
        }

        return $data;
    }
}
