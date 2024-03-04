<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/03/19
 * Time: 09:57 AM
 */

class Subscription_Epayco_SE extends WC_Payment_Subscription_Epayco_SE
{
    public \Epayco\Epayco $epayco;

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

    public function subscription_epayco(array $params): array
    {

        try {
            $order_id = $params['id_order'];
            $order = new WC_Order($order_id);
            $subscriptions = wcs_get_subscriptions_for_order($order);
            /* @var WC_Subscription $subscription */
            $subscription = current($subscriptions);
            $plans = $this->getPlansBySubscription($subscriptions);
            $id_client = get_post_meta($subscription->get_id(), 'id_client', true);

            $customerData = $this->getCustomerData($params, $subscription, $id_client);
            $id_client = $customerData['customer_id'];

            if(!wcs_cart_contains_renewal()){
                $this->subscriptionCreate($plans, $customerData);
            }

            $getPlans = $this->getPlans($plans);

            if (!empty($getPlans)){
                $this->plansCreate($getPlans);
            }

            $subs = $this->subscriptionCharge($plans, $customerData);
            $messageStatus = $this->handleStatusSubscriptions($subs, $subscriptions, $id_client);

            $response_status = [
                'status' => $messageStatus['status'],
                'message' => $messageStatus['message'],
                'url' => $order->get_checkout_order_received_url()
            ];

        }catch (Exception $exception){
            $response_status = ['status' => false];
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $response_status;

    }


    public function tokenCreate(array $params):string
    {

        $card_number = $params['subscriptionepayco_number'];
        $data['card_number'] = str_replace(' ','', $card_number);
        $card_expire = explode('/', $params['subscriptionepayco_expiry']);
        $data['cvc'] = $params['subscriptionepayco_cvc'];

        try{
            $token = $this->epayco->token->create(
                [
                    "card[number]" => $data['card_number'],
                    "card[exp_year]" => $card_expire[1],
                    "card[exp_month]" => $card_expire[0],
                    "card[cvc]" => $data['cvc']
                ]
            );

            if (isset($token->data->status) && $token->data->status === 'error'){
                $errorMessage = $token->data->description ?? $token->data->errors;
                throw new Exception($errorMessage);
            }

            return $token->data->id;

        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }


    public function customerCreate(array $data):string
    {
        try{
            $customer = $this->epayco->customer->create(
                [
                    "token_card" => $data['token_card'],
                    "name" => $data['subscriptionepayco_name'],
                    "email" => $data['email'],
                    "phone" => $data['phone'],
                    "cell_phone" => $data['phone'],
                    "country" =>  $data['country'],
                    "city" => $data['city'],
                    "address" => $data['address'],
                    "default" => true
                ]
            );

            if (isset($customer->data->status) && $customer->data->status === 'error'){
                $errorMessage = $customer->data->description ?? $customer->data->errors;
                throw new Exception($errorMessage);
            }

            return $customer->data->customerId;
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    public function getCustomer($id): object
    {
        try {
            return $this->epayco->customer->getList();
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }

    public function getPlans(array $plans): array
    {

        foreach ($plans as $key => $plan){
            try{
                $plan = $this->epayco->plan->get($plans[$key]['id_plan']);

                if ($plan->status)
                    unset($plans[$key]);
            }catch (Exception $exception){
                throw new Exception($exception->getMessage());
            }
        }

        return $plans;
    }

    public function plansCreate(array $plans): void
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
                throw new Exception($exception->getMessage());
            }
        }
    }

    public function subscriptionCreate(array $plans, array  $customer): array
    {

        $subs = [];

        foreach ($plans as $plan){
            try{
                $subs[] = $this->epayco->subscriptions->create(
                    [
                        "id_plan" => $plan['id_plan'],
                        "customer" => $customer['customer_id'],
                        "token_card" => $customer['token_card'],
                        "doc_type" => $customer['type_document'],
                        "doc_number" => $customer['doc_number'],
                        "url_confirmation" => $this->getUrlNotify(),
                        "method_confirmation" => "POST"
                    ]
                );
            }catch (Exception $exception){
                throw new Exception($exception->getMessage());
            }
        }

        return $subs;
    }

    public function subscriptionCharge(array $plans, array $customer): array
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
                        "url_confirmation" => $this->getUrlNotify(),
                        "method_confirmation" => "POST"
                    ]
                );

            }catch (Exception $exception){
                throw new Exception($exception->getMessage());
            }
        }

        return $subs;
    }

    public function cancelSubscription($subscription_id): bool
    {
        try{
            $this->epayco->subscriptions->cancel($subscription_id);
            return true;
        }catch (Exception $exception){
            subscription_epayco_se()->log('cancelSubscription: ' . $exception->getMessage());
            return false;
        }
    }

    public function paramsBilling(WC_Subscription $subscription, array $params): array
    {
        $data = [];

        $data['email'] = $subscription->get_billing_email();
        $data['phone'] = $subscription->get_billing_phone();
        $data['country'] = $subscription->get_shipping_country() ? $subscription->get_shipping_country() : $subscription->get_billing_country();
        $data['city'] = $subscription->get_shipping_city() ? $subscription->get_shipping_city() : $subscription->get_billing_city();
        $data['address'] = $subscription->get_shipping_address_1() ? $subscription->get_shipping_address_1() . " " . $subscription->get_shipping_address_2() : $subscription->get_billing_address_1() . " " . $subscription->get_billing_address_2();
        $data['doc_number'] = isset($params['billing_dni']) && $params['billing_dni'] ? $params['billing_dni'] : $params['shipping_dni'];
        $data['type_document'] = isset($params['billing_type_document']) && $params['billing_type_document'] ? $params['billing_type_document'] : $params['shipping_type_document'];

        return $data;
    }

    public function getPlansBySubscription(array $subscriptions): array
    {

        $plans = [];

        foreach ($subscriptions as $subscription){

            $total_discount = $subscription->get_total_discount();
            $order_currency = $subscription->get_currency();
            $total = $subscription->get_total();

            $products = $subscription->get_items();

            $product_plan = $this->getPlan($products);

            $quantity =  $product_plan['quantity'];
            $product_name = $product_plan['name'];
            $product_id = $product_plan['id'];
            $trial_days = $this->getTrialDays($subscription);

            $plan_code = "$total-$product_id";
            $plan_code = $trial_days > 0 ? $plan_code . "$trial_days-" : $plan_code;
            $plan_code = $this->currency !== $order_currency ? $plan_code . "$order_currency-" : $plan_code;
            $plan_code = $quantity > 1 ? $plan_code . "$quantity-" : $plan_code;
            $plan_code = $total_discount > 0 ? $plan_code . $total_discount : $plan_code;
            $plan_code = rtrim($plan_code, "-");

            $plans[] = array_merge(
                [
                    "id_plan" => $plan_code,
                    "name" => "Plan $product_name",
                    "description" => "Plan $product_name",
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

    public function getPlan($products): array
    {
        $product_plan = [];

        $product_plan['name'] = '';
        $product_plan['id'] = '';
        $product_plan['quantity'] = 0;

        foreach ($products as $product){
            $product_plan['name'] .= "{$product['name']}-";
            $product_plan['id'] .= "{$product['product_id']}-";
            $product_plan['quantity'] +=  $product['quantity'];
        }

        $product_plan['name'] = $this->cleanCharacters($product_plan['name']);

        return $product_plan;
    }

    public function intervalAmount(WC_Subscription $subscription): array
    {
        return  [
            "interval" => $subscription->get_billing_period(),
            "amount" => $subscription->get_total(),
            "interval_count" => $subscription->get_billing_interval()
        ];
    }

    public function getTrialDays(WC_Subscription $subscription): string
    {

        $trial_days = "0";

        $trial_start = $subscription->get_date('start');
        $trial_end = $subscription->get_date('trial_end');


        if ($trial_end > 0 )
            $trial_days = (string)(strtotime($trial_end) - strtotime($trial_start)) / (60 * 60 * 24);

        return $trial_days;
    }

    public function cleanCharacters($string): string
    {
        $string = str_replace(' ', '-', $string);
        $string = rtrim($string, "-");
        $patern = '/[^A-Za-z0-9\-]/';
        return preg_replace($patern, '', $string);
    }

    public function getUrlNotify(): string
    {
        $url = trailingslashit(get_bloginfo( 'url' )) . trailingslashit('wc-api') . strtolower(get_parent_class($this));
        return $url;
    }

    public function handleStatusSubscriptions(array $subscriptionsStatus, array $subscriptions, string $id_client): array
    {

        global $wpdb;
        $table_subscription_epayco = $wpdb->prefix . 'subscription_epayco';

        $count = 0;
        $messageStatus = [];
        $messageStatus['status'] = true;
        $messageStatus['message'] = [];

        /**
         * @var $subscription WC_Subscription
         */

        foreach ($subscriptions as $subscription){

            $sub = $subscriptionsStatus[$count];

            if(isset($sub->data->status) && $sub->data->status === 'error' && isset($sub->data->errors) && !is_array($sub->data->errors)){
                $messageStatus['message'] = [...$messageStatus['message'], $sub->data->errors];
            }

            if(isset($sub->data->status) && is_array($sub->data->errors) && $sub->data->status === 'error'){

                $errorMessages = array_map(function ($error) {
                    return $error->errorMessage;
                }, $sub->data->errors);

                $messageStatus['message'] = [...$messageStatus['message'], $errorMessages];
            }

            if(isset($sub->data->status->errors->errorMessage) && $sub->data->status === 'error'){
                $messageStatus['message'] = [...$messageStatus['message'], $sub->data->status->errors->errorMessage];
            }

            if(isset($sub->data->status) && $sub->data->status === 'error' && isset($sub->data->description) &&
                !isset($sub->data->status->errors->errorMessage) && !isset($sub->data->errors)){
                $messageStatus['message'] = [...$messageStatus['message'], $sub->data->description];
            }

            if (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 2 || $sub->data->cod_respuesta === 4){
                $messageStatus['message'] = [...$messageStatus['message'], "{$sub->data->estado}: {$sub->data->respuesta}"];
            }

            if (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 1){
                $subscription->payment_complete();
                $subscription_id = $sub->subscription->_id ?? $sub->data->extras->extra1;
                $note  = sprintf(__('Successful subscription (subscription ID: %s), reference (%s)', 'subscription-epayco'),
                    $subscription_id, $sub->data->ref_payco);
                $subscription->add_order_note($note);
                $calculated_next_payment = $subscription->calculate_date('next_payment');
                $subscription->update_dates(array('next_payment' => $calculated_next_payment));
                update_post_meta($subscription->get_id(), 'subscription_id', $subscription_id);
            }elseif (isset($sub->data->cod_respuesta) && $sub->data->cod_respuesta === 3){
                $subscription->update_status('pending');
            }

            if (isset($sub->data->cod_respuesta) &&
                isset($sub->data->ref_payco) &&
                ($sub->data->cod_respuesta !== 2 &&
                $sub->data->cod_respuesta !== 4)){
                $wpdb->insert(
                    $table_subscription_epayco,
                    [
                        'order_id' => $subscription->get_id(),
                        'ref_payco' => $sub->data->ref_payco
                    ]
                );
            }

            update_post_meta($subscription->get_id(), 'id_client', $id_client);
        }

        if (count($messageStatus['message'])) $messageStatus['status'] = false;

        return $messageStatus;

    }

    public static function subscription_epayco_se_add_new_token(): void
    {
        $instanceEpayco = new self();
        $res = ['status' => true];

        if (!wp_verify_nonce($_REQUEST['nonce'], 'subscriptionepayco_add_new_token')) return;

        try{

            $customer_id = sanitize_text_field($_POST['subscriptionepayco_id_client']);
            $token_card = sanitize_text_field($_POST['token_card']);

            $customer = $instanceEpayco->epayco->customer->addNewToken(array(
                "customer_id" => $customer_id,
                "token_card" => $_POST['token_card']
            ));

            subscription_epayco_se()->createTable();

            global $wpdb;
            $table_name = $wpdb->prefix . 'subscription_epayco_tokens';
            $query_delete = "DELETE FROM $table_name WHERE customer_id='$customer_id'";

            $wpdb->query($query_delete);
            $wpdb->insert($table_name, [
                "customer_id" => $customer_id,
                "token" => $token_card
            ]);

        }catch (Exception $exception){
            subscription_epayco_se()->log('subscriptionAddNewToken: ' . $exception->getMessage());
            $res['status'] = false;
        }

        if(isset($customer->status) && !$customer->status){
            $res['status'] = false;
        }

        wp_send_json($res);
    }

    public function getIP(): string
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

    protected function getCustomerData(array $params, WC_Subscription $subscription, mixed $id_client): array
    {
        try {
            $token = $this->tokenCreate($params);
            $customerData = $this->paramsBilling($subscription, $params);
            $customerData['token_card'] = $token;
            $customerData = array_merge($customerData, $params);
            $customerData['customer_id'] = $id_client ?: $this->customerCreate($customerData);
            return $customerData;
        }catch(Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }
}