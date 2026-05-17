<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'created_by',
        'scheduled_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    // ─── Couleur selon le statut ──────────────────────────────────
    // Utilisée directement dans l'API pour le frontend
    public function getColorAttribute(): string
    {
        return match ($this->status) {
            'PLANIFIE' => 'gray',
            'CONFIRME' => 'blue',
            'TERMINE'  => 'green',
            'NO_SHOW'  => 'orange',
            default    => 'gray',
        };
    }

    // ─── Scope : RDV d'un jour précis ────────────────────────────
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('scheduled_date', $date);
    }

    // ─── Scope : RDV actifs (pas annulés ni no-show) ─────────────
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['ANNULE', 'NO_SHOW']);
    }

    // ─── Vérifie si un créneau chevauche ce RDV ──────────────────
    public static function hasConflict(
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeId = null // pour exclure le RDV en cours de modification
    ): bool {
        return self::where('scheduled_date', $date)
            ->whereNotIn('status', ['ANNULE', 'NO_SHOW'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($startTime, $endTime) {
                // Chevauchement si : début_existant < fin_nouveau ET fin_existant > début_nouveau
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    // ─── Relations ────────────────────────────────────────────────

    // Le patient concerné
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Qui a créé le RDV
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Les actes prévus pour ce RDV
    public function acts()
    {
        return $this->hasMany(AppointmentAct::class);
    }

    // Les actes avec les détails du catalogue
    public function catalogActs()
    {
        return $this->belongsToMany(
            CatalogAct::class,
            'appointment_acts'  // table pivot
        );
    }
}
