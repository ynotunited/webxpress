<?php

namespace App\Http\Controllers;

use App\Jobs\SendAiFollowUp;
use App\Mail\InquiryConfirmation;
use App\Mail\ProjectInquiryReceived;
use App\Models\ProjectInquiry;
use App\Services\InquiryThreadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'message' => 'required|string|max:5000',
            'project_type' => 'nullable|string|max:100',
            'budget' => 'nullable|string|max:100',
            'timeline' => 'nullable|string|max:100',
        ]);

        $inquiry = ProjectInquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
            'project_type' => $data['project_type'] ?? null,
            'budget' => $data['budget'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'thread_token' => Str::random(8),
        ]);

        $thread = app(InquiryThreadService::class);
        $messageId = $thread->generateMessageId($inquiry);

        $thread->storeOutbound(
            $inquiry,
            'confirmation',
            "Received — project brief #{$inquiry->id} [WX-{$inquiry->thread_token}]",
            "Automatic confirmation sent to {$inquiry->email}.\n\nSubject: Your project brief — received",
            $messageId,
        );

        Mail::to($inquiry->email)
            ->send(new InquiryConfirmation($inquiry, $messageId));

        Mail::to(config('mail.inquiries_to'))
            ->send(new ProjectInquiryReceived($inquiry));

        SendAiFollowUp::dispatch($inquiry);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('success', 'Thanks. Your project details have been received. We\'ll be in touch.');
    }
}
