<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShareLinkRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'shareable_type' => 'required|in:file,folder',
            'shareable_id' => 'required|integer',
            'password' => 'nullable|min:6',
            'expires_at' => 'nullable|date|after:now',
        ];
    }
}