<?php
/**
 * Helper functions for going from Jigoshop to Woocommerce orders
 */

function grab_jigoshop($order_id) {
    global $wpdb;

    return $wpdb->get_results(
        "SELECT post_id, meta_key, meta_value
        FROM $wpdb->posts
        LEFT JOIN wp_postmeta ON ID=post_id
        WHERE post_type='shop_order' AND post_id = $order_id
    ");
}

function insert_meta($post_id, $key, $value) {
    WP_CLI::log( "Insert meta $post_id $key $value" );
    global $wpdb;
    $wpdb->insert( 
        'wp_postmeta', 
        array( 
            'post_id'       => $post_id,
            'meta_key'      => $key,
            'meta_value'    => $value,
        ), 
        array( 
            '%d',
            '%s', 
            '%s', 
        )
    );
}

function insert_itemmeta($order_item_id, $key, $value) {
    WP_CLI::log( "Insert item meta $order_item_id $key $value" );
    global $wpdb;
    $wpdb->insert( 
        'wp_woocommerce_order_itemmeta', 
        array(
            'order_item_id' => $order_item_id,
            'meta_key'      => $key,
            'meta_value'    => $value,
        ),
        array( 
            '%d',
            '%s', 
            '%s', 
        )
    );
}

function order_item($post_id, $entry) {
    WP_CLI::log( "Insert item $post_id " . $entry['name'] );
    global $wpdb;
    $wpdb->insert( 
        'wp_woocommerce_order_items', 
        array(
            'order_item_name' => $entry['name'],
            'order_item_type' => 'line_item',
            'order_id'        => $post_id,
        ),
        array( 
            '%s',
            '%s', 
            '%d', 
        )
    );

    $order_item_id = $wpdb->insert_id;
    insert_itemmeta($order_item_id, '_product_id', $entry['id']);
    insert_itemmeta($order_item_id, '_variate_id', '0');
    insert_itemmeta($order_item_id, '_qty', $entry['qty']);
    insert_itemmeta($order_item_id, '_tax_class', '');
    insert_itemmeta($order_item_id, '_line_subtotal', $entry['cost']);
    insert_itemmeta($order_item_id, '_line_subtotal_tax', '0');
    insert_itemmeta($order_item_id, '_line_total', $entry['cost']);
    insert_itemmeta($order_item_id, '_line_tax', '0');
    insert_itemmeta($order_item_id, '_line_tax_data', 'a:2:{s:5:"total";a:0:{}s:8:"subtotal";a:0:{}}');
}

function update_post_status($post_id, $new_status) {
    global $wpdb;
    $wpdb->update(
        'wp_posts',
        array( 'post_status' => $new_status ),
        array( 'ID'          => $post_id    )
    );
}
