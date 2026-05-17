<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'created_by',
        'status',
        'notes',
        'total_price',
        'session_dates',
    ];

    protected $casts = [
        'session_dates' => 'array',
        'total_price'   => 'decimal:2',
    ];

    // ─── Recalcule et sauvegarde le total depuis les actes ────────
    public function recalculateTotal(): void
    {
        $total = $this->acts()->sum('price');
        $this->update(['total_price' => $total]);
    }

    // ─── Ajoute une date de séance si elle n'existe pas déjà ──────
    public function addSessionDate(string $date): void
    {
        $dates = $this->session_dates ?? [];
        if (!in_array($date, $dates)) {
            $dates[] = $date;
            sort($dates); // Garde les dates dans l'ordre chronologique
            $this->update(['session_dates' => $dates]);
        }
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('status', 'EN_COURS');
    }

    public function scopeTermine(Builder $query): Builder
    {
        return $query->where('status', 'TERMINE');
    }

    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    // ─── Relations ────────────────────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Actes de cette consultation (avec prix + dents)
    public function acts()
    {
        return $this->hasMany(ConsultationAct::class);
    }

    // Actes du catalogue associés (via pivot)
    public function catalogActs()
    {
        return $this->belongsToMany(
            CatalogAct::class,
            'consultation_acts'
        )->withPivot(['teeth', 'price', 'notes'])
            ->withTimestamps();
    }
}
