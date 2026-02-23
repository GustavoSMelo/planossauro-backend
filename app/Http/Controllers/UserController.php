<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Resend\Laravel\Facades\Resend;
use Stripe\StripeClient;

class UserController extends Controller
{
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
             * @var string | null
             */
            $google_id = (string) $request->input('google_id');

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

            if ((!empty($google_email) || strlen($google_email) > 0) && empty($google_email)) {
                return response()->json([
                    'error' => 'Google id not provided'
                ], 400);
            }


            $githubValidationCode = rand(10000, 99999);
            $googleValidationCode = rand(10000, 99999);
            $smsValidationCode = rand(10000, 99999);

            $userFinded = User::query()
                ->where(function ($query) use ($github_email, $google_email) {
                    if ($github_email) $query
                        ->where('github', '=', null)
                        ->where('google_email', '=', $github_email);
                    if ($google_email) $query
                        ->where('google_email', '=', null)
                        ->where('github_email', '=', $google_email);
                })
                ->first();

            Log::info('User info: ' . $userFinded);

            if ($userFinded && $userFinded !== null) {
                $userFinded->full_name = $full_name;
                if ($github_email && $userFinded->github_email === null) $userFinded->github_email = $github_email;
                else if ($google_email && $userFinded->google_email === null) $userFinded->google_email = $google_email;
                $userFinded->github_id = $github_id;
                $userFinded->cellphone_number = $cellphone_number;
                $userFinded->github_validation_code = $githubValidationCode;
                $userFinded->google_validation_code = $googleValidationCode;
                $userFinded->sms_validation_code = $smsValidationCode;

                $userFinded->save();
            } else {
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
            }

            try {
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
            } catch (\Exception $err) {
                Log::error([
                    'message' => 'Error to send email to user',
                    'error' => $err
                ]);
            }

            if ($userFinded) {
                return response()->json([
                    'message' => 'user updated with success',
                    'data' => $userFinded,
                ], 200);
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

            $validate = Validator::make($request->all(), [
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

            if ($validate->failed()) {
                return response()->json([
                    'Message' => 'validation failed',
                    'Errors' => $validate->errors()
                ], 400);
            }

            $userFinded->full_name = $request->input('full_name');
            $userFinded->github_email = $request->input('github_email');
            $userFinded->google_email = $request->input('google_email');
            $userFinded->cellphone_number = $request->input('cellphone_number');
            $userFinded->github_id = $request->input('github_id');
            $userFinded->google_id = (string) $request->input('google_id');
            $userFinded->github_validation_code = rand(10000, 99999);
            $userFinded->google_validation_code = rand(10000, 99999);
            $userFinded->github_is_validated = $userFinded->github_email === $request->input('github_email') && $userFinded->github_is_validated ? true : false;
            $userFinded->google_is_validated = $userFinded->google_email === $request->input('google_email') && $userFinded->google_is_validated ? true : false;

            $userFinded->save();

            try {
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
            } catch (\Exception $err) {
                Log::error([
                    'message' => 'Error to send email to user',
                    'error' => $err
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
            $user = User::query()->where('uuid', '=', $userUUID)->first();

            if (!$user || $user === null) return response()->json([
                'error' => 'User with this uuid was not founded'
            ], 404);

            if (!$user->github_is_validated && !$user->google_is_validated) return response()->json([
                'error' => 'To delete this account, you first need to have a google or github account validated'
            ], 401);

            $user->deleted_at = date('Y-m-d');
            $user->save();

            $subscription = Subscription::query()->where('user_id', '=', $user->uuid)->first();

            if ($subscription->stripe_subscription) {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $stripe->subscriptions->cancel($subscription->stripe_subscription);

                $subscription->stripe_price = null;
                $subscription->stripe_product = null;
                $subscription->stripe_subscription = null;
                $subscription->stripe_user = null;

                $subscription->save();
            }

            return response()->json(['message' => 'User soft deleted with success']);
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
            $validator = Validator::make($request->all(), [
                'uuid' => ['required', 'uuid', 'string'],
                'loginType' => ['required', 'string']
            ]);

            if ($validator->failed()) {
                return response()->json([
                    'Message' => 'validation failed',
                    'Errors' => $validator->errors()
                ], 400);
            }

            $googleCode = rand(10000, 99999);
            $githubCode = rand(10000, 99999);

            $user = User::query()->where('uuid', '=', $request->input('uuid'))->first();
            $user->github_validation_code = $githubCode;
            $user->google_validation_code = $googleCode;
            $user->save();

            if ($request->input('loginType') === 'github') {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $githubCode,
                    'subject' => 'Planeja.ai - Validation Code',
                    'html' => view('mail.validation-mail', ['validation_code' => $user->github_validation_code])->render()
                ]);
            } else {
                Resend::emails()->send([
                    'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                    'to' => $googleCode,
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

            return response()->json([
                'message' => 'github validated with success'
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

            return response()->json([
                'message' => 'gmail validated with success'
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

    public function removeSoftDeleteUser(string $userUUID)
    {
        try {
            $user = User::query()->where('uuid', '=', $userUUID)->first();

            if (!$user || $user === null) return response()->json([
                'message' => 'user with this uuid was not founded'
            ], 400);

            $user->deleted_at = null;
            $user->save();

            return response()->json([
                'message' => 'User restored with success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error in remove soft delete on user',
                'error' => $e
            ], 400);
        }
    }

    public function unlinkAccounts(string $userUUID, Request $request)
    {
        $unlink = $request->input('unlink');

        if (!$unlink && $unlink !== 'google' && $unlink !== 'github') return response()->json([
            'error' => 'unlink not allowed'
        ], 400);

        $user = User::query()->where('uuid', '=', $userUUID)->first();

        if ($unlink === 'google' && !$user->github_email) return response()->json([
            'error' => 'unlink not allowed, should have at last two account on same profile'
        ], 400);

        if ($unlink === 'github' && !$user->google_email) return response()->json([
            'error' => 'unlink not allowed, should have at last two account on same profile'
        ], 400);

        if ($unlink === 'github') {
            $user->github_email = null;
            $user->github_id = null;
            $user->github_is_validated = false;
            $user->github_validation_code = rand(10000, 99999);

            $user->save();
            return response()->json([
                'message' => 'Github unlinked with success'
            ]);
        }

        $user->google_email = null;
        $user->google_id = null;
        $user->google_is_validated = false;
        $user->google_validation_code = rand(10000, 99999);

        $user->save();
        return response()->json([
            'message' => 'Google unlinked with success'
        ]);
    }
}
