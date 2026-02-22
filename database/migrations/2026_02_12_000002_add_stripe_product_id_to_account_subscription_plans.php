<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeProductIdToAccountSubscriptionPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('account_subscription_plans') && !Schema::hasColumn('account_subscription_plans', 'stripe_product_id')) {
            Schema::table('account_subscription_plans', function (Blueprint $table) {
                $table->string('stripe_product_id', 255)->nullable()->after('duration_days');
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
        if (Schema::hasTable('account_subscription_plans') && Schema::hasColumn('account_subscription_plans', 'stripe_product_id')) {
            Schema::table('account_subscription_plans', function (Blueprint $table) {
                $table->dropColumn('stripe_product_id');
            });
        }
    }
}
