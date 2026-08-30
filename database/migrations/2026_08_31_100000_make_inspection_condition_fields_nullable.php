<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('power_status')->nullable()->change();
            $table->string('generator_condition')->nullable()->change();
            $table->string('physical_condition')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('power_status')->nullable(false)->change();
            $table->string('generator_condition')->nullable(false)->change();
            $table->string('physical_condition')->nullable(false)->change();
        });
    }
};
