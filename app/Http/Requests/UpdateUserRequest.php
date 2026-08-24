<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'sometimes|string',
            'username' => 'sometimes|string|alpha_dash|unique:users,username,'.$this->route('id'),
            'email' => 'sometimes|email|unique:users,email,'.$this->route('id'),
            'password' => 'nullable|min:8',
            'role_id' => 'sometimes|exists:roles,id',
            'storage_quota' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive,suspended',
        ];
    }
}