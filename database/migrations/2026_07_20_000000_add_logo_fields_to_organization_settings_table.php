<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('organization_settings', 'logoUrl')) {
                $table->longText('logoUrl')->nullable()->after('leavePolicies');
            }
            if (!Schema::hasColumn('organization_settings', 'logoThumbUrl')) {
                $table->longText('logoThumbUrl')->nullable()->after('logoUrl');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (Schema::hasColumn('organization_settings', 'logoThumbUrl')) {
                $table->dropColumn('logoThumbUrl');
            }
            if (Schema::hasColumn('organization_settings', 'logoUrl')) {
                $table->dropColumn('logoUrl');
            }
        });
    }
};
