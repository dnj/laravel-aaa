<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aaa_types_children', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->references('id')
                ->on('aaa_types');

            $table->foreignId('child_id')
                ->references('id')
                ->on('aaa_types');

            $table->primary(['parent_id', 'child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aaa_types_children');
    }
};
