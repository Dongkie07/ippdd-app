<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wfp_pis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('wfp_submissions')->cascadeOnDelete();
            $table->integer('year');
            $table->string('department', 200);
            $table->integer('seq')->default(0);
            $table->string('code', 60)->nullable();
            $table->string('reference_source', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('definition')->nullable();
            $table->string('target', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wfp_pis');
    }
};