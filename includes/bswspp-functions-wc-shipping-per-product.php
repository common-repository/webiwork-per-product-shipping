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

/**
 * bswspp_per_product_shipping_get_matching_rule function.
 *
 * @param mixed $product_id
 * @param mixed $package
 * @return false|null
 */
function bswspp_per_product_shipping_get_matching_rule($product_id, $package, $standalone = true)
{
	global $wpdb;

	if (get_post_meta($product_id, '_bswspp_per_product_shipping', true) !== 'yes')
		return false;

	$country 	= $package['destination']['country'];
	$state 		= $package['destination']['state'];
	$postcode 	= $package['destination']['postcode'];

	// Define valid postcodes
	$valid_postcodes 	= array('', $postcode);

	// Work out possible valid wildcard postcodes
	$postcode_length	= strlen($postcode);
	$wildcard_postcode	= $postcode;

	for ($i = 0; $i < $postcode_length; $i++) {
		$wildcard_postcode = substr($wildcard_postcode, 0, -1);
		$valid_postcodes[] = $wildcard_postcode . '*';
	}

	// Rules array

	$matching_rule = $wpdb->get_results(
		$wpdb->prepare(
			"
    		SELECT * FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules
    		WHERE product_id = %d
    		AND rule_country IN ( '', %s )
    		AND rule_state IN ( '', %s )
    		ORDER BY rule_order
    		",
			$product_id,
			strtoupper($country),
			strtoupper($state)
		)
	);

	$rule_ids = array();
	foreach ($matching_rule as $rule) {
		$rule_ids[] = $rule->id;
	}

	$matched_rule = '';

	if (sizeof($rule_ids) > 0) {
		foreach ($rule_ids as $ids) {
			$postcode_locations = $wpdb->get_results($wpdb->prepare("SELECT rule_id, location_code FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE location_type = %s AND rule_id = %d", "postcode", $ids));

			$matches = wc_postcode_location_matcher($postcode, $postcode_locations, 'rule_id', 'location_code');

			if (empty($postcode_locations) || sizeof($matches) > 0) {
				if (sizeof($matches) > 0) {
					$matche_keys = array_keys($matches);
				} else {
					$matche_keys[0] = $ids;
				}

				$matched_rule = $wpdb->get_row(
					$wpdb->prepare(
						"
							SELECT * FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules
							WHERE id = %d
							",
						$matche_keys[0]
					)
				);

				return $matched_rule;
			}
		}
	}

	return $matched_rule;
}
