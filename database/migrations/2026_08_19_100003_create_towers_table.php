<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('towers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->geometry('location', subtype: 'point')->nullable();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // guyed | monopole | rooftop
            $table->decimal('height_m', 8, 2);
            $table->string('capacity')->nullable();
            $table->unsignedInteger('signal_radius_m')->default(10000);
            $table->string('status')->default('active'); // active | under_construction | decommissioned
            $table->string('health_status')->default('unknown'); // good | needs_attention | critical | unknown
            $table->date('commissioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('towers');
    }
};
