<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequency_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('band_label');
            $table->string('frequency_range');
            $table->text('channel_details')->nullable();
            $table->date('issued_at');
            $table->date('expires_at');
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->foreignId('renewed_from_id')->nullable()->constrained('frequency_allocations')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequency_allocations');
    }
};
