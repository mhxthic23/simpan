<?php

namespace App\Services\Integrations;

use App\Contracts\IpfsAdapter;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PinataIpfsAdapter implements IpfsAdapter
{
    public function upload(string $path, string $filename, string $contentType): array
    {
        $token = (string) config('services.pinata.jwt');
        if ($token === '') {
            throw new RuntimeException('PINATA_JWT belum dikonfigurasi.');
        }
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca untuk upload IPFS.');
        }

        try {
            $response = Http::withToken($token)->timeout(60)
                ->attach('file', $stream, $filename, ['Content-Type' => $contentType])
                ->post(rtrim((string) config('services.pinata.api_url'), '/').'/pinning/pinFileToIPFS')
                ->throw()->json();
        } finally {
            fclose($stream);
        }

        $cid = $response['IpfsHash'] ?? null;
        if (! is_string($cid) || $cid === '') {
            throw new RuntimeException('Pinata tidak mengembalikan CID.');
        }

        return [
            'cid' => $cid,
            'gateway_url' => rtrim((string) config('services.pinata.gateway_url'), '/').'/'.rawurlencode($cid),
        ];
    }
}
