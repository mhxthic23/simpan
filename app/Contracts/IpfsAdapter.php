<?php

namespace App\Contracts;

interface IpfsAdapter
{
    /** @return array{cid: string, gateway_url: string|null} */
    public function upload(string $path, string $filename, string $contentType): array;
}
