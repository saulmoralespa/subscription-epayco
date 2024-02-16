<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_subscription_epayco = $wpdb->prefix . 'subscription_epayco';
$sql = "DROP TABLE IF EXISTS $table_subscription_epayco";
$wpdb->query($sql);
$table_name = $wpdb->prefix . 'subscription_epayco_tokens';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);