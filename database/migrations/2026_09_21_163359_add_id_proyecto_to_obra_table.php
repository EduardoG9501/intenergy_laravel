<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('obra', function (Blueprint $table) {
            $table->unsignedInteger('id_proyecto')->nullable()->after('id');
            $table->foreign('id_proyecto')->references('id')->on('proyecto')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obra', function (Blueprint $table) {
            $table->dropForeign(['id_proyecto']);
            $table->dropColumn('id_proyecto');
        });
    }
};
