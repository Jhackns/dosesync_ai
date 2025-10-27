<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = trim((string) env('GEMINI_API_KEY', ''));
        // Modelo por defecto (preferir familia 2.x según disponibilidad actual)
        $this->model = env('GEMINI_MODEL', 'gemini-2.5-flash');
    }

    public function listModels()
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models?key={$this->apiKey}";

        try {
            $response = Http::timeout(60)->get($endpoint);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json([
                    'error' => 'Failed to retrieve models from Gemini API.',
                    'status' => $response->status(),
                    'body' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception while retrieving models from Gemini API.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function classifyText(string $prompt, string $textToClassify): string
    {
        $raw = $this->callGemini([$prompt, $textToClassify]);
        $classification = $this->cleanClassification($raw);
        return $classification;
    }

    public function generateNarrative(string $prompt, string $data): string
    {
        $raw = $this->callGemini([$prompt, $data]);
        return trim($raw);
    }

    private function callGemini(array $parts): string
    {
        if ($this->apiKey === '') {
            // Fallback si no hay API Key configurada
            return 'Otro';
        }
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => array_map(fn ($t) => ['text' => (string) $t], $parts),
                ],
            ],
        ];

        // Lista de modelos a intentar (el primero es el del .env si existe)
        $candidates = [];
        if (!empty($this->model)) {
            $candidates[] = $this->model;
        }
        // Fallbacks comunes
        foreach ([
            // Familia 2.x
            'gemini-2.5-flash',
            'gemini-2.5-flash-latest',
            'gemini-2.0-flash-latest',
            'gemini-2.0-pro-latest',
            // Familia 1.5 (por compatibilidad)
            'gemini-1.5-flash-latest',
            'gemini-1.5-pro-latest',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
            'gemini-pro',
        ] as $m) {
            if (!in_array($m, $candidates, true)) {
                $candidates[] = $m;
            }
        }

        foreach (['v1', 'v1beta'] as $apiVersion) {
            foreach ($candidates as $modelName) {
                $endpoint = sprintf(
                    'https://generativelanguage.googleapis.com/%s/models/%s:generateContent?key=%s',
                    $apiVersion,
                    $modelName,
                    $this->apiKey
                );
                try {
                    $resp = Http::timeout(60)->post($endpoint, $payload);
                    if (!$resp->successful()) {
                        Log::warning('Gemini API call failed', [
                            'version' => $apiVersion,
                            'model' => $modelName,
                            'status' => $resp->status(),
                            'body' => $resp->body(),
                        ]);
                        continue; // probar siguiente modelo/versión
                    }
                    $json = $resp->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($text === '') {
                        Log::warning('Gemini API returned empty text', [
                            'version' => $apiVersion,
                            'model' => $modelName,
                            'json' => $json,
                        ]);
                        continue;
                    }
                    return (string) $text;
                } catch (\Throwable $e) {
                    Log::error('Gemini API exception', [
                        'version' => $apiVersion,
                        'model' => $modelName,
                        'message' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return 'Otro';
    }

    private function cleanClassification(string $raw): string
    {
        $raw = strtolower(trim($raw));
        $map = [
            'olvido' => 'Olvido',
            'efecto secundario' => 'Efecto Secundario',
            'viaje' => 'Viaje',
            'ocupado' => 'Ocupado',
            'costo' => 'Costo',
            'otro' => 'Otro',
            // soporte en inglés básico
            'forget' => 'Olvido',
            'forgot' => 'Olvido',
            'side effect' => 'Efecto Secundario',
            'adverse effect' => 'Efecto Secundario',
            'travel' => 'Viaje',
            'busy' => 'Ocupado',
            'cost' => 'Costo',
            'expensive' => 'Costo',
            'other' => 'Otro',
        ];

        foreach ($map as $k => $v) {
            if (str_contains($raw, $k)) {
                return $v;
            }
        }
        return 'Otro';
    }
}
