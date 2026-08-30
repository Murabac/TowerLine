<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('districts', 'name_en')) {
            return;
        }

        if (! Schema::hasColumn('districts', 'name')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->string('name')->nullable()->after('region_id');
            });
        }

        DB::table('districts')->whereNull('name')->update(['name' => DB::raw('name_en')]);

        if ($this->indexExists('districts', 'districts_region_id_name_unique')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->dropUnique(['region_id', 'name']);
            });
        }

        if (! $this->indexExists('districts', 'districts_region_id_name_unique')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->unique(['region_id', 'name']);
            });
        }

        if ($this->indexExists('districts', 'districts_region_id_name_en_unique')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->dropUnique(['region_id', 'name_en']);
            });
        }

        if (Schema::hasColumn('districts', 'name_en')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->dropColumn(['name_en', 'name_so']);
            });
        }

        Schema::table('districts', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        if (! Schema::hasColumn('sub_districts', 'name')) {
            Schema::table('sub_districts', function (Blueprint $table) {
                $table->string('name')->nullable()->after('district_id');
            });
        }

        DB::table('sub_districts')->whereNull('name')->update(['name' => DB::raw('name_en')]);

        if ($this->indexExists('sub_districts', 'sub_districts_district_id_name_unique')) {
            Schema::table('sub_districts', function (Blueprint $table) {
                $table->dropUnique(['district_id', 'name']);
            });
        }

        if (! $this->indexExists('sub_districts', 'sub_districts_district_id_name_unique')) {
            Schema::table('sub_districts', function (Blueprint $table) {
                $table->unique(['district_id', 'name']);
            });
        }

        if ($this->indexExists('sub_districts', 'sub_districts_district_id_name_en_unique')) {
            Schema::table('sub_districts', function (Blueprint $table) {
                $table->dropUnique(['district_id', 'name_en']);
            });
        }

        if (Schema::hasColumn('sub_districts', 'name_en')) {
            Schema::table('sub_districts', function (Blueprint $table) {
                $table->dropColumn(['name_en', 'name_so']);
            });
        }

        Schema::table('sub_districts', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('districts', 'name')) {
            return;
        }

        Schema::table('districts', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('region_id');
            $table->string('name_so')->nullable()->after('name_en');
        });

        DB::table('districts')->update([
            'name_en' => DB::raw('name'),
            'name_so' => DB::raw('name'),
        ]);

        Schema::table('districts', function (Blueprint $table) {
            $table->unique(['region_id', 'name_en']);
            $table->dropUnique(['region_id', 'name']);
            $table->dropColumn('name');
            $table->string('name_en')->nullable(false)->change();
            $table->string('name_so')->nullable(false)->change();
        });

        Schema::table('sub_districts', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('district_id');
            $table->string('name_so')->nullable()->after('name_en');
        });

        DB::table('sub_districts')->update([
            'name_en' => DB::raw('name'),
            'name_so' => DB::raw('name'),
        ]);

        Schema::table('sub_districts', function (Blueprint $table) {
            $table->unique(['district_id', 'name_en']);
            $table->dropUnique(['district_id', 'name']);
            $table->dropColumn('name');
            $table->string('name_en')->nullable(false)->change();
            $table->string('name_so')->nullable(false)->change();
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index],
        );

        return $result !== [];
    }
};
