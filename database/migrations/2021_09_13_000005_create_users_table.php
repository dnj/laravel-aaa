<?php

use dnj\AAA\Contracts\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aaa_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('type_id')
                ->references('id')
                ->on('aaa_types')
                ->cascadeOnDelete();

            $table->enum('status', array_column(UserStatus::cases(), 'value'))
                ->index();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aaa_users');
    }
};
