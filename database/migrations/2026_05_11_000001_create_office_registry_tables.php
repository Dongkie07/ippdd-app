<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table): void {
            $table->id();
            $table->string('office_key', 120)->unique();
            $table->string('current_name', 200);
            $table->string('acronym', 40)->nullable();
            $table->string('status', 30)->default('Active');
            $table->timestamps();

            $table->index(['status', 'current_name']);
        });

        Schema::create('office_name_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('acronym', 40)->nullable();
            $table->unsignedSmallInteger('effective_from_year');
            $table->unsignedSmallInteger('effective_to_year')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['office_id', 'effective_from_year', 'effective_to_year'], 'office_history_year_index');
        });

        Schema::table('wfp_submissions', function (Blueprint $table): void {
            if (!Schema::hasColumn('wfp_submissions', 'office_id')) {
                $table->foreignId('office_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('offices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('wfp_submissions', function (Blueprint $table): void {
            if (Schema::hasColumn('wfp_submissions', 'office_id')) {
                $table->dropConstrainedForeignId('office_id');
            }
        });

        Schema::dropIfExists('office_name_histories');
        Schema::dropIfExists('offices');
    }
};
