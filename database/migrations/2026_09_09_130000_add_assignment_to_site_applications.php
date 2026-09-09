<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn('assigned_at');
        });
    }
};
