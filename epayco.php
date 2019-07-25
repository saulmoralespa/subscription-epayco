<?php

require_once ('lib/vendor/autoload.php');

$epayco = new Epayco\Epayco(array(
    "apiKey" => "47a2c048f92dfef1522dce5018d8af28",
    "privateKey" => "fb591894811608d7de42616c01478930",
    "lenguage" => "ES",
    "test" => true
));


try{
    $plan = $epayco->plan->get('Informe-HostIn--02300');
    var_dump($plan->status);
}catch (Exception $exception){
}


$sub = $epayco->subscriptions->cancel("68wAwt73NLYoy5jxu");

var_dump($sub);


/*$token = $epayco->token->create(array(
    "card[number]" => '4575623182290326',
    "card[exp_year]" => "2019",
    "card[exp_month]" => "07",
    "card[cvc]" => "123"
));

var_dump($token);*/

/*$plan = $epayco->plan->create(array(
    "id_plan" => "coursereact",
    "name" => "Course react js",
    "description" => "Course react and redux",
    "amount" => 30000,
    "currency" => "cop",
    "interval" => "month",
    "interval_count" => 1,
    "trial_days" => 30
));*/


/*$customer = $epayco->customer->create(array(
    "token_card" => 'jCpA4tBYP6Nok2kuL',
    "name" => "Andres",
    //"last_name" => "Perez",
    "email" => "andresperez@payco.co",
    "default" => true,
    //Optional parameters: These parameters are important when validating the credit card transaction
    "city" => "Bogota",
    "address" => "Cr 4 # 55 36",
    "phone" => "3005234321",
    "cell_phone"=> "3010000001",
));

var_dump($customer);*/
