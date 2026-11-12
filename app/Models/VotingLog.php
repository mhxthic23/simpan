<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VotingLog extends Model
{
    protected $table = 'voting_log';

    protected $fillable = ['voting_dao_id', 'voting_option_id', 'user_id', 'voted_at'];

    protected function casts(): array
    {
        return ['voted_at' => 'datetime'];
    }

    public function votingDao(): BelongsTo
    {
        return $this->belongsTo(VotingDao::class, 'voting_dao_id');
    }

    public function votingOption(): BelongsTo
    {
        return $this->belongsTo(VotingOption::class, 'voting_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function blockchainTransactions(): MorphMany
    {
        return $this->morphMany(BlockchainTransaction::class, 'reference', 'reference_type', 'reference_id');
    }
}
