<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    // Allow all fields to be mass-assigned (for our seeder)
    protected $guarded = [];

    // A Department has many Budgets
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    // A Department has many Performance Indicators
    public function performanceIndicators()
    {
        return $this->hasMany(PerformanceIndicator::class);
    }
}