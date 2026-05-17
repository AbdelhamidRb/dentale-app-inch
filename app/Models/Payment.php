<?php

namespace App\Models;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'patient_id',
        'consultation_id',
        'created_by',
        'label',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount'     => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // ─── Recalcule paid_amount, remaining_amount et status ────────
    public function recalculate(): void
    {
        $paid      = $this->transactions()->sum('amount');
        $remaining = max(0, $this->total_amount - $paid);

        $status = match(true) {
            $paid <= 0                          => $this->consultation_id
                                                    ? 'EN_ATTENTE'
                                                    : 'AVANCE',
            $remaining <= 0                     => 'PAYÉ',
            default                             => 'PARTIEL',
        };

        // Si c'est un acompte (sans consultation) et qu'il y a des versements
        if (!$this->consultation_id && $paid > 0) {
            $status = 'AVANCE';
        }

        $this->update([
            'paid_amount'      => $paid,
            'remaining_amount' => $remaining,
            'status'           => $status,
        ]);
    }

    // ─── Scopes ───────────────────────────────────────────────────
    public function scopeEnAttente(Builder $q): Builder
    {
        return $q->where('status', 'EN_ATTENTE');
    }

    public function scopePartiel(Builder $q): Builder
    {
        return $q->where('status', 'PARTIEL');
    }

    public function scopePaye(Builder $q): Builder
    {
        return $q->where('status', 'PAYÉ');
    }

    public function scopeForPatient(Builder $q, int $patientId): Builder
    {
        return $q->where('patient_id', $patientId);
    }

    // ─── Relations ────────────────────────────────────────────────
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class)->orderBy('date');
    }
}