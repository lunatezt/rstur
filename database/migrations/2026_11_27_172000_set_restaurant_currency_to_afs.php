<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->default('AFS')->change();
        });

        DB::table('restaurants')->update(['currency_symbol' => 'AFS']);
    }

    public function down(): void
    {
        DB::table('restaurants')->update(['currency_symbol' => '₹']);

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->default('₹')->change();
        });
    }
};
