<?php
/*
Plugin Name: Webiwork Shipping Per Product WooCommerce
Description: WooCommerce Extension that can Define different shipping costs for products, based on customers location.
Author: Webiwork
Author URI: #
Text Domain: webiwork-per-product-shipping
Version: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BSWSPP_Main_Class_Init
{
    /**
     * Constructor
     */
    public function __construct()
    {
        define('BSWSPP_VERSION', '1.0.0');
        define('BSWSPP_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('BSWSPP_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));

        if (is_admin()) {
            include_once BSWSPP_PLUGIN_DIR_PATH . '/includes/bswspp-admin.php';
        }

        include_once BSWSPP_PLUGIN_DIR_PATH . '/includes/bswspp-functions-wc-shipping-per-product.php';

        register_activation_hook(__FILE__, array($this, 'install'));

        add_action('woocommerce_shipping_init', array($this, 'load_shipping_method'));

        add_filter('woocommerce_package_rates', array($this, 'show_only_per_product_shipping_method'), 10, 2);

        add_filter('woocommerce_shipping_methods', array($this, 'register_shipping_method'));
    }

    /**
     * Load shipping method class
     */
    public function load_shipping_method()
    {
        include_once BSWSPP_PLUGIN_DIR_PATH . '/includes/bswspp-class-wc-shipping-per-product.php';
    }

    function show_only_per_product_shipping_method($rates, $package)
    {
        $method_rate = array();
        foreach ($rates as $rate_id => $rate) {
            if ('bswspp_shipping_per_product' === $rate->method_id) {
                $data = get_option('woocommerce_' . $rate->method_id . '_' . $rate->instance_id . '_settings');
                if (isset($data['use_standalone']) && $data['use_standalone'] === 'yes') {
                    $method_rate[$rate_id] = $rate;
                    break;
                }
            }
        }
        return $rates = !empty($method_rate) ? $method_rate : $rates;
    }

    function register_shipping_method($methods)
    {
        $methods['bswspp_shipping_per_product'] = 'BSWSPP_Shipping_Per_Product';
        return $methods;
    }

    /**
     * Installer
     */
    public function install()
    {
        include_once('installer.php');
    }
}
new BSWSPP_Main_Class_Init;
