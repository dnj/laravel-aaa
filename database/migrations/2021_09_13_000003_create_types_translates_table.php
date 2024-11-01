<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aaa_types_translates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('type_id')
                ->references('id')
                ->on('aaa_types')
                ->cascadeOnDelete();

            $table->string('locale', 2);
            $table->string('title');

            $table->unique(['locale', 'type_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aaa_types_translates');
    }
};
