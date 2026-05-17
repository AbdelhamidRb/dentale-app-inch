<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationAct extends Model
{
    protected $fillable = [
        'consultation_id',
        'catalog_act_id',
        'teeth',
        'price',
        'notes',
    ];

    protected $casts = [
        'teeth' => 'array',
        'price' => 'decimal:2',
    ];

    // Force l'arrondi au MAD entier à la sauvegarde
    public function setPriceAttribute($value): void
    {
        $this->attributes['price'] = round((float) $value);
    }

    // ─── Relations ────────────────────────────────────────────────

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function catalogAct()
    {
        return $this->belongsTo(CatalogAct::class);
    }
}