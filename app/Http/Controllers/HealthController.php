<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function check()
    {
        $pdo = DB::connection()->getPdo();
        $githubResponse = Http::get('https://api.github.com');

        return response()->json([
            'api' => [
                "status" => 200,
                "message" => 'ok',
            ],
            'database' => [
                "status" => $pdo ? 200 : 500,
                "message" => $pdo ? 'ok' : 'Error in database, connection not running'
            ],
            'github_api' => [
                'status' => $githubResponse->status(),
                'message' => $githubResponse->body() && $githubResponse->status() === 200 ? 'ok' : 'Github API is offline'
            ],
        ]);
    }
}
