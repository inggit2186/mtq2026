<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_access_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('participant_registration_open')->default(true);
            $table->boolean('participant_edit_open')->default(true);
            $table->boolean('mandate_upload_open')->default(true);
            $table->boolean('participant_documents_open')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_access_settings');
    }
};
