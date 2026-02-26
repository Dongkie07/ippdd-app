<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wfp_imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->integer('year');
            $table->integer('dept_count')->default(0);
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->string('imported_by')->default('Admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wfp_imports');
    }
};