<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_name');
            $table->string('event_title');
            $table->string('event_location')->nullable();
            $table->string('signature_city');
            $table->json('officials');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
