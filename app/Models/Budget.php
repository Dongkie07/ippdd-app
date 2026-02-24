<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $guarded = [];

    // A Budget belongs to a specific Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}