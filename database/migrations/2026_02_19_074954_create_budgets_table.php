<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::create('budgets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('department_id')->constrained()->cascadeOnDelete();
        $table->year('year');
        
        // Make sure these two lines are here and saved!
        $table->decimal('allocated_amount', 15, 2);
        $table->decimal('spent_amount', 15, 2)->default(0);
        
        $table->timestamps();
    });
}
};
