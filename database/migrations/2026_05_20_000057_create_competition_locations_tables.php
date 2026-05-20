<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->string('venue_name');
            $table->text('map_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_thumb_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_category_location', function (Blueprint $table): void {
            $table->foreignId('competition_category_id')
                ->constrained('competition_categories')
                ->cascadeOnDelete();
            $table->foreignId('competition_location_id')
                ->constrained('competition_locations')
                ->cascadeOnDelete();
            $table->primary(['competition_category_id', 'competition_location_id'], 'competition_category_location_primary');
            $table->index('competition_location_id', 'competition_category_location_location_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_category_location');
        Schema::dropIfExists('competition_locations');
    }
};
