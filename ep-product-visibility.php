<?php
/*
 * Plugin Name:  Product Visibility for Woocommerce ElasticPress
 * Description:  Extends ElasticPress to use product_visibility taxonomy for searches
 * License:      GPLv2 or later
*/

/**
 * Output module box summary
 *
 */
function ep_product_visibility_module_box_summary() {
	?>
	<p><?php esc_html_e( 'Fixes product visibility queries in Woocommerce 3.0', 'ep-product-visibility' ); ?></p>
	<?php
}

/**
 * Output module box long
 *
 */
function ep_product_visibility_module_box_long() {
	?>
	<p><?php esc_html_e( 'Fixes product visibility queries in Woocommerce 3.0', 'ep-product-visibility' ); ?></p>
	<?php

}

/**
 * Make sure WC is activated
 *
 * @return bool|WP_Error
 */
function ep_product_visibility_dependencies_met_cb( $status ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		$status->code = 2;
		$status->message = esc_html__( 'WooCommerce not installed.', 'ep-product-visibility' );
	}

	return $status;
}

/**
 * Removes product_visbility tax_queries and recreates them with term_id instead of term_taxonomy_id
 *
 * @param $tax_query
 *
 * @return array
 */
function ep_wc_product_visibility_convert_terms( $tax_query ) {

	/**
	 * unset the product_visibility tax_queries that exist
	 */
	foreach ( $tax_query AS $key => $value ) {

		// if taxonomy is in array and is product_visibility unset this $key
		if ( ! empty( $value['taxonomy'] ) && 'product_visibility' == $value['taxonomy'] ) {
			unset( $tax_query[$key] );
		}

	}

	/**
	 * now recreate product_visibility tax_queries with term_ids instead of term_taxonomy_ids
	 * TODO: woocommerce passes $main_query into the function, not sure is_main_query is appropriate substitute
	 */
	$product_visibility_terms  = ep_wc_product_visibility_get_term_ids();
	$product_visibility_not_in = array( is_search() && is_main_query() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

	// Hide out of stock products.
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
	}

	// Filter by rating.
	if ( isset( $_GET['rating_filter'] ) ) {
		$rating_filter = array_filter( array_map( 'absint', explode( ',', $_GET['rating_filter'] ) ) );
		$rating_terms  = array();
		for ( $i = 1; $i <= 5; $i ++ ) {
			if ( in_array( $i, $rating_filter ) && isset( $product_visibility_terms[ 'rated-' . $i ] ) ) {
				$rating_terms[] = $product_visibility_terms[ 'rated-' . $i ];
			}
		}
		if ( ! empty( $rating_terms ) ) {
			$tax_query[] = array(
				'taxonomy'      => 'product_visibility',
				'field'         => 'term_id',
				'terms'         => $rating_terms,
				'operator'      => 'IN',
				'rating_filter' => true,
			);
		}
	}

	if ( ! empty( $product_visibility_not_in ) ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => $product_visibility_not_in,
			'operator' => 'NOT IN',
		);
	}

	return $tax_query;
}


function ep_wc_product_visibility_get_term_ids() {
	return array_map( 'absint', wp_parse_args(
		wp_list_pluck(
			get_terms( array(
				'taxonomy' => 'product_visibility',
				'hide_empty' => false,
			) ),
			'term_id',
			'name'
		),
		array(
			'exclude-from-catalog' => 0,
			'exclude-from-search'  => 0,
			'featured'             => 0,
			'outofstock'           => 0,
			'rated-1'              => 0,
			'rated-2'              => 0,
			'rated-3'              => 0,
			'rated-4'              => 0,
			'rated-5'              => 0,
		)
	) );
}

function ep_wc_product_visibility_whitelist_taxonomies( $taxonomies, $post ) {

	$woo_taxonomies = array();
	$product_visibility = get_taxonomy( 'product_visibility' );
	$woo_taxonomies[] = $product_visibility;

	return array_merge( $taxonomies, $woo_taxonomies );
}

/**
 * Setup
 */
function ep_product_visibility_setup() {
	add_filter( 'ep_sync_taxonomies', 'ep_wc_product_visibility_whitelist_taxonomies', 11, 2 );
	add_filter( 'woocommerce_product_query_tax_query', 'ep_wc_product_visibility_convert_terms' );
}

/**
 * Register the module
 */
add_action( 'plugins_loaded', function() {
    if ( function_exists('ep_register_feature' ) ) {
	    ep_register_feature( 'product_visibility', array(
		    'title'                     => 'Woocommerce Product Visibility',
		    'setup_cb'                  => 'ep_product_visibility_setup',
		    'feature_box_summary_cb'    => 'ep_product_visibility_module_box_summary',
		    'feature_box_long_cb'       => 'ep_product_visibility_module_box_long',
		    'requirements_status_cb'    => 'ep_product_visibility_dependencies_met_cb',
		    'requires_install_reindex'  => true
	    ) );
    }
} );
