<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function githubAccessToken(string $code)
    {
        try {
            $clientId = env('GITHUB_CLIENT_ID');
            $clientSecret = env('GITHUB_SECRET_ID');

            $accessResponse = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post("https://github.com/login/oauth/access_token", [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' => config('app.frontend_url') . '/callback/github',
                ]);

            $accessToken = $accessResponse->json('access_token');

            if (!$accessToken) {
                Log::error('GitHub token exchange failed', [
                    'status' => $accessResponse->status(),
                    'body' => $accessResponse->body(),
                ]);

                return response()->json([
                    'Error' => 'Failed to obtain access token from GitHub',
                    'details' => $accessResponse->json(),
                ], 400);
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer $accessToken",
            ])->get('https://api.github.com/user');

            return response()->json([
                "data" => $response->json(),
                "accessToken" => $accessToken,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'Error' => 'Github code provided is invalid',
                'ErrorData' => $e
            ], 400);
        }
    }

    public function githubAuth(String $token)
    {
        /**
         * @var mixed
         */
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get('https://api.github.com/user');

        $email = json_decode($response->body())->email;
        $id = json_decode($response->body())->id;

        $user = User::query()
            ->where('github_email', '=', $email)
            ->where('github_id', '=', $id)
            ->first();

        if (!$user || empty($user)) {
            return response()->json([
                'message' => 'User not founded',
                'error' => 'Unauthenticated'
            ], 401);
        }

        $user->tokens()->delete();
        $sactumToken = $user->createToken('auth');

        return response()->json([
            'token' => $sactumToken,
            'type' => 'Bearer',
            'message' => 'Auth granted',
            'user' => $user
        ]);
    }

    public function googleAuth(String $token)
    {
        try {
            /**
             * @var mixed
             */
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get('https://openidconnect.googleapis.com/v1/userinfo');

            Log::info('resposta ' . $response->body());

            $email = json_decode($response->body())->email;
            $subId = (string) json_decode(
                $response->body(),
                false,
                512,
                JSON_BIGINT_AS_STRING
            )->sub;

            $user = User::query()
                ->where('google_email', '=', $email)
                ->first();

            Log::info('user finded: ' . $user);
            Log::info('user finded: ' . $user->google_id);
            Log::info('tokn finded: ' . $subId);

            if (!$user || empty($user) || $user === null) {
                return response()->json([
                    'message' => 'User not founded',
                    'error' => 'Unauthenticated'
                ], 401);
            }

            if ($user->google_id !== $subId || $user['google_id'] !== $subId) return response()->json([
                'message' => 'Google id is invalid',
                'error' => 'Unauthenticated'
            ], 401);

            Log::info('Usuario: ' . $user);

            $user->tokens()->delete();
            $sactumToken = $user->createToken('auth');

            return response()->json([
                'token' => $sactumToken,
                'type' => 'Bearer',
                'message' => 'Auth granted',
                'user' => $user
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Unauthenticated',
                'error' => $e
            ], 401);
        }
    }

    public function facebookAccessToken(String $code)
    {
        try {
            $clientId = env('FACEBOOK_CLIENT_ID');
            $clientSecret = env('FACEBOOK_CLIENT_SECRET');
            $frontendURL = env('FRONTEND_URL');

            $response = Http::withQueryParameters([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $frontendURL . '/callback/facebook'
            ])->get('https://graph.facebook.com/v25.0/oauth/access_token');

            $accessToken = json_decode($response->body())->access_token;

            return response()->json([
                'access_token' => $accessToken,
                'json' => $response->json()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e
            ], 401);
        }
    }

    public function facebookAuth(String $token)
    {
        try {
            $response = Http::withQueryParameters([
                'access_token' => $token,
                'fields' => 'id,name,email'
            ])->get('https://graph.facebook.com/v25.0/me');

            $id = json_decode($response->body())->id;
            $email = json_decode($response->body())->email;
            $name = json_decode($response->body())->name;

            Log::info($response->body());

            $user = User::query()
                ->where('facebook_email', '=', $email)
                ->where('facebook_id', '=', $id)
                ->first();

            if (!$user || empty($user) || $user === null) {
                return response()->json([
                    'message' => 'User not found',
                    'token' => [
                        'plainTextToken' => ''
                    ],
                    'email' => $email,
                    'id' => $id,
                    'name' => $name
                ]);
            }

            if ($user->facebook_email !== $email) {
                return response()->json([
                    'Error' => 'user email is invalid'
                ], 401);
            }

            $token = $user->createToken('auth');

            return response()->json([
                'token' => $token,
                'type' => 'Bearer',
                'message' => 'Auth granted',
                'email' => $email,
                'id' => $id,
                'name' => $name,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'Error' => $e
            ]);
        }
    }

    public function logout(String $userUUID)
    {
        try {
            $user = User::query()->where('uuid', '=', $userUUID)->first();

            $user->tokens()->delete();
            return response()->json([
                'message' => 'User logout with success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error',
                'error' => $e
            ], 401);
        }
    }
}
