<?php

namespace App\Services\Integrations;

use App\Contracts\AiWritingDetector;
use App\Contracts\PlagiarismChecker;
use RuntimeException;

class UnavailableDiagnosticProvider implements AiWritingDetector, PlagiarismChecker
{
    public function inspect(string $path, string $filename, string $contentType): array
    {
        throw new RuntimeException('Provider pemeriksaan belum dikonfigurasi.');
    }
}
