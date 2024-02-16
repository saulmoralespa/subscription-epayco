<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 23/01/19
 * Time: 05:44 AM
 */


wc_enqueue_js( "
    jQuery( function( $ ) {	
	$( '#woocommerce_subscription_epayco_environment' ).change(function(){
		if ( '1' === $( this ).val() ) {
		   $( '#woocommerce_subscription_epayco_docs' ).show();
		   $( '#woocommerce_subscription_epayco_docs + p + p' ).show();
		}else{
		  $( '#woocommerce_subscription_epayco_docs' ).hide();
		  $( '#woocommerce_subscription_epayco_docs + p + p' ).hide();
		}
	}).change();
});	
");

$docs = "<p><strong>Compruebe que su cuenta de ePayco se encuentre en pruebas.</strong> <a target='_blank' href='https://docs.epayco.com/docs/medios-de-pruebas-1'>Tarjetas para pruebas</a></p>";

$docs = array(
    'docs'  => array(
        'title' => __( 'Documentación' ),
        'type'  => 'title',
        'description' => $docs
    )
);

return apply_filters(
    'subscription_epayco_settings',
    array_merge(
      $docs,
        array(
            'enabled' => array(
                'title' => __('Enable/Disable'),
                'type' => 'checkbox',
                'label' => __('Enable Suscription ePayco'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title'),
                'type' => 'text',
                'description' => __('It corresponds to the title that the user sees during the checkout'),
                'default' => __('Subscription ePayco'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description'),
                'type' => 'textarea',
                'description' => __('It corresponds to the description that the user will see during the checkout'),
                'default' => __('Subscription ePayco'),
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug'),
                'type' => 'checkbox',
                'label' => __('Debug records, it is saved in payment log'),
                'default' => 'no'
            ),
            'environment' => array(
                'title' => __('Environment'),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => __('Enable to run tests'),
                'desc_tip' => true,
                'default' => true,
                'options'     => array(
                    false    => __( 'Production' ),
                    true => __( 'Test' ),
                ),
            ),
            'custIdCliente' => array(
                'title' => __('P_CUST_ID_CLIENTE'),
                'type' => 'text',
                'description' => __('La encuentra en el panel de ePayco, integraciones, Llaves API'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => ''
            ),
            'pKey' => array(
                'title' => __('P_KEY'),
                'type' => 'text',
                'description' => __('La encuentra en el panel de ePayco, integraciones, Llaves API'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => ''
            ),
            'apiKey' => array(
                'title' => __('PUBLIC_KEY'),
                'type' => 'text',
                'description' => __('La encuentra en el panel de ePayco, integraciones, Llaves API'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => ''
            ),
            'privateKey' => array(
                'title' => __('PRIVATE_KEY'),
                'type' => 'password',
                'description' => __('La encuentra en el panel de ePayco, integraciones, Llaves API'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => ''
            )
        )
    )
);