<?php

namespace App\Services;

use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiReplyService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai.base_url', 'https://openrouter.ai/api'), '/');
        $this->apiKey = (string) config('services.ai.api_key', '');
        $this->model = (string) config('services.ai.model', 'anthropic/claude-3.5-haiku');
    }

    public function enabled(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Draft a reply from the perspective of the Webxpress team.
     */
    public function draft(ProjectInquiry $inquiry, array $thread = []): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $transcript = $this->buildTranscript($inquiry, $thread);

        try {
            $response = Http::timeout(45)
                ->withToken($this->apiKey)
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $transcript,
                        ],
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 700,
                ]);

            if (! $response->successful()) {
                Log::warning('AI draft failed', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $text = data_get($response->json(), 'choices.0.message.content');

            return is_string($text) ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('AI draft exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the client-facing response writer for Webxpress Technologies, a web design and development studio based in Lagos, Nigeria.

Write on behalf of the studio in a warm, professional, confident tone. Short and direct, NOT salesy. Nigerian professionals may write in friendly, casual-professional English — that is fine.

Rules:
- NEVER invent prices, timelines, features, or guarantees the client has not agreed to.
- If scope is unclear, ask one clarifying question instead of assuming.
- If the client asked something you can't answer (pricing tiers, exact dates), say the team will confirm in a call rather than guessing.
- Address the client by their first name.
- Keep it to 2-4 sentences unless the client asked a detailed question.
- End with a clear next step question or offer to schedule an intro call.
- Do not use emojis.
- Output only the email body text. No subject line, no greeting/sign-off from you — only the message body.
PROMPT;
    }

    protected function buildTranscript(ProjectInquiry $inquiry, array $thread): string
    {
        $lines = [];
        $lines[] = 'INQUIRY SUMMARY';
        $lines[] = "Name: {$inquiry->name}";
        $lines[] = "Email: {$inquiry->email}";
        $lines[] = 'Project type: '.($inquiry->project_type ?: 'Not specified');
        $lines[] = 'Budget: '.($inquiry->budget ?: 'Not specified');
        $lines[] = 'Timeline: '.($inquiry->timeline ?: 'Not specified');
        $lines[] = 'Original brief: '.$inquiry->message;
        $lines[] = '';

        if ($thread !== []) {
            $lines[] = 'CONVERSATION SO FAR (oldest to newest):';
            foreach ($thread as $entry) {
                $who = $entry['direction'] === InquiryMessage::DIR_INBOUND ? 'Client' : 'Team';
                $lines[] = sprintf('[%s] %s', $who, $entry['body']);
            }
            $lines[] = '';
        }

        $lines[] = 'Write the next reply to send to the client.';

        return implode("\n", $lines);
    }
}
