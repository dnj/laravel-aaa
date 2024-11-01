<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('aaa_users', function (Blueprint $table) {
            $table->timestamp('ping_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropColumns('aaa_users', ['ping_at']);
    }
};
