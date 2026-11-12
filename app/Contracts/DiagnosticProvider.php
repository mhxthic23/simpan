<?php

namespace App\Contracts;

interface DiagnosticProvider
{
    /** @return array{score: float|null, result: array<string, mixed>} */
    public function inspect(string $path, string $filename, string $contentType): array;
}
