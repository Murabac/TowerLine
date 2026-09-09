<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('contact_name');
            $table->string('telephone');
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            $table->string('license_class_no');
            $table->string('email');
            $table->string('address');
            $table->string('site_name');
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_district_id')->constrained()->restrictOnDelete();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('letter_path');
            $table->string('map_path')->nullable();
            $table->string('layout_path');
            $table->string('radio_path');
            $table->string('icnirp_path');
            $table->string('status')->default('received');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_applications');
    }
};
