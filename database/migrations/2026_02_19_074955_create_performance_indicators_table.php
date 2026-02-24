<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
{
    Schema::create('performance_indicators', function (Blueprint $table) {
        $table->id();
        $table->foreignId('department_id')->constrained()->cascadeOnDelete();
        $table->year('year');
        $table->integer('target_count');
        $table->integer('achieved_count')->default(0);
        $table->timestamps();
    });
}
};
