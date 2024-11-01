<?php

/**
 * Description : Define different shipping costs for products, based on customer location
 * Package : Webiwork Shipping Per Product WooCommerce
 * Version : 1.0.0
 * Author : Webiwork
 */


if (!defined('ABSPATH')) {
	exit;
}

class BSWSPP_Admin
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $wpdb;

		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('woocommerce_product_options_shipping', array($this, 'product_options'));
		add_action('woocommerce_process_product_meta', array($this, 'save'));

		add_action('wp_ajax_nopriv_get_product_shipping_rules', array($this, 'get_product_shipping_rules'));
		add_action('wp_ajax_get_product_shipping_rules', array($this, 'get_product_shipping_rules'));
	}

	public function get_product_shipping_rules()
	{
		global $wpdb;

		// Check for nonce security
		if (!isset($_POST['ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ajax_nonce'])), 'ajax-nonce')) {
			die('Busted!');
		}

		$product_id_from = absint($_POST['product_id_from']);
		$product_id_to = absint($_POST['product_id_to']);

		ob_start();

		//get product rules by id
		$rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules WHERE product_id = %d ORDER BY rule_order;", $product_id_from));

		foreach ($rules as $rule) {
			$rule_locations = $wpdb->get_results($wpdb->prepare("SELECT location_code FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE rule_id = %d;", $rule->id));

			$postcode = array();
			foreach ($rule_locations as $location) {
				$postcode[] = $location->location_code;
			}
?>
			<tr class="custom-fields-row draggable-row">
				<td class="sort draggable-handle">&nbsp;</td>
				<td class="zone draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_zone); ?>" placeholder="*" name="per_product_zone[<?php echo esc_attr($product_id_to); ?>][new][]" /></td>
				<td class="country draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_country); ?>" placeholder="*" name="per_product_country[<?php echo esc_attr($product_id_to); ?>][new][]" /></td>
				<td class="state draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_state); ?>" placeholder="*" name="per_product_state[<?php echo esc_attr($product_id_to); ?>][new][]" /></td>
				<td class="postcode draggable-handle"><textarea placeholder="*" name="per_product_postcode[<?php echo esc_attr($product_id_to); ?>][new][]"><?php echo esc_attr(implode(',', $postcode)); ?></textarea></td>
				<td class="cost draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_cost); ?>" placeholder="0.00" name="per_product_cost[<?php echo esc_attr($product_id_to); ?>][new][]" /></td>
				<td class="item_cost draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_item_cost); ?>" placeholder="0.00" name="per_product_item_cost[<?php echo esc_attr($product_id_to); ?>][new][]" /></td>
			</tr>
		<?php
		}
		$html = ob_get_contents();
		ob_end_clean();
		wp_send_json_success(array('html' => $html));
	}

	/**
	 * Scripts and styles
	 */
	public function admin_enqueue_scripts()
	{
		wp_enqueue_style('wc-shipping-per-product-styles', BSWSPP_PLUGIN_URL . 'assets/css/admin.css');
		wp_register_script('wc-shipping-per-product', BSWSPP_PLUGIN_URL . 'assets/js/shipping-per-product.js', array('jquery'), BSWSPP_VERSION, true);

		wp_localize_script('wc-shipping-per-product', 'BSWSPP_Shipping_Per_Product_params', array(
			'i18n_no_row_selected' => __('No row selected', 'webiwork-per-product-shipping'),
			'i18n_product_id'      => __('Product ID', 'webiwork-per-product-shipping'),
			'i18n_country_code'    => __('Country Code', 'webiwork-per-product-shipping'),
			'i18n_state'           => __('State Code', 'webiwork-per-product-shipping'),
			'i18n_postcode'        => __('Zip Code', 'webiwork-per-product-shipping'),
			'i18n_cost'            => __('Cost', 'webiwork-per-product-shipping'),
			'i18n_item_cost'       => __('Item Cost', 'webiwork-per-product-shipping'),
			'ajax_url'             => admin_url('admin-ajax.php'),
			'ajax_nonce'           => wp_create_nonce('ajax-nonce')
		));
	}

	/**
	 * Output product options
	 */
	public function product_options()
	{
		global $post, $wpdb;

		wp_enqueue_script('jquery-ui-sortable');

		wp_enqueue_script('wc-shipping-per-product');

		echo '</div><div class="options_group per_product_shipping">';

		woocommerce_wp_checkbox(array('id' => '_bswspp_per_product_shipping', 'label' => __('Per-product shipping', 'webiwork-per-product-shipping'), 'description' => __('Enable per-product shipping cost', 'webiwork-per-product-shipping')));

		$this->output_rules();
	}

	/**
	 * Output rules table
	 */
	public function output_rules($post_id = 0)
	{
		global $post, $wpdb;

		if (!$post_id) {
			$post_id = $post->ID;
		}
		?>
		<div class="rules bswspp_per_product_shipping_rules inn_wrap">
			<table class="widefat">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php esc_html_e('Zone Name', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('This is the name of the zone for your reference.', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
						<th><?php esc_html_e('Country Code', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('A 2 digit country code, e.g. US. Leave blank to apply to all.', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
						<th><?php esc_html_e('State Code', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('A state code, e.g. AL. Leave blank to apply to all.', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
						<th><?php esc_html_e('Zip Code', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('Zip Code containing wildcards (e.g. CB23*) or fully numeric ranges (e.g. 90210...99000) are also supported. Leave blank to apply to all areas. For multiple zip code sepreate by them comma (e.g. 90210...99000,CB23*).', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
						<th class="cost"><?php esc_html_e('Line Cost (Excl. Tax)', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('Decimal cost for the line as a whole.', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
						<th class="item_cost"><?php esc_html_e('Item Cost (Excl. Tax)', 'webiwork-per-product-shipping'); ?>&nbsp;<a class="tips" data-tip="<?php esc_html_e('Decimal cost for the item (multiplied by qty).', 'webiwork-per-product-shipping'); ?>">[?]</a></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="7">
							<a href="javascript:;" class="button button-primary insert" data-postid="<?php echo esc_attr($post_id); ?>"><?php esc_html_e('Add row', 'webiwork-per-product-shipping'); ?></a>
							<a href="javascript:;" class="button remove"><?php esc_html_e('Delete row', 'webiwork-per-product-shipping'); ?></a>
							<a href="javascript:;" class="button copy"><?php esc_html_e('Copy from', 'webiwork-per-product-shipping'); ?></a>
							<span id="copy_from_product_id">
								<input class="product-id-input-for-copy" type="text" placeholder="Enter product id" id="product_id" />&nbsp;&nbsp;
								<button type="button" class="button copy-action" data-postid="<?php echo esc_attr($post_id); ?>">Click for copy</button>
							</span>
						</th>
					</tr>
				</tfoot>
				<tbody class="sortable-table-body">
					<?php
					$rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules WHERE product_id = %d ORDER BY rule_order;", $post_id));

					foreach ($rules as $rule) {
						$rule_locations = $wpdb->get_results($wpdb->prepare("SELECT location_code FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE rule_id = %d;", $rule->id));

						$postcode = array();
						foreach ($rule_locations as $location) {
							$postcode[] = $location->location_code;
						}
					?>
						<tr class="custom-fields-row draggable-row">
							<td class="sort draggable-handle">&nbsp;</td>
							<td class="zone draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_zone); ?>" placeholder="*" name="per_product_zone[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]" /></td>
							<td class="country draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_country); ?>" placeholder="*" name="per_product_country[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]" /></td>
							<td class="state draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_state); ?>" placeholder="*" name="per_product_state[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]" /></td>
							<td class="postcode draggable-handle"><textarea placeholder="*" name="per_product_postcode[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]"><?php echo esc_attr(implode(',', $postcode)); ?></textarea></td>
							<td class="cost draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_cost); ?>" placeholder="0.00" name="per_product_cost[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]" /></td>
							<td class="item_cost draggable-handle"><input type="text" value="<?php echo esc_attr($rule->rule_item_cost); ?>" placeholder="0.00" name="per_product_item_cost[<?php echo esc_attr($post_id); ?>][<?php echo esc_attr($rule->id) ?>]" /></td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>
		</div>
<?php
	}

	/**
	 * Sanitize inputs
	 */
	private function array_walk_funtion(&$arrayValue, $arrayKey)
	{
		if ($arrayKey == 'new') {
			foreach ($arrayValue as $newKey => $newValue) {
				$arrayValue[$newKey] = $this->replace_aseterisk(sanitize_text_field($newValue));
			}
		} else {
			$arrayValue = $this->replace_aseterisk(sanitize_text_field($arrayValue));
		}
	}

	/**
	 * Save
	 */
	public function save($post_id)
	{
		global $wpdb;

		// Enabled or Disabled
		if (!empty($_POST['_bswspp_per_product_shipping'])) {
			update_post_meta($post_id, '_bswspp_per_product_shipping', 'yes');
		} else {
			delete_post_meta($post_id, '_bswspp_per_product_shipping');
		}


		$zones  = !empty($_POST['per_product_zone'][$post_id]) ? $_POST['per_product_zone'][$post_id] : [];
		$countries  = !empty($_POST['per_product_country'][$post_id]) ? $_POST['per_product_country'][$post_id] : [];
		$states     = !empty($_POST['per_product_state'][$post_id]) ? $_POST['per_product_state'][$post_id] : [];
		$postcodes  = !empty($_POST['per_product_postcode'][$post_id]) ? $_POST['per_product_postcode'][$post_id] : [];
		$costs      = !empty($_POST['per_product_cost'][$post_id]) ? $_POST['per_product_cost'][$post_id] : [];
		$item_costs = !empty($_POST['per_product_item_cost'][$post_id]) ? $_POST['per_product_item_cost'][$post_id] : [];
		$i = 0;
		$rule_ids = array();

		array_walk($zones, array($this, 'array_walk_funtion'));
		array_walk($countries, array($this, 'array_walk_funtion'));
		array_walk($states, array($this, 'array_walk_funtion'));
		array_walk($postcodes, array($this, 'array_walk_funtion'));
		array_walk($costs, array($this, 'array_walk_funtion'));
		array_walk($item_costs, array($this, 'array_walk_funtion'));

		if ($countries) {
			foreach ($countries as $key => $value) {
				if ($key == 'new') {
					foreach ($value as $new_key => $new_value) {
						if (!empty($countries[$key][$new_key]) || !empty($states[$key][$new_key]) || !empty($postcodes[$key][$new_key]) || !empty($costs[$key][$new_key]) || !empty($item_costs[$key][$new_key])) {
							$postcode = explode(",", $postcodes[$key][$new_key]);
							$postcode_filtered = array_filter($postcode, fn ($val) => trim($val) != "");

							$wpdb->insert(
								$wpdb->prefix . 'bs_woo_shipping_per_product_rules',
								array(
									'rule_zone' 		=> $zones[$key][$new_key],
									'rule_country' 		=> $countries[$key][$new_key],
									'rule_state' 		=> $states[$key][$new_key],
									'rule_cost' 		=> $costs[$key][$new_key],
									'rule_item_cost' 	=> $item_costs[$key][$new_key],
									'rule_order'		=> $i++,
									'product_id'		=> absint($post_id)
								)
							);

							if ($wpdb->insert_id) {
								$rule_id = $wpdb->insert_id;
								$rule_ids[] = $rule_id;

								//insert into rule location table
								$values = array();
								$place_holders = array();

								if (sizeof($postcode_filtered) > 0) {
									$query = "INSERT INTO {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations (rule_id, product_id, location_code, location_type) VALUES ";

									foreach ($postcode_filtered as $code) {
										$code = sanitize_text_field($code);
										$location_type = 'postcode';

										array_push($values, $rule_id, absint($post_id), $code, $location_type);
										$place_holders[] = "('%d', '%d', '%s', '%s')";
									}

									$query .= implode(', ', $place_holders);
									$wpdb->query($wpdb->prepare("$query ", $values));
								}
							}
						}
					}
				} else {
					if (!empty($countries[$key]) || !empty($states[$key]) || !empty($postcodes[$key]) || !empty($costs[$key]) || !empty($item_costs[$key])) {
						$postcode = explode(",", $postcodes[$key]);
						$postcode_filtered = array_filter($postcode, fn ($val) => trim($val) != "");

						$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE product_id = %d AND rule_id = %d;", absint($post_id), absint($key)));

						$wpdb->update(
							$wpdb->prefix . 'bs_woo_shipping_per_product_rules',
							array(
								'rule_zone' 		=> $zones[$key],
								'rule_country' 		=> $countries[$key],
								'rule_state' 		=> $states[$key],
								'rule_cost' 		=> $costs[$key],
								'rule_item_cost' 	=> $item_costs[$key],
								'rule_order'		=> $i++
							),
							array(
								'product_id' 		=> absint($post_id),
								'id'	 		=> absint($key)
							)
						);

						$rule_ids[] = absint($key);

						//insert into rule location table
						$values = array();
						$place_holders = array();

						if (sizeof($postcode_filtered) > 0) {
							$query = "INSERT INTO {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations (rule_id, product_id, location_code, location_type) VALUES ";

							foreach ($postcode_filtered as $code) {
								$code = sanitize_text_field($code);
								$location_type = 'postcode';

								array_push($values, absint($key), absint($post_id), $code, $location_type);
								$place_holders[] = "('%d', '%d', '%s', '%s')";
							}

							$query .= implode(', ', $place_holders);
							$wpdb->query($wpdb->prepare("$query ", $values));
						}
					} else {
						$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules WHERE product_id = %d AND id = %d;", absint($post_id), absint($key)));
						$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE product_id = %d AND rule_id = %d;", absint($post_id), absint($key)));
					}
				}
			}
		}

		if (sizeof($rule_ids) > 0) {
			$rule_id_placeholders = implode(', ', array_fill(0, count($rule_ids), '%d'));
			$prepare_values = array_merge(array(absint($post_id)), $rule_ids);

			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules WHERE product_id = %d AND id NOT IN ($rule_id_placeholders);", $prepare_values));

			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE product_id = %d AND rule_id NOT IN ($rule_id_placeholders);", $prepare_values));
		} else {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rules WHERE product_id = %d;", absint($post_id)));
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}bs_woo_shipping_per_product_rule_locations WHERE product_id = %d;", absint($post_id)));
		}
	}

	/**
	 * Replaces the aseterisks with emtpy string
	 *
	 * @param string $rule
	 * @return string
	 */
	public function replace_aseterisk($rule)
	{
		if (!empty($rule) && '*' === $rule) {
			return '';
		}

		return $rule;
	}
}

new BSWSPP_Admin();
