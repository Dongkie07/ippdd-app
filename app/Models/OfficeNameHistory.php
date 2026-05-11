<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeNameHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'name',
        'acronym',
        'effective_from_year',
        'effective_to_year',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'effective_from_year' => 'integer',
        'effective_to_year' => 'integer',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
