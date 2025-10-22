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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('rol_id')->nullable()->after('id');

            // Define la relación con la tabla rol
            $table->foreign('rol_id')
                ->references('id_rol')
                ->on('rol')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            
            $table->dropForeign(['rol_id']);
            $table->dropColumn('rol_id');
        
        });
    }
};
