<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('rejected')->index();
        });

        DB::table('shares')
            ->whereNotNull('accepted_at')
            ->where('rejected', false)
            ->orderBy('id')
            ->get()
            ->each(function (object $share): void {
                $sourceService = DB::table('services')->where('id', $share->service_id)->first();

                if (! $sourceService) {
                    return;
                }

                $sharedGroupId = $sourceService->shared_group_id ?: (string) Str::uuid();

                if (! $sourceService->shared_group_id) {
                    DB::table('services')
                        ->where('id', $sourceService->id)
                        ->update(['shared_group_id' => $sharedGroupId]);
                }

                DB::table('services')
                    ->where('user_id', $share->to_user_id)
                    ->where('shared_user_id', $share->from_user_id)
                    ->where('name', $sourceService->name)
                    ->when(
                        $sourceService->url,
                        fn ($query) => $query->where('url', $sourceService->url),
                        fn ($query) => $query->whereNull('url')
                    )
                    ->whereNull('shared_group_id')
                    ->update(['shared_group_id' => $sharedGroupId]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
