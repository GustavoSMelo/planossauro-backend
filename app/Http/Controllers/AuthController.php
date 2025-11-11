<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function githubAuth(string $code)
    {
        $clientId = env('GITHUB_CLIENT_ID');
        $clientSecret = env('GITHUB_SECRET_ID');

        $guzzleClient = new \GuzzleHttp\Client();

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])
        ->asJson()
        ->post("https://github.com/login/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'callbackURL' => 'http://localhost:5173/callback/github',
        ]);

        return response()->json([$response]);
    }
}
