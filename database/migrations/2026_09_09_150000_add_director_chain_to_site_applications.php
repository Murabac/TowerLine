<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->string('director_decision')->nullable()->after('officer_reviewed_at');
            $table->text('director_remarks')->nullable()->after('director_decision');
            $table->string('director_name')->nullable()->after('director_remarks');
            $table->foreignId('director_reviewed_by')->nullable()->after('director_name')->constrained('users')->nullOnDelete();
            $table->timestamp('director_reviewed_at')->nullable()->after('director_reviewed_by');
            $table->string('dg_decision')->nullable()->after('director_reviewed_at');
            $table->text('dg_remarks')->nullable()->after('dg_decision');
            $table->string('dg_name')->nullable()->after('dg_remarks');
            $table->foreignId('dg_reviewed_by')->nullable()->after('dg_name')->constrained('users')->nullOnDelete();
            $table->timestamp('dg_reviewed_at')->nullable()->after('dg_reviewed_by');
            $table->foreignId('tower_id')->nullable()->after('dg_reviewed_at')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('director_reviewed_by');
            $table->dropConstrainedForeignId('dg_reviewed_by');
            $table->dropConstrainedForeignId('tower_id');
            $table->dropColumn([
                'director_decision',
                'director_remarks',
                'director_name',
                'director_reviewed_at',
                'dg_decision',
                'dg_remarks',
                'dg_name',
                'dg_reviewed_at',
            ]);
        });
    }
};
