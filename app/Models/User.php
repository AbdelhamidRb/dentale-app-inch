<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'role',
        'avatar',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function isDentist(): bool
    {
        return $this->role === 'DENTIST';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'ASSISTANT';
    }

    // Un utilisateur peut créer des alertes médicales
    public function medicalAlerts()
    {
        return $this->hasMany(MedicalAlert::class, 'created_by');
    }

    // Un utilisateur peut uploader des documents
    public function uploadedDocuments()
    {
        return $this->hasMany(PatientDocument::class, 'uploaded_by');
    }
    // Un utilisateur peut créer des RDV
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }
}
