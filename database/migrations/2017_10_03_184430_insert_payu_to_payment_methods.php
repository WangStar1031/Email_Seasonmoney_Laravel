<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertPayuToPaymentMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert some stuff
        DB::table('payment_methods')->insert(
          array(
              'uid' => uniqid(),
              'name' => 'PayU Money',
              'type' => \Acelle\Model\PaymentMethod::TYPE_PAYU_MONEY,
              'status' => \Acelle\Model\PaymentMethod::STATUS_INACTIVE,
              'custom_order' => 6,
              'created_at' => '2017-10-02 00:00:00',
              'updated_at' => '2017-10-02 00:00:00',
          )
      );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Insert some stuff
        DB::table('payment_methods')->where('type', '=', \Acelle\Model\PaymentMethod::TYPE_PADDLE_CARD)->delete();
    }
}
