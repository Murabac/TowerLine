<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequency_allocation_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('frequency_allocation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->date('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('template_version', 32)->default('2026-08');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequency_allocation_letters');
    }
};
