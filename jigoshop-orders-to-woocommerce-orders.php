<?php
/*
Plugin Name: Jigoshop Orders to WooCommerce Orders
Plugin URI: https://falkus.co
Description: Convert Jigoshop orders to WooCommerce orders.
Author: Martin Falkus
Author URI: https://falkus.co
Version: 0.1
*/

include 'includes/helper-functions.php';

function jigo_woo_convert_orders() {

    if (get_option('jigo_woo_order_conversion')) {
        echo '<h2>The orders have already been converted</h2>';
        echo '<p>This plugin can now be disabled and deleted.</p>';
        return;
    }

    global $wpdb;
    $jigoshop_ids = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT post_id
            FROM wp_postmeta
            WHERE meta_key = 'order_data'
                AND post_id > %d
            ORDER BY post_id ASC",
            get_option('jigo_woo_last_id', -1)
        )
    );

    // Might be a part-complete run

    $count = 0;
    echo "<ul>";
    foreach ($jigoshop_ids as $post_id_obj) {
        $post_id = $post_id_obj->post_id;

        $results = grab_jigoshop( $post_id );
        foreach ($results as $meta_entry) {
            $meta_key = $meta_entry->meta_key;
            $meta_value = $meta_entry->meta_value;

            if ($meta_key === 'order_key') {
                insert_meta($post_id, '_order_key', $meta_value);
            }
            if ($meta_key === 'customer_user') {
                insert_meta($post_id, '_customer_user', $meta_value);
            }

            if ($meta_key === 'order_data') {
                $order_data = unserialize($meta_entry->meta_value);
                insert_meta($post_id, '_billing_first_name', $order_data['billing_first_name'] );
                insert_meta($post_id, '_billing_last_name', $order_data['billing_last_name'] );
                insert_meta($post_id, '_billing_company', $order_data['billing_company'] );
                insert_meta($post_id, '_billing_address_1', $order_data['billing_address_1'] );
                insert_meta($post_id, '_billing_address_2', $order_data['billing_address_2'] );
                insert_meta($post_id, '_billing_city', $order_data['billing_city'] );
                insert_meta($post_id, '_billing_state', $order_data['billing_state'] );
                insert_meta($post_id, '_billing_postcode', $order_data['billing_postcode'] );
                insert_meta($post_id, '_billing_country', $order_data['billing_country'] );
                insert_meta($post_id, '_billing_email', $order_data['billing_email'] );
                insert_meta($post_id, '_billing_phone', $order_data['billing_phone'] );
                insert_meta($post_id, '_billing_address_index', implode(' ', array(
                    $order_data['billing_first_name'],
                    $order_data['billing_last_name'],
                    $order_data['billing_company'],
                    $order_data['billing_address_1'],
                    $order_data['billing_address_2'],
                    $order_data['billing_city'],
                    $order_data['billing_state'],
                    $order_data['billing_postcode'],
                    $order_data['billing_country'],
                    $order_data['billing_email'],
                    $order_data['billing_phone'],
                )));

                insert_meta($post_id, '_shipping_first_name', $order_data['shipping_first_name'] );
                insert_meta($post_id, '_shipping_last_name', $order_data['shipping_last_name'] );
                insert_meta($post_id, '_shipping_company', $order_data['shipping_company'] );
                insert_meta($post_id, '_shipping_address_1', $order_data['shipping_address_1'] );
                insert_meta($post_id, '_shipping_address_2', $order_data['shipping_address_2'] );
                insert_meta($post_id, '_shipping_city', $order_data['shipping_city'] );
                insert_meta($post_id, '_shipping_state', $order_data['shipping_state'] );
                insert_meta($post_id, '_shipping_postcode', $order_data['shipping_postcode'] );
                insert_meta($post_id, '_shipping_country', $order_data['shipping_country'] );
                insert_meta($post_id, '_shipping_address_index', implode(' ', array(
                    $order_data['shipping_first_name'],
                    $order_data['shipping_last_name'],
                    $order_data['shipping_company'],
                    $order_data['shipping_address_1'],
                    $order_data['shipping_address_2'],
                    $order_data['shipping_city'],
                    $order_data['shipping_state'],
                    $order_data['shipping_postcode'],
                    $order_data['shipping_country'],
                )));

                insert_meta($post_id, '_payment_method', $order_data['payment_method'] );
                insert_meta($post_id, '_payment_method_title', $order_data['payment_method_title'] );
                insert_meta($post_id, '_order_shipping_tax', $order_data['order_shipping_tax'] );
                insert_meta($post_id, '_order_shipping', $order_data['order_shipping'] );

                insert_meta($post_id, '_order_tax', $order_data['order_tax'] );
                insert_meta($post_id, '_order_total', $order_data['order_total'] );

                insert_meta($post_id, '_shipping_method', $order_data['shipping_method'] );

                // Extras
                insert_meta($post_id, '_transaction_id', '' );

                // Extras - definitely not required for now
                // insert_meta($post_id, '_cart_hash', '' );
            }
            if ($meta_entry->meta_key === 'order_items') {
                // Coming soon
                $order_items = unserialize($meta_entry->meta_value);
                foreach ($order_items as $entry) {
                    order_item($post_id, $entry);
                }
            }

            if ($meta_entry->meta_key === '_custom_billing_fields') {
                $custom = unserialize($meta_entry->meta_value);
                insert_meta($post_id, 'Delivery Date',      $custom['preferred-delivery-date']['value'] );
                insert_meta($post_id, 'Personal Message',   $custom['message-on-card']['value'] );
            }
        }

        // Update post_status to wc-completed here
        update_post_status($post_id, 'wc-completed');

        update_option('jigo_woo_last_id', $post_id);
        echo( "<li>Updated order $post_id</li>" );

        $count++;
    }
    echo "<ul>";

    add_option('jigo_woo_order_conversion', 1);
}
add_action( 'wp_head', 'jigo_woo_convert_orders', 50 );
