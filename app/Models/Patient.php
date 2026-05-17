<?php

namespace App\Models;

use App\Models\PatientDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'numero_dossier',
        'first_name',
        'last_name',
        'phone',
        'birth_date',
        'gender',
        'couverture',
        'notes',
        'status',
        'is_archived',
        'archived_at',
        'no_show_count',
    ];

    protected $casts = [
        'birth_date'  => 'date',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    // ─── Accesseur : nom complet ──────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // ─── Accesseur : âge calculé depuis birth_date ────────────────
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date
            ? $this->birth_date->age
            : null;
    }

    // ─── Génère automatiquement le numéro de dossier ─────────────
    // Appelé dans le controller avant la création
    public static function generateNumeroDossier(): string
    {
        // max('id') au lieu de withTrashed() car on n'utilise pas SoftDeletes
        $last = self::max('id') ?? 0;
        return 'PAT-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    // ─── Scope : patients actifs uniquement ───────────────────────
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    // ─── Scope : recherche par nom, téléphone, numéro dossier ─────
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name',      'like', "%{$search}%")
                ->orWhere('last_name',     'like', "%{$search}%")
                ->orWhere('phone',         'like', "%{$search}%")
                ->orWhere('numero_dossier', 'like', "%{$search}%");
        });
    }

    // ─── Relations ────────────────────────────────────────────────

    // Un patient a plusieurs alertes médicales
    public function medicalAlerts()
    {
        return $this->hasMany(MedicalAlert::class);
    }

    // Alertes critiques uniquement (ROUGE)
    public function criticalAlerts()
    {
        return $this->hasMany(MedicalAlert::class)
            ->where('severity', 'ROUGE');
    }

    // Un patient a plusieurs documents
    public function documents()
    {
        return $this->hasMany(PatientDocument::class);
    }
    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
