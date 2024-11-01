<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aaa_dummy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->nullable()
                ->references('id')
                ->on('aaa_users')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aaa_dummy');
    }
};
