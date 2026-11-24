<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AnthropicService
{
    public function extractTasks(string $text): array
    {
        $system = 'Je haalt schooltaken uit tekst. Antwoord uitsluitend met geldige JSON, zonder uitleg of markdown: {"tasks":[{"title":string,"course":string|null,"deadline":"YYYY-MM-DD"|null,"estimated_hours":number}]}. Vandaag is ' . now()->toDateString() . '. Verzin geen datum: als die niet duidelijk is, gebruik null. Schat estimated_hours realistisch voor een bachelorstudent.';

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-5-5',
            'max_tokens' => 1000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $text]],
        ]);

        if ($response->failed()) {
            return ['error' => 'De API-aanroep is mislukt (HTTP ' . $response->status() . ').', 'raw' => $response->body()];
        }

        $body = (string) $response->json('content.0.text', '');

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['tasks']) || !is_array($data['tasks'])) {
            return ['error' => 'Het antwoord van de AI kon niet als JSON worden gelezen.', 'raw' => $body];
        }

        $tasks = [];
        foreach ($data['tasks'] as $t) {
            if (!is_array($t)) {
                continue;
            }
            $tasks[] = [
                'title' => is_string($t['title'] ?? null) && trim($t['title']) !== '' ? trim($t['title']) : 'Zonder titel',
                'course' => is_string($t['course'] ?? null) && trim($t['course']) !== '' ? trim($t['course']) : null,
                'deadline' => $this->validDate($t['deadline'] ?? null),
                'estimated_hours' => is_numeric($t['estimated_hours'] ?? null) ? max(0.5, (float) $t['estimated_hours']) : 2.0,
            ];
        }

        return ['tasks' => $tasks];
    }

    private function validDate(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = explode('-', $value);
        if (!checkdate((int) $m, (int) $d, (int) $y)) {
            return null;
        }
        return $value;
    }
}
