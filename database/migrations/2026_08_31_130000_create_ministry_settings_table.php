<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministry_settings', function (Blueprint $table) {
            $table->id();
            $table->string('approval_director_name')->nullable();
            $table->string('approval_director_title_so');
            $table->string('approval_director_title_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_settings');
    }
};
