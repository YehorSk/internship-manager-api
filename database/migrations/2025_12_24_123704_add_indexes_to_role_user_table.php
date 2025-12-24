<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            Schema::table('role_user', function (Blueprint $table) {
                $table->unique(['user_id', 'role_id'], 'uq_role_user_user_role');
                $table->index(['role_id', 'user_id'], 'idx_role_user_role_user');
            });
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            //
        });
    }
};
