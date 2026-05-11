<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';

    protected $fillable = [
        'office_key',
        'current_name',
        'acronym',
        'status',
    ];

    public function nameHistories(): HasMany
    {
        return $this->hasMany(OfficeNameHistory::class)->orderBy('effective_from_year');
    }
}
