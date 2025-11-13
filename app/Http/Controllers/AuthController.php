<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function githubAuth(string $code)
    {
        try {
            $clientId = env('GITHUB_CLIENT_ID');
            $clientSecret = env('GITHUB_SECRET_ID');

            $accessResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->asJson()
                ->post("https://github.com/login/oauth/access_token", [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' => 'http://localhost:5173/callback/github',
                ]);

            $accessToken = $accessResponse->json('access_token');

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
}
