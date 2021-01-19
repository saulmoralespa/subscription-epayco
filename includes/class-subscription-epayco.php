<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/03/19
 * Time: 09:57 AM
 */

class Subscription_Epayco_SE extends WC_Payment_Subscription_Epayco_SE
{
    public $epayco;

    public function __construct()
    {
        parent::__construct();

        $lang =  get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];

        $this->epayco = new Epayco\Epayco(
            [
                "apiKey" => $this->apiKey,
                "privateKey" => $this->privateKey,
                "lenguage" => strtoupper($lang),
                "test" => $this->isTest
            ]
        );

    }

    public function subscription_epayco(array $params)
    {
        $order_id = $params['id_order'];
        $order = new WC_Order($order_id);

        $card = $this->prepareDataCard($params);

        $token = $this->tokenCreate($card);

        $subscriptions = $this->getWooCommerceSubscriptionFromOrderId($order_id);

        $customerData = $this->paramsBilling($subscriptions);

        $customerData['token_card'] = $token->data->id;

        $customerData = array_merge($customerData, $card);

        $customer = $this->customerCreate($customerData);

        $customerData['customer_id'] = $customer->data->customerId;

        $response_status = [
            'status' => false,
            'message' => __('An internal error has arisen, try again', 'subscription-epayco')
        ];

        if (!$customer)
            return $response_status;

        $plans = $this->getPlansBySubscription($subscriptions);

        $getPlans = $this->getPlans($plans);

        if (!empty($getPlans))
            $this->plansCreate($getPlans);

        $this->subscriptionCreate($plans, $customerData);

        $subs = $this->subscriptionCharge($plans, $customerData);

        if($this->debug === 'yes')
            subscription_epayco_se()->log($subs);

        $messageStatus = $this->handleStatusSubscriptions($subs, $subscriptions, $customerData);

        $response_status = ['status' => $messageStatus['status'],
            'message' => $messageStatus['message'],
            'url' => $order->get_checkout_order_received_url()
        ];

        return $response_status;

    }

    public function tokenCreate(array $data)
    {
        $token = false;

        try{
            $token = $this->epayco->token->create(
                array(
                "card[number]" => $data['card_number'],
                "card[exp_year]" => $data['card_expire_year'],
                "card[exp_month]" => $data['card_expire_month'],
                "card[cvc]" => $data['cvc']
            )
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log('tokenCreate: ' . $exception->getMessage());
        }

        return $token;
    }

    public function customerCreate(array $data)
    {
        $customer = false;

        try{
            $customer = $this->epayco->customer->create(
                [
                    "token_card" => $data['token_card'],
                    "name" => $data['card_holder_name'],
                    "email" => $data['email'],
                    "phone" => $data['phone'],
                    "cell_phone" => $data['phone'],
                    "country" =>  $data['country'],
                    "city" => $data['city'],
                    "address" => $data['address'],
                    "default" => true
                ]
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log('create client: ' . $exception->getMessage());
        }

        return $customer;
    }

    public function getPlans(array $plans)
    {

        foreach ($plans as $key => $plan){
            try{
                $plan = $this->epayco->plan->get($plans[$key]['id_plan']);

                if ($plan->status)
                    unset($plans[$key]);
            }catch (Exception $exception){
                subscription_epayco_se()->log('getPlans: ' . $exception->getMessage());
            }
        }

        return $plans;
    }

    public function plansCreate(array $plans)
    {

        foreach ($plans as $plan){
            try{
                $this->epayco->plan->create(
                    [
                        "id_plan" => $plan['id_plan'],
                        "name" => $plan['name'],
                        "description" => $plan['description'],
                        "amount" => $plan['amount'],
                        "currency" => $plan['currency'],
                        "interval" => $plan['interval'],
                        "interval_count" => $plan['interval_count'],
                        "trial_days" => $plan['trial_days']
                    ]
                );
            }catch (Exception $exception){
                subscription_epayco_se()->log('create plan: ' . $exception->getMessage());
            }
        }
    }

    public function subscriptionCreate(array $plans, array  $customer)
    {

        foreach ($plans as $plan){
            try{
                $this->epayco->subscriptions->create(
                    [
                        "id_plan" => $plan['id_plan'],
                        "customer" => $customer['customer_id'],
                        "token_card" => $customer['token_card'],
                        "doc_type" => $customer['type_document'],
                        "doc_number" => $customer['doc_number']
                    ]
                );
            }catch (Exception $exception){
                subscription_epayco_se()->log('subscriptionCreate: ' .  $exception->getMessage());
            }
        }
    }

    public function subscriptionCharge(array $plans, array $customer)
    {
        $subs = [];

        foreach ($plans as $plan){
            try{
                $subs[] = $this->epayco->subscriptions->charge(
                    [
                        "id_plan" => $plan['id_plan'],
                        "customer" => $customer['customer_id'],
                        "token_card" => $customer['token_card'],
                        "doc_type" => $customer['type_document'],
                        "doc_number" => $customer['doc_number'],
                        "ip" => $this->getIP(),
                        "url_confirmation" => $this->getUrlNotify()
                    ]
                );

            }catch (Exception $exception){
                subscription_epayco_se()->log('subscriptionCharge: ' . $exception->getMessage());
            }
        }

        return $subs;
    }

    public function cancelSubscription($subscription_id)
    {
        try{
            $this->epayco->subscriptions->cancel($subscription_id);
        }catch (Exception $exception){
            subscription_epayco_se()->log('cancelSubscription: ' . $exception->getMessage());
        }
    }

    private function getWooCommerceSubscriptionFromOrderId($orderId)
    {
        $subscriptions = wcs_get_subscriptions_for_order($orderId);

        return $subscriptions;
    }

    public function prepareDataCard(array $params)
    {
        $data = [];

        $card_number = $params['subscriptionepayco_number'];
        $data['card_number'] = str_replace(' ','', $card_number);
        $card_expire = explode('/', $params['subscriptionepayco_expiry']);
        $data['cvc'] = $params['subscriptionepayco_cvc'];


        $data['card_expire_year'] = $card_expire[1];
        $data['card_expire_month'] = $card_expire[0];
        $data['card_holder_name'] = $params['subscriptionepayco_name'];

        return $data;

    }

    public function paramsBilling($subscriptions)
    {
        $data = [];

        $subscription = end($subscriptions);

        $data['email'] = $subscription->get_billing_email();
        $data['phone'] = $subscription->get_billing_phone();
        $data['country'] = $subscription->get_shipping_country() ? $subscription->get_shipping_country() : $subscription->get_billing_country();
        $data['city'] = $subscription->get_shipping_city() ? $subscription->get_shipping_city() : $subscription->get_billing_city();
        $data['address'] = $subscription->get_shipping_address_1() ? $subscription->get_shipping_address_1() . " " . $subscription->get_shipping_address_2() : $subscription->get_billing_address_1() . " " . $subscription->get_billing_address_2();
        $data['doc_number'] = get_post_meta( $subscription->get_id(), '_billing_dni', true );
        $data['type_document'] = get_post_meta( $subscription->get_id(), '_billing_type_document', true );

        return $data;
    }

    public function getPlansBySubscription(array $subscriptions)
    {

        $plans = [];

        foreach ($subscriptions as $key => $subscription){

            $total_discount = $subscription->get_total_discount();
            $order_currency = $subscription->get_currency();

            $products = $subscription->get_items();

            $product_plan = $this->getPlan($products);

            $quantity =  $product_plan['quantity'];
            $product_name = $product_plan['name'];
            $product_id = $product_plan['id'];
            $trial_days = $this->getTrialDays($subscription);

            $plan_code = "$product_name-$product_id";
            $plan_code = $trial_days > 0 ? "$product_name-$product_id-$trial_days" : $plan_code;
            $plan_code = $this->currency !== $order_currency ? "$plan_code-$order_currency" : $plan_code;
            $plan_code = $quantity > 1 ? "$plan_code-$quantity" : $plan_code;
            $plan_code = $total_discount > 0 ? "$plan_code-$total_discount" : $plan_code;
            $plan_code = rtrim($plan_code, "-");

            $plans[] = array_merge(
                [
                    "id_plan" => $plan_code,
                    "name" => "Plan $plan_code",
                    "description" => "Plan $plan_code",
                    "currency" => $order_currency,
                ],
                [
                    "trial_days" => $trial_days
                ],
                $this->intervalAmount($subscription)
            );
        }

        return $plans;
    }

    public function getPlan($products)
    {
        $product_plan = [];

        $product_plan['name'] = '';
        $product_plan['id'] = 0;
        $product_plan['quantity'] = 0;

        foreach ($products as $product){
            $product_plan['name'] .= "{$product['name']}-";
            $product_plan['id'] .= "{$product['product_id']}-";
            $product_plan['quantity'] .=  $product['quantity'];
        }

        $product_plan['name'] = $this->cleanCharacters($product_plan['name']);

        return $product_plan;
    }

    public function intervalAmount(WC_Subscription $subscription)
    {
        return  [
            "interval" => $subscription->get_billing_period(),
            "amount" => $subscription->get_total(),
            "interval_count" => $subscription->get_billing_interval()
        ];
    }

    public function getTrialDays(WC_Subscription $subscription)
    {

        $trial_days = "0";

        $trial_start = $subscription->get_date('start');
        $trial_end = $subscription->get_date('trial_end');


        if ($trial_end > 0 )
            $trial_days = (string)(strtotime($trial_end) - strtotime($trial_start)) / (60 * 60 * 24);

        return $trial_days;
    }

    public function cleanCharacters($string)
    {
        $string = str_replace(' ', '-', $string);
        $patern = '/[^A-Za-z0-9\-]/';
        return preg_replace($patern, '', $string);
    }

    public function getUrlNotify()
    {
        $url = trailingslashit(get_bloginfo( 'url' )) . trailingslashit('wc-api') . strtolower(get_parent_class($this));
        return $url;
    }

    public function handleStatusSubscriptions(array $subscriptionsStatus, array $subscriptions, array $customer)
    {

        global $wpdb;
        $table_subscription_epayco = $wpdb->prefix . 'subscription_epayco';

        $count = 0;
        $messageStatus = [];
        $messageStatus['status'] = true;
        $messageStatus['message'] = [];

        $quantitySubscriptions = count($subscriptionsStatus);

        foreach ($subscriptions as $subscription){

            $sub = $subscriptionsStatus[$count];

            if(isset($sub->data->status) && $sub->data->status === 'error' && isset($sub->data->status->errors->errorMessage))
                $messageStatus['message'] = array_merge($messageStatus['message'], [ $sub->data->status->errors->errorMessage ]);
            if(isset($sub->data->status) && $sub->data->status === 'error' && !isset($sub->data->status->errors->errorMessage))
                $messageStatus['message'] = array_merge($messageStatus['message'], [ $sub->data->description ]);

            if (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 2 || $sub->data->cod_respuesta === 4)
                $messageStatus['message'] = array_merge($messageStatus['message'], [ "{$sub->data->estado}: {$sub->data->respuesta}" ]);

            if (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 1){
                $subscription->payment_complete();
                $note  = sprintf(__('Successful subscription (subscription ID: %s), reference (%s)', 'subscription-epayco'),
                    $sub->subscription->_id, $sub->data->ref_payco);
                $subscription->add_order_note($note);
                update_post_meta($subscription->get_id(), 'subscription_id', $sub->subscription->_id);
            }elseif (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 3){
                $subscription->update_status('pending');
                $wpdb->insert(
                    $table_subscription_epayco,
                    [
                        'order_id' => $subscription->get_id(),
                        'ref_payco' => $sub->data->ref_payco
                    ]
                );

            }

            if (isset($sub->data->cod_respuesta) &&
                isset($sub->data->ref_payco) &&
                ($sub->data->cod_respuesta === 3) ||
                $sub->data->cod_respuesta !== 1){
                $wpdb->insert(
                    $table_subscription_epayco,
                    [
                        'order_id' => $subscription->get_id(),
                        'ref_payco' => $sub->data->ref_payco
                    ]
                );
            }

            $count++;

            if ($count === $quantitySubscriptions && count($messageStatus['message']) >= $count)
                $messageStatus['status'] = false;

            update_post_meta($subscription->get_id(), 'id_client', $customer['customer_id']);
        }

        return $messageStatus;

    }

    public function getIP()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = '127.0.0.1';

        return $ipaddress;
    }
}