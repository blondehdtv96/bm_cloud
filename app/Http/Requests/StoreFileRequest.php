<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:524288'], // 512 MB dalam kilobyte.
            'folder_id' => [
                'nullable',
                'integer',
                Rule::exists('folders', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file yang akan diunggah.',
            'file.file' => 'Berkas yang dikirim bukan file yang valid.',
            'file.max' => 'Ukuran maksimum file adalah 512 MB.',
            'file.uploaded' => 'File gagal diterima server. Periksa batas upload PHP lalu coba lagi.',
            'folder_id.integer' => 'Folder tujuan tidak valid.',
            'folder_id.exists' => 'Folder tujuan tidak ditemukan atau bukan milik Anda.',
        ];
    }
}