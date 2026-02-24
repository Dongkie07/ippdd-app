<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::create('wfp_data', function (Blueprint $table) {
        $table->id();
        $table->integer('year');
        $table->string('department');
        $table->string('sheet_code');
        $table->decimal('budget', 15, 2)->default(0);
        $table->integer('pi_count')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wfp_data');
    }
};
