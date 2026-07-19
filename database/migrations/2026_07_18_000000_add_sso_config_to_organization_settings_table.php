<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('organization_settings', 'ssoConfig')) {
                $table->json('ssoConfig')->nullable()->after('leavePolicies');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (Schema::hasColumn('organization_settings', 'ssoConfig')) {
                $table->dropColumn('ssoConfig');
            }
        });
    }
};
