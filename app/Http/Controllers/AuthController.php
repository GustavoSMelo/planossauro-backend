<?php

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Models\PlanningHour;
use App\Models\Plans;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Resend\Laravel\Facades\Resend;

class AuthController extends Controller
{
    public function githubAccessToken(string $code)
    {
        try {
            $clientId = env("GITHUB_CLIENT_ID");
            $clientSecret = env("GITHUB_SECRET_ID");

            $accessResponse = Http::asForm()
                ->withHeaders([
                    "Accept" => "application/json",
                ])
                ->post("https://github.com/login/oauth/access_token", [
                    "client_id" => $clientId,
                    "client_secret" => $clientSecret,
                    "code" => $code,
                    "redirect_uri" =>
                        config("app.frontend_url") . "/callback/github",
                ]);

            $accessToken = $accessResponse->json("access_token");

            if (!$accessToken) {
                Log::error("GitHub token exchange failed", [
                    "status" => $accessResponse->status(),
                    "body" => $accessResponse->body(),
                ]);

                return response()->json(
                    [
                        "Error" => "Failed to obtain access token from GitHub",
                        "details" => $accessResponse->json(),
                    ],
                    400,
                );
            }

            $response = Http::withHeaders([
                "Authorization" => "Bearer $accessToken",
            ])->get("https://api.github.com/user");

            return response()->json([
                "data" => $response->json(),
                "accessToken" => $accessToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "Error" => "Github code provided is invalid",
                    "ErrorData" => $e,
                ],
                400,
            );
        }
    }

