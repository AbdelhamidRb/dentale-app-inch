<?php

namespace Database\Seeders;

use App\Models\CatalogAct;
use Illuminate\Database\Seeder;

class CatalogActSeeder extends Seeder
{
    public function run(): void
    {
        $acts = [
            ['code' => 'CONS001', 'name' => 'Consultation générale',     'base_price' => 150,  'duration_minutes' => 30],
            ['code' => 'DET001',  'name' => 'Détartrage',                'base_price' => 400,  'duration_minutes' => 30],
            ['code' => 'EXT001',  'name' => 'Extraction simple',         'base_price' => 300,  'duration_minutes' => 30],
            ['code' => 'EXT002',  'name' => 'Extraction chirurgicale',   'base_price' => 600,  'duration_minutes' => 60],
            ['code' => 'OBT001',  'name' => 'Obturation composite',      'base_price' => 350,  'duration_minutes' => 30],
            ['code' => 'CAN001',  'name' => 'Traitement de canal',       'base_price' => 1200, 'duration_minutes' => 60],
            ['code' => 'COU001',  'name' => 'Pose couronne',             'base_price' => 2500, 'duration_minutes' => 60],
            ['code' => 'RAD001',  'name' => 'Radiographie rétro-alvéolaire', 'base_price' => 100, 'duration_minutes' => 15],
            ['code' => 'RAD002',  'name' => 'Panoramique',               'base_price' => 250,  'duration_minutes' => 15],
            ['code' => 'BLA001',  'name' => 'Blanchiment',               'base_price' => 1500, 'duration_minutes' => 60],
        ];

        foreach ($acts as $act) {
            CatalogAct::firstOrCreate(['code' => $act['code']], $act);
        }
    }
}