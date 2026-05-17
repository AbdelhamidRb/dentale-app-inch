<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogAct extends Model
{
    protected $fillable = [
        'code',
        'name',
        'base_price',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'base_price'       => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    // ─── Scope : actes actifs uniquement ─────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relations ────────────────────────────────────────────────

    // Un acte peut être prévu dans plusieurs RDV
    public function appointments()
    {
        return $this->belongsToMany(
            Appointment::class,
            'appointment_acts'
        );
    }
}