    public function githubAuth(string $token)
    {
        /**
         * @var mixed
         */
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $token,
        ])->get("https://api.github.com/user");

        Log::info($response);

        $email = json_decode($response->body())->email;
        $id = json_decode($response->body())->id;

        $user = User::query()
            ->where("github_email", "=", $email)
            ->where("github_id", "=", $id)
            ->first();

        if (!$user || empty($user)) {
            return response()->json(
                [
                    "message" => "User not founded",
                    "error" => "Unauthenticated",
                ],
                401,
            );
        }

        $user->tokens()->delete();
        $sactumToken = $user->createToken("auth");

        return response()->json([
            "token" => $sactumToken,
            "type" => "Bearer",
            "message" => "Auth granted",
            "user" => $user,
        ]);
    }

    public function googleAuth(string $token)
    {
        try {
            /**
             * @var mixed
             */
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $token,
            ])->get("https://openidconnect.googleapis.com/v1/userinfo");

            Log::info("resposta " . $response->body());

            $email = json_decode($response->body())->email;
            $subId = (string) json_decode(
                $response->body(),
                false,
                512,
                JSON_BIGINT_AS_STRING,
            )->sub;

            $user = User::query()
                ->where("google_email", "=", $email)
                ->first();

            Log::info("user finded: " . $user);
            Log::info("user finded: " . $user->google_id);
            Log::info("tokn finded: " . $subId);

            if (!$user || empty($user) || $user === null) {
                return response()->json(
                    [
                        "message" => "User not founded",
                        "error" => "Unauthenticated",
                    ],
                    401,
                );
            }

            if ($user->google_id !== $subId || $user["google_id"] !== $subId) {
                return response()->json(
                    [
                        "message" => "Google id is invalid",
                        "error" => "Unauthenticated",
                    ],
                    401,
                );
            }

            Log::info("Usuario: " . $user);

            $user->tokens()->delete();
            $sactumToken = $user->createToken("auth");

            return response()->json([
                "token" => $sactumToken,
                "type" => "Bearer",
                "message" => "Auth granted",
                "user" => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "Unauthenticated",
                    "error" => $e,
                ],
                401,
            );
        }
    }

    public function facebookAccessToken(string $code)
    {
        try {
            $clientId = env("FACEBOOK_CLIENT_ID");
            $clientSecret = env("FACEBOOK_CLIENT_SECRET");
            $frontendURL = env("FRONTEND_URL");

            $response = Http::withQueryParameters([
                "client_id" => $clientId,
                "client_secret" => $clientSecret,
                "code" => $code,
                "redirect_uri" => $frontendURL . "/callback/facebook",
            ])->get("https://graph.facebook.com/v25.0/oauth/access_token");

            $accessToken = json_decode($response->body())->access_token;

            return response()->json([
                "access_token" => $accessToken,
                "json" => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "Error",
                    "error" => $e,
                ],
                401,
            );
        }
    }

    public function facebookAuth(string $token)
    {
        try {
            $response = Http::withQueryParameters([
                "access_token" => $token,
                "fields" => "id,name,email",
            ])->get("https://graph.facebook.com/v25.0/me");

            $id = json_decode($response->body())->id;
            $email = json_decode($response->body())->email;
            $name = json_decode($response->body())->name;

            Log::info($response->body());

            $user = User::query()
                ->where("facebook_email", "=", $email)
                ->where("facebook_id", "=", $id)
                ->first();

            if (!$user || empty($user) || $user === null) {
                return response()->json([
                    "message" => "User not found",
                    "token" => [
                        "plainTextToken" => "",
                    ],
                    "email" => $email,
                    "id" => $id,
                    "name" => $name,
                ]);
            }

            if ($user->facebook_email !== $email) {
                return response()->json(
                    [
                        "Error" => "user email is invalid",
                    ],
                    401,
                );
            }

            $token = $user->createToken("auth");

            return response()->json([
                "token" => $token,
                "type" => "Bearer",
                "message" => "Auth granted",
                "email" => $email,
                "id" => $id,
                "name" => $name,
                "user" => $user,
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                "Error" => $e,
            ]);
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "full_name" => ["required", "string", "max:255", "min:3"],
                "email" => ["required", "string", "email", "max:255"],
                "cellphone_number" => ["required", "string", "max:15", "min:11"],
                "password" => ["required", "string", "min:8"],
                "initial_hour" => ["required", "string"],
                "interval_between_classes" => ["required", "string"],
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "message" => "Validation failed",
                        "errors" => $validator->errors(),
                    ],
                    400,
                );
            }

            $email = $request->input("email");
            $emailValidationCode = rand(10000, 99999);

            $existingUser = User::query()
                ->where("user_email", "=", $email)
                ->orWhere("github_email", "=", $email)
                ->orWhere("google_email", "=", $email)
                ->orWhere("facebook_email", "=", $email)
                ->first();

            if ($existingUser) {
                if ($existingUser->user_password) {
                    if (!Hash::check($request->input("password"), $existingUser->user_password)) {
                        return response()->json(
                            [
                                "message" => "Email already in use with a different password",
                                "error" => "Invalid credentials",
                            ],
                            401,
                        );
                    }
                }

                $existingUser->full_name = $request->input("full_name");
                $existingUser->user_email = $email;
                $existingUser->user_password = Hash::make($request->input("password"));
                $existingUser->cellphone_number = $request->input("cellphone_number");
                $existingUser->email_is_validated = false;
                $existingUser->email_validation_code = $emailValidationCode;
                $existingUser->save();

                $planningHour = PlanningHour::query()
                    ->where("user_id", "=", $existingUser->uuid)
                    ->first();

                if ($planningHour) {
                    $planningHour->initial_hour = $request->input("initial_hour");
                    $planningHour->interval_between_classes = $request->input("interval_between_classes");
                    $planningHour->save();
                } else {
                    PlanningHour::create([
                        "initial_hour" => $request->input("initial_hour"),
                        "interval_between_classes" => $request->input("interval_between_classes"),
                        "user_id" => $existingUser->uuid,
                    ]);
                }

                try {
                    Resend::emails()->send([
                        "from" => config("services.resend.name") . "<" . config("services.resend.mail") . ">",
                        "to" => $email,
                        "subject" => "Planossauro - Email Validation Code",
                        "html" => view("mail.validation-mail", [
                            "validation_code" => $emailValidationCode,
                        ])->render(),
                    ]);
                } catch (\Exception $err) {
                    Log::error([
                        "message" => "Error to send email to user",
                        "error" => $err,
                    ]);
                }

                $token = $existingUser->createToken("auth");

                return response()->json(
                    [
                        "message" => "User updated and registered with success",
                        "user" => $existingUser,
                        "token" => $token,
                        "type" => "Bearer",
                    ],
                    200,
                );
            }

            $user = User::create([
                "full_name" => $request->input("full_name"),
                "user_email" => $email,
                "user_password" => Hash::make($request->input("password")),
                "cellphone_number" => $request->input("cellphone_number"),
                "email_is_validated" => false,
                "email_validation_code" => $emailValidationCode,
                "sms_validation_code" => rand(10000, 99999),
                "google_validation_code" => rand(10000, 99999),
                "github_validation_code" => rand(10000, 99999),
                "facebook_validation_code" => rand(10000, 99999),
            ]);

            PlanningHour::create([
                "initial_hour" => $request->input("initial_hour"),
                "interval_between_classes" => $request->input("interval_between_classes"),
                "user_id" => $user->uuid,
            ]);

            $freePlan = Plans::query()->where("price", "=", 0)->first();
            if (config("app.env") === "local") {
                $freePlan = Plans::query()->where("plan_name", "=", "adm")->first();
            }

            if ($freePlan) {
                Subscription::create([
                    "daily_plans_used" => 0,
                    "weekly_plans_used" => 0,
                    "date_verified" => null,
                    "next_billing" => date("Y-m-d", strtotime("+1 month")),
                    "status" => PlanStatus::PAID->value,
                    "last_four_digits" => null,
                    "card_brand" => null,
                    "user_id" => $user->uuid,
                    "plans_id" => $freePlan->uuid,
                ]);
            }

            try {
                Resend::emails()->send([
                    "from" => config("services.resend.name") . "<" . config("services.resend.mail") . ">",
                    "to" => $email,
                    "subject" => "Planossauro - Email Validation Code",
                    "html" => view("mail.validation-mail", [
                        "validation_code" => $emailValidationCode,
                    ])->render(),
                ]);
            } catch (\Exception $err) {
                Log::error([
                    "message" => "Error to send email to user",
                    "error" => $err,
                ]);
            }

            $token = $user->createToken("auth");

            return response()->json(
                [
                    "message" => "User registered with success",
                    "user" => $user,
                    "token" => $token,
                    "type" => "Bearer",
                ],
                201,
            );
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    "message" => "Error on register",
                    "error" => $e,
                ],
                400,
            );
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => ["required", "string", "email"],
                "password" => ["required", "string"],
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "message" => "Validation failed",
                        "errors" => $validator->errors(),
                    ],
                    400,
                );
            }

            $user = User::query()
                ->where("user_email", "=", $request->input("email"))
                ->first();

            if (!$user || !Hash::check($request->input("password"), $user->user_password)) {
                return response()->json(
                    [
                        "message" => "Invalid credentials",
                        "error" => "Unauthenticated",
                    ],
                    401,
                );
            }

            $user->tokens()->delete();
            $token = $user->createToken("auth");

            return response()->json([
                "token" => $token,
                "type" => "Bearer",
                "message" => "Auth granted",
                "user" => $user,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    "message" => "Error on login",
                    "error" => $e,
                ],
                400,
            );
        }
    }

    public function logout(string $userUUID)
    {
        try {
            $user = User::query()->where("uuid", "=", $userUUID)->first();

            $user->tokens()->delete();
            return response()->json([
                "message" => "User logout with success",
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" => "Error",
                    "error" => $e,
                ],
                401,
            );
        }
    }
}
