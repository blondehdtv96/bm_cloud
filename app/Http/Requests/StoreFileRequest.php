<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
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
            'file.uploaded' => $this->uploadFailureMessage(),
            'folder_id.integer' => 'Folder tujuan tidak valid.',
            'folder_id.exists' => 'Folder tujuan tidak ditemukan atau bukan milik Anda.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $uploadedFile = $this->file('file');

        Log::warning('File upload validation failed.', [
            'user_id' => $this->user()?->id,
            'upload_error_code' => $uploadedFile?->getError(),
            'upload_error' => $uploadedFile?->getErrorMessage(),
            'content_length' => $this->server('CONTENT_LENGTH'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'validation_errors' => $validator->errors()->toArray(),
        ]);

        parent::failedValidation($validator);
    }

    private function uploadFailureMessage(): string
    {
        $error = $this->file('file')?->getError();

        return match ($error) {
            UPLOAD_ERR_INI_SIZE => sprintf(
                'Ukuran file melebihi batas PHP server (%s). Restart Apache/server Laravel setelah mengubah php.ini.',
                ini_get('upload_max_filesize') ?: 'tidak diketahui'
            ),
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir upload.',
            UPLOAD_ERR_PARTIAL => 'File hanya terkirim sebagian. Periksa koneksi lalu coba lagi.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diterima server.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload PHP tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file ke folder temporary.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP pada server.',
            default => 'File gagal diterima server. Coba ulangi upload atau periksa konfigurasi PHP.',
        };
    }
}