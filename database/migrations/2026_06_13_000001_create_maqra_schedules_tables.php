<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maqra_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama babak: Penyisihan, Final, dll
            $table->string('slug')->unique(); // slug: penyisihan, final, dll
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default rounds
        DB::table('maqra_rounds')->insert([
            ['name' => 'Penyisihan', 'slug' => 'penyisihan', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Final', 'slug' => 'final', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('maqra_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('maqra_rounds')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('competition_categories')->onDelete('cascade');
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->unsignedInteger('lot_min')->default(1);
            $table->unsignedInteger('lot_max');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for faster queries
            $table->index(['category_id', 'is_active']);
            $table->index(['open_at', 'close_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maqra_schedules');
        Schema::dropIfExists('maqra_rounds');
    }
};
