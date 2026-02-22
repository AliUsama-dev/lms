<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionValidityDateToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'subscription_validity_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('subscription_validity_date')->nullable()->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'subscription_validity_date')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('subscription_validity_date');
            });
        }
    }
}
