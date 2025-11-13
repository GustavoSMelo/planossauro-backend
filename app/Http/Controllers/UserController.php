<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Resend\Laravel\Facades\Resend;

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
                'cellphone_number' => ['required', 'string', 'max:15', 'min:11'],
                'github_email' => ['nullable', 'string', 'email'],
                'github_id' => ['nullable', 'integer'],
                'google_email' => ['nullable', 'string', 'email'],
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
             * @var integer | null
             */
            $github_id = $request->input('github_id');

            /**
             * @var string
             */
            $full_name = $request->input('full_name');

            /**
             * @var string
             */
            $cellphone_number = $request->input('cellphone_number');

            if ((empty($github_email) || strlen($github_email) <= 0) && (empty($google_email) || strlen($google_email) <= 0)) {
                return response()->json([
                    'error' => 'Github and Google email is null, please, provide at least one of them'
                ], 400);
            }

            if ((!empty($github_email) || strlen($github_email) > 0) && empty($github_id)) {
                return response()->json([
                    'error' => 'Github id not provided'
                ], 400);
            }

            $validationCode = rand(10000, 99999);

            $userCreated = User::create([
                'full_name' => $full_name,
                'google_email' => $google_email,
                'github_email' => $github_email,
                'github_id' => $github_id,
                'cellphone_number' => $cellphone_number,
                'validation_code' => $validationCode
            ]);

            if (!empty($github_email)) {
                Resend::emails()->send([
                    'from' => 'Acme <onboarding@resend.dev>',
                    'to' => $github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $validationCode])->render()
                ]);
            } else {
                Resend::emails()->send([
                    'from' => 'Acme <onboarding@resend.dev>',
                    'to' => $github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $validationCode])->render()
                ]);
            }

            return response()->json([
                'message' => 'user created with success',
                'data' => $userCreated,
                'validation_code' => $validationCode
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Malformated or missing values', 'data' => $err], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid): JsonResponse | User
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

            return response()->json([
                'message' => 'User updated with success',
                'user' => $userFinded
            ], 200);
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

    public function findByGithubEmail(string $githubEmail)
    {
        try {
            return User::query()->where('github_email', '=', $githubEmail)->first();
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded'], 400);
        }
    }

    public function resendEmail(Request $request)
    {
        try {
            $request->validate([
                'uuid' => ['required', 'uuid', 'string'],
                'loginType' => ['required', 'string']
            ]);

            $user = User::query()->where('uuid', '=', $request->input('uuid'))->first();

            if ($request->input('loginType') === 'github') {
                Resend::emails()->send([
                    'from' => 'Acme <onboarding@resend.dev>',
                    'to' => $user->github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $user->validation_code])->render()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded', 'errorData' => $e], 400);
        }
    }
}
