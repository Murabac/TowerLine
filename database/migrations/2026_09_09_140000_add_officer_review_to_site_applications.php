<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->date('site_visit_on')->nullable()->after('assigned_at');
            $table->text('site_visit_notes')->nullable()->after('site_visit_on');
            $table->foreignId('site_visited_by')->nullable()->after('site_visit_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('site_visited_at')->nullable()->after('site_visited_by');
            $table->text('officer_remarks')->nullable()->after('site_visited_at');
            $table->foreignId('officer_reviewed_by')->nullable()->after('officer_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('officer_reviewed_at')->nullable()->after('officer_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_visited_by');
            $table->dropConstrainedForeignId('officer_reviewed_by');
            $table->dropColumn([
                'site_visit_on',
                'site_visit_notes',
                'site_visited_at',
                'officer_remarks',
                'officer_reviewed_at',
            ]);
        });
    }
};
