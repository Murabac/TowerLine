<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('operators')->where('name', 'Sogasho')->update(['name' => 'Somcable']);
        DB::table('operator_region')->delete();
    }

    public function down(): void
    {
        DB::table('operators')->where('name', 'Somcable')->update(['name' => 'Sogasho']);
    }
};
