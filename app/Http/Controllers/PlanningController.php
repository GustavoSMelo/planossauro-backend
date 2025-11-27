<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class PlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param string $user_id
     */
    public function index(string $user_id)
    {
        try {
            return Planning::query()
                ->orderBy('updated_at', 'desc')
                ->where('user_id', '=', $user_id)
                ->paginate(5, '*');
        } catch (\Exception $e) {
            return response()->json(['Error to receive values from database', 400]);
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
            return response()->json(['message' => 'Planning not founded', 'error' => $e], 400);
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
                'class_name' => ['required', 'string'],
                'user_id' => ['required', 'string', 'uuid']
            ]);

            $user_id = $request->input('user_id');

            $user = User::find($user_id);

            if (!$user || empty($user) || is_null($user)) {
                return response()->json(['error' => 'UUID provided is not associated with any user'], 400);
            }

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
                'start_plan' => $start_plan,
                'user_id' => $user_id
            ]);

            return response()->json(['message' => 'Planning created with success!', 'data' => $planning]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'malformated planning', 'error' => $e, 400]);
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
                'class_name' => ['required', 'string'],
                'user_id' => ['required', 'string', 'uuid']
            ]);

            $user_id = $request->input('user_id');

            $user = User::find($user_id);

            if (!$user || empty($user) || is_null($user)) {
                return response()->json(['error' => 'UUID provided is not associated with any user'], 400);
            }

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

    public function archive(string $uuid)
    {
        try {
            $planning = Planning::query()->where('uuid', '=', $uuid)->first();

            $planning->update([
                'deleted_at' => Date::now()->year . '-' . Date::now()->month . '-' . Date::now()->day
            ]);

            return response()->json([
                'message' => 'Planning archived with success'
            ]);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Planning not founded', 'error' => $err], 400);
        }
    }

    public function unarchive(string $uuid)
    {
        try {
            $planning = Planning::query()->where('uuid', '=', $uuid)->first();

            $planning->update([
                'deleted_at' => null
            ]);

            return response()->json([
                'message' => 'Planning unarchived with success'
            ]);
        } catch (\Exception $err) {
            return response()->json(['message' => 'Planning not founded', 'error' => $err], 400);
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
