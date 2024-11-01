<?php

/**
 * Description : WooCommerce Extension that can Define different shipping costs for products, based on customers location.
 * Package : Webiwork Shipping Per Product WooCommerce
 * Version : 1.0.0
 * Author : Webiwork
 */


if (!defined('ABSPATH')) {
    exit;
}

class BSWSPP_Shipping_Per_Product extends WC_Shipping_Method
{
    public $sum_of_every_cart_item = 'no';
    public $apply_on_single_cart_item = 'yes';
    public $use_standalone = 'yes';

    /**
     * Constructor. The instance ID is passed to this.
     */
    public function __construct($instance_id = 0)
    {
        $this->id                    = 'bswspp_shipping_per_product';
        $this->instance_id           = absint($instance_id);
        $this->method_title          = __('Shipping Per Product');
        $this->method_description    = __('Per product shipping allows you to WooCommerce Extension that can Define different shipping costs for products, based on customers location.');

        $this->supports              = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );
        $this->instance_form_fields = array(
            'use_standalone' => array(
                'title'         => __('Enable/Disable', 'webiwork-per-product-shipping'),
                'type'             => 'checkbox',
                'label'         => __('Standalone Per Product Shipping Method'),
                'default'         => 'yes',
                'description'     => 'Use this method as standalone option. Other shipping method will be hide.',
            ),
            'title' => array(
                'title'         => __('Method Title', 'webiwork-per-product-shipping'),
                'type'             => 'text',
                'description'     => __('This controls the title which the user sees during checkout.'),
                'default'        => __('Shipping Per Product'),
                'desc_tip'        => true
            ),
            'tax_status' => array(
                'title'         => __('Tax Status', 'webiwork-per-product-shipping'),
                'type'             => 'select',
                'description'     => '',
                'default'         => 'taxable',
                'options'        => array(
                    'taxable'     => __('Taxable', 'webiwork-per-product-shipping'),
                    'none'         => __('None', 'webiwork-per-product-shipping'),
                ),
            ),
            'sum_of_every_cart_item' => array(
                'title'         => __('Enable/Disable', 'webiwork-per-product-shipping'),
                'type'             => 'checkbox',
                'label'         => __('Sum of every cart product shipping cost'),
                'default'         => 'no',
                'description'     => 'Sum of every cart product shipping cost, otherwise higher cart product shipping cost will be apply.',
            ),
            'apply_on_single_cart_item' => array(
                'title'         => __('Enable/Disable', 'webiwork-per-product-shipping'),
                'type'             => 'checkbox',
                'label'         => __('Also applicable if one product in cart is matched'),
                'default'         => 'yes',
                'description'     => 'This shipping method apply if a single product matched in cart. Otherwise other methods will apply.',
            )
        );

        $this->use_standalone = $this->get_option('use_standalone');
        $this->sum_of_every_cart_item = $this->get_option('sum_of_every_cart_item');
        $this->apply_on_single_cart_item = $this->get_option('apply_on_single_cart_item');
        $this->title = $this->get_option('title');
        $this->tax_status = $this->get_option('tax_status');

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * calculate_shipping function.
     * @param array $package (default: array())
     */
    public function calculate_shipping($package = array())
    {
        $_tax           = new WC_Tax();
        $taxes          = array();
        $shipping_cost_arr = array();

        if (count($package['contents']) > 0) {
            foreach ($package['contents'] as $item_id => $values) {
                if ($values['quantity'] > 0) {
                    if ($values['data']->needs_shipping()) {

                        $rule = false;
                        $item_shipping_cost = 0;

                        $rule = bswspp_per_product_shipping_get_matching_rule($values['product_id'], $package, false);

                        if ($rule) {
                            $item_shipping_cost += (float) $rule->rule_item_cost * (int) $values['quantity'];
                            $item_shipping_cost += (float) $rule->rule_cost;

                            array_push($shipping_cost_arr, array('cost' => $item_shipping_cost, 'tax_class' => $values['data']->get_tax_class()));
                        } else {
                            if ($this->apply_on_single_cart_item !== 'yes') {
                                // NO default and nothing found - abort
                                return;
                            }
                        }
                    }
                }
            }
        }

        if (sizeof($shipping_cost_arr) === 0) {
            return;
        }

        $final_price = 0;

        if ($this->sum_of_every_cart_item !== 'yes') {
            // for only topmost price item
            $keys = array_column($shipping_cost_arr, 'cost');
            array_multisort($keys, SORT_DESC, $shipping_cost_arr);

            if (get_option('woocommerce_calc_taxes') == 'yes' && $this->tax_status == 'taxable') {

                $rates      = $_tax->get_shipping_tax_rates($shipping_cost_arr[0]['tax_class']);
                $item_taxes = $_tax->calc_shipping_tax($shipping_cost_arr[0]['cost'], $rates);

                // Sum the item taxes
                foreach (array_keys($taxes + $item_taxes) as $key) {
                    $taxes[$key] = (isset($item_taxes[$key]) ? $item_taxes[$key] : 0) + (isset($taxes[$key]) ? $taxes[$key] : 0);
                }
            }

            $final_price = $shipping_cost_arr[0]['cost'];
        } else {
            //for every item
            foreach ($shipping_cost_arr as $arr) {
                $final_price += $arr['cost'];
            }

            if (get_option('woocommerce_calc_taxes') == 'yes' && $this->tax_status == 'taxable') {
                foreach ($shipping_cost_arr as $arr) {
                    $rates      = $_tax->get_shipping_tax_rates($arr['tax_class']);
                    $item_taxes = $_tax->calc_shipping_tax($arr['cost'], $rates);

                    // Sum the item taxes
                    foreach (array_keys($taxes + $item_taxes) as $key) {
                        $taxes[$key] = (isset($item_taxes[$key]) ? $item_taxes[$key] : 0) + (isset($taxes[$key]) ? $taxes[$key] : 0);
                    }
                }
            }
        }


        $this->add_rate(array(
            'id'    => $this->id . $this->instance_id,
            'label' => $this->title,
            'cost'  => $final_price,
            'taxes' => $taxes // We calc tax in the method
        ));
    }
}
