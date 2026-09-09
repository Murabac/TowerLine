<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
        });

        Schema::table('site_applications', function (Blueprint $table) {
            $table->string('officer_signature_path')->nullable()->after('officer_reviewed_at');
            $table->string('director_signature_path')->nullable()->after('director_reviewed_at');
            $table->string('dg_signature_path')->nullable()->after('dg_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });

        Schema::table('site_applications', function (Blueprint $table) {
            $table->dropColumn(['officer_signature_path', 'director_signature_path', 'dg_signature_path']);
        });
    }
};
