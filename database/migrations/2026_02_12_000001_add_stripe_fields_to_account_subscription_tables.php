<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStripeFieldsToAccountSubscriptionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_customer_id', 255)->nullable();
            });
        }

        if (Schema::hasTable('account_subscription_plans') && !Schema::hasColumn('account_subscription_plans', 'stripe_price_id')) {
            Schema::table('account_subscription_plans', function (Blueprint $table) {
                $table->string('stripe_price_id', 255)->nullable()->after('duration_days');
            });
        }

        if (Schema::hasTable('account_subscriptions') && !Schema::hasColumn('account_subscriptions', 'stripe_subscription_id')) {
            Schema::table('account_subscriptions', function (Blueprint $table) {
                $table->string('stripe_subscription_id', 255)->nullable()->after('payment_method');
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
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stripe_customer_id');
            });
        }
        if (Schema::hasTable('account_subscription_plans') && Schema::hasColumn('account_subscription_plans', 'stripe_price_id')) {
            Schema::table('account_subscription_plans', function (Blueprint $table) {
                $table->dropColumn('stripe_price_id');
            });
        }
        if (Schema::hasTable('account_subscriptions') && Schema::hasColumn('account_subscriptions', 'stripe_subscription_id')) {
            Schema::table('account_subscriptions', function (Blueprint $table) {
                $table->dropColumn('stripe_subscription_id');
            });
        }
    }
}
