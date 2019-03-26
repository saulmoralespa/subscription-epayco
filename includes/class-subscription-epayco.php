<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 25/03/19
 * Time: 09:57 AM
 */

class Subscription_Epayco_SE extends WC_Payment_Subscription_Epayco_SE
{
    private $epayco;

    public function __construct()
    {
        parent::__construct();

        $lang =  get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];

        $this->epayco = new Epayco\Epayco(
            array(
                "apiKey" => $this->apiKey,
                "privateKey" => $this->privateKey,
                "lenguage" => strtoupper($lang),
                "test" => $this->isTest
            )
        );

    }

    public function subscription_epayco($params)
    {
        $order_id = $params['id_order'];
        $order = new WC_Order($order_id);

        $card = $this->prepareDataCard($params);

        $token = $this->tokenCreate($card);

        $subscription = $this->getWooCommerceSubscriptionFromOrderId($order_id);

        $customerData = $this->paramsBilling($subscription);

        $customerData['token_card'] = $token->data->id;

        $customer = $this->customerCreate($customerData);

        $response_status = array('status' => false, 'message' => __('An internal error has arisen, try again', 'subscription-epayco'));

        if (!$customer)
            return $response_status;

        $product = $this->getProductFromOrder($order);

        $order_currency = $order->get_currency();

        $plan = $this->getPlanByProduct($product, $order_currency);

        $planCreate = array_merge(
            $plan, $this->getTrialDays($subscription),
            array(
                "interval" => $subscription->billing_period,
                "amount" => WC_Subscriptions_Order::get_recurring_total( $order ),
                "interval_count" => WC_Subscriptions_Order::get_subscription_interval( $order )

            )
        );

        $getPlan = $this->getPlan($planCreate['id_plan']);
        if (!$getPlan->status)
            $this->planCreate($planCreate);

        $subscriptionCreate = array(
            "id_plan" => $planCreate['id_plan'],
            "customer" => $customer->data->customerId,
            "token_card" => $token->data->id,
            "doc_number" => get_post_meta( $subscription->get_id(), '_billing_dni', true )
        );

        $this->subscriptionCreate($subscriptionCreate);

        $sub = $this->subscriptionCharge($subscriptionCreate);

        if ($sub->data->estado === 'Aceptada'){
            $order->payment_complete();
            $note  = sprintf(__('Successful subscription (subscription ID: %s), reference (%s)', 'subscription-epayco'),
                $sub->subscription->_id, $sub->data->ref_payco);
            $subscription->add_order_note($note);

        }elseif ($sub->data->estado === 'Rechazada' || $sub->data->estado === ' Fallida'){
            $order->update_status('failed');
        }else{
            $order->update_status('pending');
        }

        update_post_meta($subscription->get_id(), 'id_client', $customer->data->customerId);

        $response_status = array('status' => true, 'url' => $order->get_checkout_order_received_url());

        return $response_status;

    }

    public function tokenCreate($data)
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
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $token;
    }

    public function customerCreate($data)
    {
        $customer = false;

        try{
            $customer = $this->epayco->customer->create(
                array(
                "token_card" => $data['token_card'],
                "name" => $data['name'],
                "email" => $data['email'],
                "phone" => $data['phone'],
                "default" => true
            )
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $customer;
    }

    public function getPlan($id)
    {
        $plan = false;

        try{
            $plan = $this->epayco->plan->get($id);
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $plan;
    }

    public function planCreate($data)
    {
        $plan = false;

        try{
            $plan = $this->epayco->plan->create(
                array(
                "id_plan" => $data['id_plan'],
                "name" => $data['name'],
                "description" => $data['description'],
                "amount" => $data['amount'],
                "currency" => $data['currency'],
                "interval" => $data['interval'],
                "interval_count" => $data['interval_count'],
                "trial_days" => $data['trial_days']
            )
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $plan;
    }

    public function subscriptionCreate($data)
    {
        $sub = false;

        try{
            $sub = $this->epayco->subscriptions->create(
                array(
                "id_plan" => $data['id_plan'],
                "customer" => $data['customer'],
                "token_card" => $data['token_card'],
                "doc_type" => "CC",
                "doc_number" => $data['doc_number']
            )
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $sub;
    }

    public function subscriptionCharge($data)
    {
        $sub = false;

        try{
            $sub = $this->epayco->subscriptions->charge(
                array(
                    "id_plan" => $data['id_plan'],
                    "customer" => $data['customer'],
                    "token_card" => $data['token_card'],
                    "doc_type" => "CC",
                    "doc_number" => $data['doc_number'],
                    "url_confirmation" => $this->getUrlNotify()
                )
            );
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }

        return $sub;
    }

    public function cancelSubscription($idClient)
    {
        try{
            $this->epayco->subscriptions->cancel($idClient);
        }catch (Exception $exception){
            subscription_epayco_se()->log($exception->getMessage());
        }
    }

    private function getWooCommerceSubscriptionFromOrderId($orderId)
    {
        $subscriptions = wcs_get_subscriptions_for_order($orderId);
        return end($subscriptions);
    }

    public function prepareDataCard($params)
    {
        $data = array();

        $card_number = $params['subscriptionepayco_number'];
        $data['card_number'] = str_replace(' ','', $card_number);
        $card_expire = $params['subscriptionepayco_expiry'];
        $data['cvc'] = $params['subscriptionepayco_cvc'];


        $year = date('Y');
        $lenyear = substr($year, 0,2);
        $expires = str_replace(' ', '', $card_expire);
        $expire = explode('/', $expires);
        $month = $expire[0];
        if (strlen($month) == 1) $month = '0' . $month;
        $yearEnd =  strlen($expire[1]) == 4 ? $expire[1] :  $lenyear . substr($expire[1], -2);
        $data['card_expire_year'] = $yearEnd;
        $data['card_expire_month'] = $month;

        return $data;

    }

    public function paramsBilling($subscription)
    {
        $data = array();

        $data['name'] =  $subscription->get_shipping_first_name() ? $subscription->get_shipping_first_name() . " " . $subscription->get_shipping_last_name() : $subscription->get_billing_first_name() . " " . $subscription->get_billing_last_name();
        $data['email'] = $subscription->get_billing_email();
        $data['phone'] = $subscription->get_billing_phone();

        return $data;
    }

    public function getProductFromOrder($order)
    {
        $products = $order->get_items();
        $count = $order->get_item_count();
        if ($count > 1)
        {
            wc_add_notice(__('Currently Subscription ePayco does not support more than one product in the cart if one of the products is a subscription.', 'subscription-epayco'), 'error');
        }
        return array_values($products)[0];
    }

    public function getPlanByProduct($product, $order_currency)
    {
        $product_name = $product['name'];
        $produt_name = $this->cleanCharacters($product_name);
        $product_id = $product['product_id'];
        $quantity =  $product['quantity'];
        $planCode = "$produt_name-$product_id";
        $planCode = $this->currency !== $order_currency ? "$planCode-$order_currency" : $planCode;
        $planCode = $quantity > 1 ? "$planCode-$quantity" : "$planCode";
        return array(
            "id_plan" => $planCode,
            "name" => "Plan $planCode",
            "description" => "Plan $planCode",
            "currency" => $order_currency
        );
    }

    public function getTrialDays($subscription)
    {
        $trial_start = $subscription->get_date('start');
        $trial_end = $subscription->get_date('trial_end');
        $trial_days = "0";
        if ($trial_end > 0 ){
            $trial_days = (string)(strtotime($trial_end) - strtotime($trial_start)) / (60 * 60 * 24);
        }
        return array(
            'trial_days' => $trial_days
        );
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
}