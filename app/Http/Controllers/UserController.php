<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
            $validator = Validator::make($request->all(), [
                'full_name' => ['required', 'string', 'max:255', 'min:3'],
                'cellphone_number' => ['required', 'string', 'max:15', 'min:11'],
                'github_email' => ['nullable', 'string', 'email', Rule::unique('user', 'github_email')],
                'github_id' => ['nullable', 'integer', Rule::unique('user', 'github_id')],
                'google_email' => ['nullable', 'string', 'email', Rule::unique('user', 'google_email')],
                'google_id' => ['nullable', 'string', Rule::unique('user', 'google_id')]
            ]);

            if ($validator->fails()) return response()->json([
                'message' => 'Error on validation code',
                'errors' => $validator->errors()
            ], '400');

            /**
             * @var string | null
             */
            $google_email = $request->input('google_email');

            /**
             * @var string | null
             */
            $github_email = $request->input('github_email');

            /**
             * @var string | null
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

            /**
             * @var integer | null
             */
            $google_id = $request->input('google_id');

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

            $githubValidationCode = rand(10000, 99999);
            $googleValidationCode = rand(10000, 99999);
            $smsValidationCode = rand(10000, 99999);

            $userCreated = User::create([
                'full_name' => $full_name,
                'google_email' => $google_email,
                'github_email' => $github_email,
                'github_id' => $github_id,
                'google_id' => $google_id,
                'cellphone_number' => $cellphone_number,
                'github_validation_code' => $githubValidationCode,
                'google_validation_code' => $googleValidationCode,
                'sms_validation_code' => $smsValidationCode
            ]);

            if (!empty($github_email)) {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $githubValidationCode])->render()
                ]);
            } else {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $google_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $googleValidationCode])->render()
                ]);
            }

            return response()->json([
                'message' => 'user created with success',
                'data' => $userCreated,
            ], 200);
        } catch (\Exception $err) {
            return response()->json(['error' => 'Malformated or missing values', 'data' => $err], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $userUUID): JsonResponse | User
    {
        try {
            return User::query()->where('uuid', '=', $userUUID)->first();
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded'], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $userUUID)
    {
        try {
            $userFinded = User::query()->where('uuid', '=', $userUUID)->first();

            if (empty($userFinded) || $userFinded === null) {
                return response()->json('User not founded', 400);
            }

            $request->validate([
                'full_name' => ['required', 'string', 'max:255', 'min:3'],
                'cellphone_number' => ['required', 'string', 'max:15', 'min:11'],
                'github_email' => [
                    'nullable',
                    'string',
                    'email',
                    Rule::unique('user', 'github_email')->ignore($userUUID, 'uuid')
                ],
                'google_email' => ['nullable', 'string', 'email', Rule::unique('user', 'google_email')->ignore($userUUID, 'uuid')],
                'github_id' => ['nullable', 'integer', Rule::unique('user', 'github_id')->ignore($userUUID, 'uuid')],
                'google_id' => ['nullable', 'string', Rule::unique('user', 'google_id')->ignore($userUUID, 'uuid')]
            ]);

            $userFinded->update([
                'full_name' => $request->input('full_name'),
                'github_email' => $request->input('github_email'),
                'google_email' => $request->input('google_email'),
                'cellphone_number' => $request->input('cellphone_number'),
                'github_id' => $request->input('github_id'),
                'google_id' => $request->input('google_id'),
                'github_validation_code' => rand(10000, 99999),
                'google_validation_code' => rand(10000, 99999),
                'github_is_validated' => $userFinded->github_email === $request->input('github_email') && $userFinded->github_is_validated ? true : false,
                'google_is_validated' => $userFinded->google_email === $request->input('google_email') && $userFinded->google_is_validated ? true : false
            ]);

            if (!empty($github_email)) {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $userFinded->github_validation_code])->render()
                ]);
            } else {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $request->google_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $userFinded->google_validation_code])->render()
                ]);
            }

            return response()->json([
                'message' => 'User updated with success',
                'user' => $userFinded
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User not founded', 'error' => $e], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $userUUID)
    {
        try {
            User::query()->delete($userUUID);

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
            return response()->json(['message' => 'User not founded', 'error' => $e], 400);
        }
    }


    public function findByGoogleEmail(string $googleEmail)
    {
        try {
            return User::query()->where('google_email', '=', $googleEmail)->first();
        } catch (\Exception $e) {
            return response()->json(['message' => 'User not founded', 'error' => $e], 400);
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
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $user->github_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $user->github_validation_code])->render()
                ]);
            } else {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $user->google_email,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $user->google_validation_code])->render()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded', 'errorData' => $e], 400);
        }
    }

    public function validateGithubEmail(string $userUUID)
    {
        try {
            $userFinded = User::query()->where('uuid', '=', $userUUID)->first();

            $userFinded->update([
                'github_is_validated' => true
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded', 'errorData' => $e], 400);
        }
    }

    public function validateGoogleEmail(string $userUUID)
    {
        try {
            $userFinded = User::query()->where('uuid', '=', $userUUID)->first();

            $userFinded->update([
                'google_is_validated' => true
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not founded', 'errorData' => $e], 400);
        }
    }

    public function validate(string $userUUID, Request $request)
    {
        try {
            $request->validate([
                'loginType' => ['required', 'string', 'in:google,github'],
                'validationCode' => ['required', 'string']
            ]);

            /**
             * @var 'github' | 'google'
             */
            $loginType = $request->input('loginType');

            /**
             * @var string
             */
            $validationCode = $request->input('validationCode');

            /**
             * @var User
             */
            $user = User::query()->where('uuid', '=', $userUUID)->first();

            if ($loginType === 'github') {
                if ((int) $validationCode == $user->github_validation_code) {
                    $this->validateGithubEmail($userUUID);
                    return response()->json(['message' => 'user validated with success'], 200);
                }
            } else {
                if ($validationCode == $user->google_validation_code) {
                    $this->validateGoogleEmail($userUUID);
                    return response()->json(['message' => 'user validated with success'], 200);
                }
            }

            return response()->json(['message' => 'validation code is invalid'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'missing values or user not found', 'error' => $e], 400);
        }
    }
}
