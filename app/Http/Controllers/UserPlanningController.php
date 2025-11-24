<?php

namespace App\Http\Controllers;

use App\Models\UserPlanning;
use Illuminate\Http\Request;

class UserPlanningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $userUuid)
    {
        try {
            return UserPlanning::query()->where('user_id', '=', $userUuid)->get();
        } catch (\Exception $e) {
            return response()->json(['Error' => $e], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => ['required', 'string', 'uuid'],
                'planning_id' => ['required', 'string', 'uuid']
            ]);

            $userCreated = UserPlanning::create([
                'user_id' => $request->input('user_id'),
                'planning_id' => $request->input('planning_id'),
            ]);

            return response()->json([
                'message' => 'relationship created with success',
                'relationship' => $userCreated
            ]);
        } catch (\Exception $e) {
            return response()->json(['Error' => $e], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            UserPlanning::delete($uuid);

            return response()->json([
                'message' => 'relationship deleted with success !'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'Error' => $e
            ]);
        }
    }
}
