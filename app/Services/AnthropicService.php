<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AnthropicService
{
    public function extractTasks(string $text): array
    {
        $config = config('services.anthropic');
        $key = $config['key'] ?? null;
        if (!$key) {
            return ['error' => 'Er is geen API-key ingesteld. Vul ANTHROPIC_API_KEY in het .env-bestand in.', 'raw' => ''];
        }

        $headers = ['anthropic-version' => '2023-06-01'];
        if (($config['auth'] ?? 'x-api-key') === 'bearer') {
            $headers['Authorization'] = 'Bearer ' . $key;
        } else {
            $headers['x-api-key'] = $key;
        }

        $system = 'Je haalt schooltaken uit tekst. Antwoord uitsluitend met geldige JSON, zonder uitleg of markdown: {"tasks":[{"title":string,"course":string|null,"deadline":"YYYY-MM-DD"|null,"estimated_hours":number}]}. Vandaag is ' . now()->toDateString() . '. Verzin geen datum: als die niet duidelijk is, gebruik null. Schat estimated_hours realistisch voor een bachelorstudent.';

        try {
            $response = Http::timeout(60)->withHeaders($headers)->post(rtrim($config['url'], '/') . '/v1/messages', [
                'model' => $config['model'],
                'max_tokens' => 4000,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $text]],
            ]);
        } catch (ConnectionException $e) {
            return ['error' => 'Geen verbinding met de AI-dienst. Controleer je internetverbinding.', 'raw' => $e->getMessage()];
        }

        if ($response->failed()) {
            $message = match ($response->status()) {
                401 => 'De API-key ontbreekt of is ongeldig.',
                402 => 'Het budget van deze API-key is op.',
                403 => 'Het model is onbekend. Controleer ANTHROPIC_MODEL in het .env-bestand.',
                404 => 'Het API-adres klopt niet. Controleer ANTHROPIC_BASE_URL in het .env-bestand (zonder /v1).',
                429 => 'Te veel aanvragen of tegoed op. Probeer het later opnieuw.',
                default => 'De API-aanroep is mislukt (HTTP ' . $response->status() . ').',
            };

            return ['error' => $message, 'raw' => $response->body()];
        }

        $body = trim(collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n"));

        if ($response->json('stop_reason') === 'max_tokens') {
            return ['error' => 'Het antwoord van de AI was te lang en werd afgebroken. Probeer een kortere tekst.', 'raw' => $body];
        }

        $start = strpos($body, '{');
        $end = strrpos($body, '}');
        $json = $start !== false && $end > $start ? substr($body, $start, $end - $start + 1) : $body;

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['tasks']) || !is_array($data['tasks'])) {
            return ['error' => 'Het antwoord van de AI kon niet als JSON worden gelezen.', 'raw' => $body !== '' ? $body : $response->body()];
        }

        $tasks = [];
        foreach ($data['tasks'] as $t) {
            if (!is_array($t)) {
                continue;
            }
            $tasks[] = [
                'title' => is_string($t['title'] ?? null) && trim($t['title']) !== '' ? trim($t['title']) : 'Zonder titel',
                'course' => is_string($t['course'] ?? null) && trim($t['course']) !== '' ? trim($t['course']) : null,
                'deadline' => self::validDate($t['deadline'] ?? null),
                'estimated_hours' => is_numeric($t['estimated_hours'] ?? null) ? max(0.5, round((float) $t['estimated_hours'] * 2) / 2) : 2.0,
            ];
        }

        return ['tasks' => $tasks];
    }

    public static function validDate(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }
}
