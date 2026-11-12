<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchain_transaction';

    protected $fillable = [
        'event_type', 'reference_type', 'reference_id', 'network', 'chain_id', 'contract_address',
        'transaction_hash', 'payload_hash', 'block_number', 'status', 'attempt_count', 'idempotency_key',
        'error_message', 'submitted_at', 'confirmed_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'chain_id' => 'integer',
            'block_number' => 'integer',
            'attempt_count' => 'integer',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
