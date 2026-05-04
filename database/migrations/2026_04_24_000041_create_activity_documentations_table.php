<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_documentations', function (Blueprint $table): void {
            $table->id();
            $table->string('caption');
            $table->string('image_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_documentations');
    }
};
