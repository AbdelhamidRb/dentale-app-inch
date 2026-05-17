<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentAct extends Model
{
    protected $fillable = [
        'appointment_id',
        'catalog_act_id',
    ];

    // ─── Relations ────────────────────────────────────────────────

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function catalogAct()
    {
        return $this->belongsTo(CatalogAct::class);
    }
}