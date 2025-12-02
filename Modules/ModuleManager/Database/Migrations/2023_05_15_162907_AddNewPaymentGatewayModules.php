<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Modules\ModuleManager\Entities\Module;

class AddNewPaymentGatewayModules extends Migration
{
    public function up()
    {
        $totalCount = DB::table('modules')->count();
        $modules = [
            ['name' => 'AuthorizeNet', 'details' => 'AuthorizeNet payment gateway for MetamorphosisLMS'],
            ['name' => 'Braintree', 'details' => 'Braintree payment gateway for MetamorphosisLMS'],
            ['name' => 'Flutterwave', 'details' => 'Flutterwave payment gateway for MetamorphosisLMS'],
            ['name' => 'Mollie', 'details' => 'Mollie payment gateway for MetamorphosisLMS'],
            ['name' => 'JazzCash', 'details' => 'JazzCash payment gateway for MetamorphosisLMS'],
            ['name' => 'Coinbase', 'details' => 'Coinbase payment gateway for MetamorphosisLMS'],
            ['name' => 'CCAvenue', 'details' => 'CCAvenue payment gateway for MetamorphosisLMS'],
        ];
        foreach ($modules as $key => $module) {
            Module::updateOrCreate([
                'name' => $module['name'],
            ], [
                    'name' => $module['name'],
                    'details' => $module['details'],
                    'status' => 1,
                    'order' => $totalCount + $key
                ]
            );
        }
    }

    public function down()
    {
        //
    }
}
