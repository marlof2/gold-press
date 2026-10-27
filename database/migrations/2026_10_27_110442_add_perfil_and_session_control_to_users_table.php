<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('perfil_id')->nullable()->constrained('perfis')->nullOnDelete();
            $table->string('session_token', 64)->nullable()->index();
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken();
            $table->dropColumn(['perfil_id', 'session_token', 'last_login_at']);
        });
    }
};
