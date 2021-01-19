<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 12/03/19
 * Time: 11:56 AM
 */

class Subscription_Epayco_SE_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * @var WC_Logger
     */
    public $logger;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version, $name)
    {
        $this->file = $file;
        $this->version = $version;
        $this->name = $name;
        // Path.
        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
        $this->logger = new WC_Logger();
    }

    public function run_epayco()
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( __( 'Subscription Payu Latam can only be called once'));
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                subscription_epayco_se_notices('Subscription Payu Latam: ' . $e->getMessage());
            }
        }
    }

    protected function _run()
    {
        require_once($this->includes_path . 'class-subscription-epayco-admin.php');
        require_once ($this->includes_path . 'class-gateway-subscription-epayco.php');
        require_once ($this->includes_path . 'class-subscription-epayco.php');
        $this->admin = new Subscription_Epayco_SE_Admin();
        if (!class_exists('Epayco\Epayco'))
            require_once ($this->lib_path . 'vendor/autoload.php');
        add_filter( 'plugin_action_links_' . plugin_basename( $this->file), array( $this, 'plugin_action_links' ) );
        add_filter( 'woocommerce_payment_gateways', array($this, 'woocommerce_suscription_epayco_add_gateway'));
        add_filter( 'woocommerce_checkout_fields', array($this, 'custom_woocommerce_billing_fields'));
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="'.admin_url( 'admin.php?page=wc-settings&tab=checkout&section=subscription_epayco').'">' . esc_html__( 'Configuraciones') . '</a>';
        $plugin_links[] = '<a href="https://wordpress.org/plugins/subscription-epayco/">' . esc_html__( 'Documentación' ) . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function woocommerce_suscription_epayco_add_gateway($methods)
    {

        if (class_exists('WC_Subscriptions'))
            $methods[] = 'WC_Payment_Subscription_Epayco_SE';
        return $methods;
    }

    public function custom_woocommerce_billing_fields($fields)
    {
        $fields['billing']['billing_type_document'] = array(
            'label'       => __('Tipo de documento', 'subscription-epayco'),
            'placeholder' => _x('', 'placeholder', 'subscription-epayco'),
            'required'    => true,
            'clear'       => false,
            'type'        => 'select',
            'default' => 'CC',
            'options'     => array(
                'CC' => __('Cédula de ciudadanía' ),
                'CE' => __('Cédula de extranjería'),
                'PPN' => __('Pasaporte'),
                'SSN' => __('Número de seguridad social'),
                'LIC' => __('Licencia de conducción'),
                'NIT' => __('(NIT) Número de indentificación tributaria'),
                'TI' => __('Tarjeta de identidad'),
                'DNI' => __('Documento nacional de identificación')
            )
        );                                                                                                                                                                                                                                                                      

        $fields['billing']['billing_dni'] = array(
            'label' => __('DNI', 'subscription-epayco'),
            'placeholder' => _x('Your DNI here....', 'placeholder', 'subscription-epayco'),
            'required' => true,
            'clear' => false,
            'type' => 'number',
            'class' => array('my-css')
        );


        $fields['shipping']['shipping_type_document'] = array(
            'label'       => __('Tipo de documento', 'subscription-epayco'),
            'placeholder' => _x('', 'placeholder', 'subscription-epayco'),
            'required'    => true,
            'clear'       => false,
            'type'        => 'select',
            'default' => 'CC',
            'options'     => array(
                'CC' => __('Cédula de ciudadanía' ),
                'CE' => __('Cédula de extranjería'),
                'PPN' => __('Pasaporte'),
                'SSN' => __('Número de seguridad social'),
                'LIC' => __('Licencia de conducción'),
                'NIT' => __('(NIT) Número de indentificación tributaria'),
                'TI' => __('Tarjeta de identidad'),
                'DNI' => __('Documento nacional de identificación')
            )
        );

        $fields['shipping']['shipping_dni'] = array(
            'label' => __('DNI', 'subscription-epayco'),
            'placeholder' => _x('Your DNI here....', 'placeholder', 'subscription-epayco'),
            'required' => true,
            'clear' => false,
            'type' => 'number',
            'class' => array('my-css')
        );

        return $fields;
}

    public function enqueue_scripts()
    {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();

        if($gateways['subscription_epayco']->enabled === 'yes' && is_checkout()){
            wp_enqueue_script( 'subscription-epayco', $this->plugin_url . 'assets/js/subscription-epayco.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'subscription-epayco-card', $this->plugin_url . 'assets/js/card.js', array( 'jquery' ), $this->version, true );
            wp_localize_script( 'subscription-epayco', 'subscription_epayco', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'country' => WC()->countries->get_base_country(),
                'msgNoCard' => __('The type of card is not accepted','subscription-epayco'),
                'msgEmptyInputs' => __('Enter the card information','subscription-epayco'),
                'msgProcess' => __('Please wait...','subscription-epayco'),
                'msgReturn' => __('Redirecting to verify status...','subscription-epayco'),
                'msgNoCardValidate' => __('Card number, invalid','subscription-epayco'),
                'msgValidateDate' => __('Invalid card expiration date','subscription-epayco')
            ) );
            wp_enqueue_style('frontend-subscription-epayco', $this->plugin_url . 'assets/css/subscription-epayco.css', array(), $this->version, null);
        }
    }

    public function log($message = '')
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);
        $this->logger->add('subscription-epayco', $message);
    }
}