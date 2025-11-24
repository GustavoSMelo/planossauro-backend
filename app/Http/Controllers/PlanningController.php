<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return Planning::all();
        } catch (\Exception $e) {
            return response()->json(['Error to receive values from database', 400]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'document_b64' => ['required', 'string'],
                'start_plan' => ['required', 'date'],
                'end_plan' => ['required', 'date', 'after_or_equal:start_plan'],
                'school_name' => ['required', 'string'],
                'class_name' => ['required', 'string']
            ]);

            $documentb64 = $request->input('document_b64');
            $start_plan = $request->input('start_plan');
            $end_plan = $request->input('end_plan');
            $school_name = $request->input('school_name');
            $class_name = $request->input('class_name');

            $planning = Planning::create([
                'document_b64' => $documentb64,
                'class_name' => $class_name,
                'end_plan' => $end_plan,
                'school_name' => $school_name,
                'start_plan' => $start_plan
            ]);

            return response()->json(['message' => 'Planning created with success!', 'data' => $planning]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'malformated planning', 'error' => $e, 400]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(['error' => 'uuid provided was not valid'], 400);
            }

            return Planning::query()->where('uuid', '=', $uuid)->first();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Planning not founded'], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $request->validate([
                'document_b64' => ['required', 'string'],
                'start_plan' => ['required', 'date'],
                'end_plan' => ['required', 'date', 'after:start_plan'],
                'school_name' => ['required', 'string'],
                'class_name' => ['required', 'string']
            ]);

            $documentb64 = $request->input('document_b64');
            $start_plan = $request->input('start_plan');
            $end_plan = $request->input('end_plan');
            $school_name = $request->input('school_name');
            $class_name = $request->input('class_name');


            $planningFinded = Planning::query()->where('uuid', '=', $uuid)->first();

            $planningFinded->update([
                'document_b64' => $documentb64,
                'class_name' => $class_name,
                'end_plan' => $end_plan,
                'school_name' => $school_name,
                'start_plan' => $start_plan
            ]);

            return response()->json([
                'message' => 'Planning updated with success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Planning malformated or not founded id'
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            if (empty($uuid) || strlen($uuid) <= 3 || $uuid === null) {
                return response()->json(['error' => 'uuid provided was not valid'], 400);
            }

            $planningFinded = Planning::query()->where('uuid', '=', $uuid)->first();
            $planningFinded->delete();

            return response()->json(['message' => 'Planning deleted with success']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Planning was not founded'], 400);
        }
    }
}
