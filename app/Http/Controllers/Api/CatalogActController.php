<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogAct;
use Illuminate\Http\Request;

class CatalogActController extends Controller
{
    public function index()
    {
        $acts = CatalogAct::orderBy('code')
            ->withCount('consultationActs')
            ->get()
            ->map(fn($a) => array_merge($a->toArray(), ['in_use' => $a->consultation_acts_count > 0]));

        return response()->json(['acts' => $acts]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => 'required|string|max:20|unique:catalog_acts,code',
            'name'       => 'required|string|max:100',
            'base_price' => 'required|numeric|min:0',
            'is_active'  => 'boolean',
        ]);

        $act = CatalogAct::create($data);

        return response()->json(['act' => array_merge($act->toArray(), ['in_use' => false])], 201);
    }

    public function update(Request $request, CatalogAct $catalogAct)
    {
        $data = $request->validate([
            'code'       => 'sometimes|string|max:20|unique:catalog_acts,code,' . $catalogAct->id,
            'name'       => 'sometimes|string|max:100',
            'base_price' => 'sometimes|numeric|min:0',
            'is_active'  => 'sometimes|boolean',
        ]);

        $catalogAct->update($data);
        $fresh = $catalogAct->fresh()->loadCount('consultationActs');

        return response()->json(['act' => array_merge($fresh->toArray(), ['in_use' => $fresh->consultation_acts_count > 0])]);
    }

    public function destroy(CatalogAct $catalogAct)
    {
        if ($catalogAct->consultationActs()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cet acte : il est utilisé dans des consultations.',
            ], 422);
        }

        $catalogAct->delete();
        return response()->json(['success' => true]);
    }
}
