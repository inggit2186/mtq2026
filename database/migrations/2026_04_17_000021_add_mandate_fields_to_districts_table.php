<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table): void {
            $table->string('mandate_document_path')->nullable()->after('slug');
            $table->timestamp('mandate_uploaded_at')->nullable()->after('mandate_document_path');
            $table->string('mandate_status')->nullable()->after('mandate_uploaded_at');
            $table->text('mandate_verification_notes')->nullable()->after('mandate_status');
            $table->foreignId('mandate_verified_by')->nullable()->after('mandate_verification_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('mandate_verified_at')->nullable()->after('mandate_verified_by');
        });

        $districtMandates = DB::table('users')
            ->select([
                'district_id',
                'mandate_document_path',
                'mandate_uploaded_at',
                'mandate_status',
                'mandate_verification_notes',
                'mandate_verified_by',
                'mandate_verified_at',
            ])
            ->whereIn('role', ['official', 'pendamping'])
            ->whereNotNull('district_id')
            ->whereNotNull('mandate_document_path')
            ->orderBy('district_id')
            ->orderByRaw("case when mandate_status = 'verified' then 0 when mandate_status = 'submitted' then 1 when mandate_status = 'rejected' then 2 else 3 end")
            ->orderBy('id')
            ->get()
            ->unique('district_id');

        foreach ($districtMandates as $mandate) {
            DB::table('districts')
                ->where('id', $mandate->district_id)
                ->update([
                    'mandate_document_path' => $mandate->mandate_document_path,
                    'mandate_uploaded_at' => $mandate->mandate_uploaded_at,
                    'mandate_status' => $mandate->mandate_status ?: 'submitted',
                    'mandate_verification_notes' => $mandate->mandate_verification_notes,
                    'mandate_verified_by' => $mandate->mandate_verified_by,
                    'mandate_verified_at' => $mandate->mandate_verified_at,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('mandate_verified_by');
            $table->dropColumn([
                'mandate_document_path',
                'mandate_uploaded_at',
                'mandate_status',
                'mandate_verification_notes',
                'mandate_verified_at',
            ]);
        });
    }
};
