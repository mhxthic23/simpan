<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingOption extends Model
{
    protected $table = 'voting_option';

    protected $fillable = ['voting_dao_id', 'opsi', 'deskripsi', 'urutan'];

    protected function casts(): array
    {
        return ['urutan' => 'integer'];
    }

    public function votingDao(): BelongsTo
    {
        return $this->belongsTo(VotingDao::class, 'voting_dao_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VotingLog::class, 'voting_option_id');
    }
}
