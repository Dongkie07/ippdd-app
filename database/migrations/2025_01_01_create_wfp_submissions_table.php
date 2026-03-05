<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('wfp_submissions');
        Schema::create('wfp_submissions', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('no', 10)->nullable();           // row number: 1, a, b, 2...
            $table->string('department', 200);
            $table->string('sheet_code', 60)->nullable();
            $table->string('parent_dept', 200)->nullable();  // NULL = top-level, filled = sub-office
            $table->boolean('is_parent')->default(false);    // true if has children
            $table->string('status', 100)->default('Pending');
            $table->text('remarks')->nullable();
            $table->decimal('budget_total',    15, 2)->default(0);
            $table->decimal('budget_fund_101', 15, 2)->default(0);
            $table->decimal('budget_fund_164', 15, 2)->default(0);
            $table->decimal('budget_fund_161', 15, 2)->default(0);
            $table->decimal('budget_fund_163', 15, 2)->default(0);
            $table->integer('pi_count')->default(0);
            $table->timestamps();
            $table->index(['year', 'department']);
        });
    }
    public function down(): void { Schema::dropIfExists('wfp_submissions'); }
};