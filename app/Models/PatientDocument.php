<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'consultation_id',
        'type',
        'original_name',
        'stored_name',
        'path',
        'size',
        'uploaded_by',
    ];

    // ─── Accesseur : URL complète du fichier ──────────────────────
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    // ─── Accesseur : taille lisible (ex: "2.4 Mo") ───────────────
    public function getReadableSizeAttribute(): string
    {
        $size = $this->size;
        if ($size < 1024)       return $size . ' o';
        if ($size < 1048576)    return round($size / 1024, 1) . ' Ko';
        return round($size / 1048576, 1) . ' Mo';
    }

    // ─── Relations ────────────────────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}