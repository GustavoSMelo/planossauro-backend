<?php

namespace App\Http\Controllers;

use App\Models\SupportEmails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Resend\Laravel\Facades\Resend;

class SupportEmailsController extends Controller
{
    public function createAndSendEmail(string $userUUID, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string'],
            'ticketId' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
        ]);

        if ($validator->failed()) {
            Log::info($validator->errors());
            Log::info($request->all());

            return response()->json([
                'message' => 'Error on validation',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::query()->where('uuid', '=', $userUUID)->first();

        if (!$user) return response()->json(['error' => 'user not found']);

        $title = $request->input('title');
        $description = $request->input('description');
        $category = $request->input('category');
        $ticketId = $request->input('ticketId');

        SupportEmails::create([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'ticketId' => $ticketId,
            'user_id' => $userUUID
        ]);

        try {
            $attachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = [
                        'content' => base64_encode($file->get()),
                        'filename' => $file->getClientOriginalName()
                    ];
                }
            }

            return Resend::emails()->send([
                'from' => config('services.resend.name') . '<' . config('services.resend.mail') . '>',
                'to' => 'planeja.ai.app@gmail.com',
                'subject' => 'Planeja.ai - Support',
                'attachments' => $attachments,
                'html' => view('mail.support', [
                    'userUUID' => $userUUID,
                    'title' => $title,
                    'category' => $category,
                    'ticketId' => $ticketId,
                    'description' => $description,
                ])->render(),
            ]);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
