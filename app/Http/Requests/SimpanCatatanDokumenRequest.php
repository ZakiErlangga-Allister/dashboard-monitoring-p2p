<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class SimpanCatatanDokumenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->aktif && $user->peran === 'staf_keuangan';
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge([
            'bg_ada' => $this->boolean('bg_ada'),
            'skf_ada' => $this->boolean('skf_ada'),
            'surat_pernyataan_ada' => $this->boolean('surat_pernyataan_ada'),
            'rekening_ada' => $this->boolean('rekening_ada'),
        ]);
    }
    public function rules(): array
    {
        $lengkap = $this->bg_ada && $this->skf_ada
            && $this->surat_pernyataan_ada && $this->rekening_ada;

        return [
            'bg_ada' => ['boolean'],
            'skf_ada' => ['boolean'],
            'surat_pernyataan_ada' => ['boolean'],
            'rekening_ada' => ['boolean'],
            'ttd_status' => ['required', Rule::in(['belum', 'sudah'])],
            'ttd_tanggal' => ['nullable', 'date', 'required_if:ttd_status,sudah'],
            'lokasi_arsip' => ['nullable', 'string', 'max:50', Rule::requiredif($lengkap)],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'ttd_status.required' => 'status tanda tangan wajib dipilih',
            'ttd_status.in' => 'Status tanda tangan tidak valid.',
            'ttd_tanggal.required_if' => 'tanggal tanda tangan wajib diisi bila status sudah.',
            'ttd_tanngal.date' => 'tanggal tanda tangan tidak valid',
            'lokasi_arsip.required' => 'lokasi arsip wajib diisi keempat butir kelengkapan sudah ada',
            'lokasi_arsip.max' => 'lokasi arsip maksimal 50 karakter'
        ];
    }
}
