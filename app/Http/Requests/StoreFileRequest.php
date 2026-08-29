<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'file' => 'required|file|max:1048576',
            'folder_id' => 'nullable|exists:folders,id',
        ];
    }
}