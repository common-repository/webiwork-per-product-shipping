<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$wpdb->hide_errors();

$collate = '';

if ($wpdb->has_cap('collation')) {
    if (!empty($wpdb->charset)) {
        $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
    }
    if (!empty($wpdb->collate)) {
        $collate .= " COLLATE $wpdb->collate";
    }
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$table = $wpdb->prefix . 'bs_woo_shipping_per_product_rules';

// Table for storing rules for products.
$sql = "
CREATE TABLE {$table} (
id bigint(20) NOT NULL auto_increment,
product_id bigint(20) NOT NULL,
rule_zone varchar(100) NOT NULL,
rule_country varchar(10) NOT NULL,
rule_state varchar(10) NOT NULL,
rule_postcode varchar(200) NOT NULL,
rule_cost varchar(200) NOT NULL,
rule_item_cost varchar(200) NOT NULL,
rule_order bigint(20) NOT NULL,
PRIMARY KEY (id)
) $collate;
";
dbDelta($sql);

$table = $wpdb->prefix . 'bs_woo_shipping_per_product_rule_locations';

// Table for storing rule locations for products.
$sql = "
CREATE TABLE {$table} (
id bigint(20) NOT NULL auto_increment,
rule_id bigint(20) NOT NULL,
product_id bigint(20) NOT NULL,
location_code varchar(200) NOT NULL,
location_type varchar(40) NOT NULL,
PRIMARY KEY (id)
) $collate;
";
dbDelta($sql);
