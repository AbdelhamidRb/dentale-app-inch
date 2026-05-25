<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogAct;
use Illuminate\Http\Request;

class CatalogActController extends Controller
{
    public function index()
    {
        return response()->json([
            'acts' => CatalogAct::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|max:20|unique:catalog_acts,code',
            'name'             => 'required|string|max:100',
            'base_price'       => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'is_active'        => 'boolean',
        ]);

        $act = CatalogAct::create($data);

        return response()->json(['act' => $act], 201);
    }

    public function update(Request $request, CatalogAct $catalogAct)
    {
        $data = $request->validate([
            'code'             => 'sometimes|string|max:20|unique:catalog_acts,code,' . $catalogAct->id,
            'name'             => 'sometimes|string|max:100',
            'base_price'       => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:5|max:480',
            'is_active'        => 'sometimes|boolean',
        ]);

        $catalogAct->update($data);

        return response()->json(['act' => $catalogAct->fresh()]);
    }

    public function destroy(CatalogAct $catalogAct)
    {
        $catalogAct->delete();
        return response()->json(['success' => true]);
    }
}
