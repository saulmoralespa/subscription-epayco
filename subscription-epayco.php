<?php
/*
Plugin Name: Subscription ePayco
Description: Cobros periódicos, suscripciones de ePayco
Version: 4.0.2
Author: Saul Morales Pacheco
Author URI: https://saulmoralespa.com
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: subscription-epayco
Domain Path: /languages/
WC tested up to: 8.6
WC requires at least: 4.0
*/

if (!defined( 'ABSPATH' )) exit;

if(!defined('SUBSCRIPTION_EPAYCO_SE_VERSION')){
    define('SUBSCRIPTION_EPAYCO_SE_VERSION', '4.0.2');
}

add_action('plugins_loaded','subscription_epayco_se_init');
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
        }
    }
);

function subscription_epayco_se_init(): void
{

    load_plugin_textdomain('subscription-epayco', FALSE, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!requeriments_subscription_epayco_se())
        return;

    subscription_epayco_se()->run_epayco();

    if ( get_option( 'subscription_epayco_se_redirect', false ) ) {
        delete_option( 'subscription_epayco_se_redirect' );
        wp_redirect( admin_url( 'admin.php?page=subscription-epayco-install-setp' ) );
    }
}


function subscription_epayco_se_notices( $notice ): void
{
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function requeriments_subscription_epayco_se(): bool
{

    $openssl_warning = __( 'Subscription ePayco: Requiere OpenSSL >= 1.0.1 para instalarse en su servidor' );

    if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() use($openssl_warning) {
                    subscription_epayco_se_notices($openssl_warning);
                }
            );
        }
        return false;
    }

    preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
    if ( empty( $matches[1] ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() use($openssl_warning) {
                    subscription_epayco_se_notices($openssl_warning);
                }
            );
        }
        return false;
    }

    if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() use($openssl_warning) {
                    subscription_epayco_se_notices($openssl_warning);
                }
            );
        }
        return false;
    }

    if ( !in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action(
                'admin_notices',
                function() {
                    subscription_epayco_se_notices('Subscription ePayco: Woocommerce debe estar instalado y activo');
                }
            );
        }
        return false;
    }

    if (!class_exists('WC_Subscriptions')){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $url_docs = 'https://wordpress.org/plugins/subscription-epayco/#%C2%BF%20what%20else%20should%20i%20keep%20in%20mind%2C%20that%20you%20have%20not%20told%20me%20%3F';
            $subs = __( 'Subscription ePayco: Las suscripciones de Woocommerce deben estar instaladas y activas, ') . sprintf(__('<a target="_blank" href="%s">'. __('verificar la documentación para ayuda') .'</a>'), $url_docs);
            add_action(
                'admin_notices',
                function() use($subs) {
                    subscription_epayco_se_notices($subs);
                }
            );
        }
    }

    $shop_currency = get_option('woocommerce_currency');

    if (!in_array($shop_currency, array('USD','COP'))){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $currency = __('Subscription ePayco: Requiere una de estas monedas USD o COP ' )  . sprintf(__('%s' ), '<a href="' . admin_url() . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">' . __('Haga clic aquí para configurar') . '</a>' );
            add_action(
                'admin_notices',
                function() use($currency) {
                    subscription_epayco_se_notices($currency);
                }
            );
        }
        return false;
    }

    return true;
}

function subscription_epayco_se(){
    static $plugin;
    if (!isset($plugin)){
        require_once('includes/class-subscription-epayco-plugin.php');
        $plugin = new Subscription_Epayco_SE_Plugin(__FILE__, SUBSCRIPTION_EPAYCO_SE_VERSION, 'subscription epayco');

    }
    return $plugin;
}

function activate_subscription_epayco_se(): void
{
    global $wpdb;

    $table_subscription_epayco = $wpdb->prefix . 'subscription_epayco';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_subscription_epayco '" ) !== $table_subscription_epayco ) {

        $sql = "CREATE TABLE $table_subscription_epayco (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_id INT(10) NOT NULL,
		ref_payco VARCHAR(60) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

        dbDelta( $sql );
    }

    add_option( 'subscription_epayco_se_redirect', true );
}

register_activation_hook( __FILE__, 'activate_subscription_epayco_se' );