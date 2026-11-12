<?php

namespace App\Services\Integrations;

use App\Contracts\AiWritingDetector;
use App\Contracts\PlagiarismChecker;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Adapter for a provider endpoint configured to accept the SIMPAN multipart contract. */
class ConfiguredDiagnosticProvider implements AiWritingDetector, PlagiarismChecker
{
    public function __construct(private readonly string $endpointConfig, private readonly string $keyConfig) {}

    public function inspect(string $path, string $filename, string $contentType): array
    {
        $endpoint = (string) config($this->endpointConfig);
        $key = (string) config($this->keyConfig);
        if ($endpoint === '' || $key === '') {
            throw new RuntimeException('Endpoint dan API key provider diagnosis belum dikonfigurasi.');
        }
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Berkas diagnosis tidak dapat dibaca.');
        }

        try {
            $body = Http::withToken($key)->timeout(90)
                ->attach('document', $stream, $filename, ['Content-Type' => $contentType])
                ->post($endpoint)->throw()->json();
        } finally {
            fclose($stream);
        }
        $score = $body['score'] ?? null;
        if ($score !== null && (! is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
            throw new RuntimeException('Provider diagnosis mengembalikan skor di luar rentang 0–100.');
        }

        $safe = [];
        foreach (['status', 'verdict', 'model', 'scan_id', 'credits_used'] as $field) {
            if (array_key_exists($field, $body) && is_scalar($body[$field])) $safe[$field] = $body[$field];
        }
        return ['score' => $score === null ? null : (float) $score, 'result' => $safe];
    }
}
