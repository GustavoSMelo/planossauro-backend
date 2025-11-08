<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'full_name' => ['required', 'string', 'max:255', 'min:3'],
                'cellphone' => ['required', 'string', 'max:14', 'min:11'],
                'github_email' => ['nullable', 'string', 'email'],
                'google_email' => ['nullable', 'string', 'email']
            ]);

            /**
             * @var string | null
             */
            $google_email = $request->input('google_email');

            /**
             * @var string | null
             */
            $github_email = $request->input('github_email');

            /**
             * @var string
             */
            $full_name = $request->input('full_name');

            /**
             * @var string
             */
            $cellphone_number = $request->input('cellphone_number');

            $userCreated = User::create([
                'full_name' => $full_name,
                'google_email' => $google_email,
                'github_email' => $github_email,
                'cellphone_number' => $cellphone_number
            ]);

            return response()->json([
                'message' => 'user created with success',
                'data' => $userCreated
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Malformated values'], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            return User::query()->where('uuid', '=', $uuid)->first();
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded'], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        try {
            $userFinded = User::query()->where('uuid', '=', $uuid)->first();

            if (empty($userFinded) || $userFinded === null) {
                return response()->json('User not founded', 400);
            }

            $request->validate([
                'full_name' => ['required', 'string', 'max:255', 'min:3'],
                'cellphone' => ['required', 'string', 'max:14', 'min:11'],
                'github_email' => ['nullable', 'string', 'email'],
                'google_email' => ['nullable', 'string', 'email']
            ]);

            $userFinded->update([
                'full_name' => $request->full_name,
                'github_email' => $request->github_email,
                'google_email' => $request->google_email,
                'cellphone_number' => $request->cellphone_number,
            ]);

            return response()->json(['message' => 'User updated with success'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            User::query()->delete($uuid);

            return response()->json(['message' => 'User deleted with success'], 400);
        } catch (\Exception $e) {
            return response(['error' => 'User not founded'], 400);
        }
    }
}
