<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalAlert extends Model
{
    protected $fillable = [
        'patient_id',
        'type',
        'description',
        'severity',
        'created_by',
    ];

    // ─── Relations ────────────────────────────────────────────────

    // Appartient à un patient
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Créée par un utilisateur
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}