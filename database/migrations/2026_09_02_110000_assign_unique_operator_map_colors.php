<?php

use App\Support\OperatorPalette;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('operators')) {
            return;
        }

        $operators = DB::table('operators')->orderBy('id')->get(['id', 'name']);
        $colors = OperatorPalette::uniqueColorsFor($operators);

        foreach ($colors as $id => $color) {
            DB::table('operators')->where('id', $id)->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        // Colors are display-only; previous values are not restored.
    }
};
