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

            /**
             * @var mixed
             */
            $accessResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->asJson()
                ->post("https://github.com/login/oauth/access_token", [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' =>  config('app.frontend_url') . '/callback/github',
                ]);
            $accessToken = $accessResponse->json('access_token');

            /**
             * @var mixed
             */
            $response = Http::withHeader('Authorization', "Bearer $accessToken")->get('https://api.github.com/user');

            return response()->json([
                "data" => $response->json(),
                "accessToken" => $accessResponse->json('access_token')
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
            'message' => 'Auth granted'
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
            $subId = (int) json_decode($response->body())->sub;

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
                'message' => 'Auth granted'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Unauthenticated',
                'error' => $e
            ], 401);
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
