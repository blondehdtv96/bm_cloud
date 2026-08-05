<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'shareable_type' => 'required|in:file,folder',
            'shareable_id' => 'required|integer',
            'shared_to' => 'required|exists:users,id',
            'permission' => 'required|in:view,edit',
        ];
    }
}