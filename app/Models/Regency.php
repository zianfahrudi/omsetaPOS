<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['province_id', 'code', 'name'])]
class Regency extends Model
{
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
