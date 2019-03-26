<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 18/03/19
 * Time: 09:35 PM
 */

class WC_Payment_Subscription_Epayco_SE extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'subscription_epayco';
        $this->icon = subscription_epayco_se()->plugin_url . 'assets/images/logo.png';
        $this->method_title = __('Subscription ePayco');
        $this->method_description = __('Subscription ePayco recurring payments');
        $this->description  = $this->get_option( 'description' );
        $this->order_button_text = __('To subscribe', 'subscription-epayco');
        $this->has_fields = true;
        $this->supports = array(
            'subscriptions',
            'subscription_cancellation'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');

        $this->isTest = (bool)$this->get_option( 'environment' );
        $this->debug = $this->get_option( 'debug' );
        $this->currency = get_option('woocommerce_currency');
        $this->apiKey = $this->get_option('apiKey');
        $this->privateKey = $this->get_option('privateKey');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_subscription_status_cancelled', array($this, 'subscription_cancelled'));
        add_action('woocommerce_available_payment_gateways', array($this, 'disable_non_subscription'), 20);
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'confirmation_ipn'));
    }

    public function is_available()
    {
        return parent::is_available() &&
            !empty($this->apiKey) &&
            !empty($this->privateKey);
    }

    public function init_form_fields()
    {
        $this->form_fields = require( dirname( __FILE__ ) . '/admin/epayco-settings.php' );
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            $this->generate_settings_html();
            ?>
        </table>
        <?php
    }

    public function payment_fields()
    {
        if ( $description = $this->get_description() ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }

        ?>
        <div id="card-epayco-suscribir">
            <div class='card-wrapper'></div>
            <div id="form-epayco">
                <label for="number" class="label"><?php echo __('Data of card', 'subscription-epayco'); ?> *</label>
                <input placeholder="<?php echo __('Card number', 'subscription-epayco'); ?>" type="tel" name="subscriptionepayco_number" id="subscriptionepayco_number" required="" class="form-control">
                <input placeholder="<?php echo __('Cardholder', 'subscription-epayco'); ?>" type="text" name="subscriptionepayco_name" id="subscriptionepayco_name" required="" class="form-control">
                <input type="hidden" name="subscriptionepayco_type" id="subscriptionepayco_type">
                <input placeholder="MM/YY" type="tel" name="subscriptionepayco_expiry" id="subscriptionepayco_expiry" required="" class="form-control" >
                <input placeholder="123" type="text" name="subscriptionepayco_cvc" id="subscriptionepayco_cvc" required="" class="form-control" maxlength="4">
            </div>
        </div>
        <?php
    }

    public function validate_fields()
    {
        if (isset($_POST['subscriptionepayco_errorcard'])){
            wc_add_notice($_POST['subscriptionepayco_errorcard'], 'error' );
            return false;
        }

        return true;
    }

    public function process_payment($order_id)
    {
        $params = $_POST;
        $params['id_order'] = $order_id;

        $subscription = new Subscription_Epayco_SE();
        $data = $subscription->subscription_epayco($params);

        if($data['status']){
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $data['url']
            );
        }else{
            wc_add_notice($data['message'], 'error' );
        }

        return parent::process_payment($order_id);

    }

    public function subscription_cancelled($subscription)
    {
        $id = $subscription->get_id();
        $idClient = get_post_meta( $id, 'id_client', true );

        $subscription = new Subscription_Epayco_SE();
        $subscription->cancelSubscription($idClient);
    }

    public function disable_non_subscription($availableGateways)
    {
        $enable = WC_Subscriptions_Cart::cart_contains_subscription();
        if (!$enable)
        {
            if (isset($availableGateways[$this->id]))
            {
                unset($availableGateways[$this->id]);
            }
        }
        return $availableGateways;
    }

    public function confirmation_ipn()
    {
        $body = file_get_contents('php://input');
        parse_str($body, $data);

        subscription_epayco_se()->log($data);

        header("HTTP/1.1 200 OK");
    }
}