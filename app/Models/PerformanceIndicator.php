<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceIndicator extends Model
{
    use HasFactory;

    protected $guarded = [];

    // A Performance Indicator belongs to a specific Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}