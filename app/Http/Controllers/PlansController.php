<?php

namespace App\Http\Controllers;

use App\Models\Plans;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $plans = Plans::all();

            return response()->json([
                'plans' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error to receive plans from database',
                'exception' => $e
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            return Plans::query()->where('uuid', '=', $uuid)->first();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'a plan with this uuid was not found',
                'exception' => $e
            ]);
        }
    }
}
