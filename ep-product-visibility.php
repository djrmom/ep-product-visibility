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
 * Converts term_query on term_taxonomy_id to term_id
 *
 * @param $tax_query
 *
 * @return array
 */
function ep_wc_product_visibility_convert_terms( $tax_query ) {

	return array_map( function( $tax_query_el ) {
		if ( is_array( $tax_query_el ) && isset( $tax_query_el[ 'field' ] ) && 'term_taxonomy_id' == $tax_query_el[ 'field' ] ) {

			$tax_query_el[ 'field' ] = 'term_id';

			if ( ! empty( $tax_query_el[ 'terms' ] ) ) {
				$tax_query_el[ 'terms' ] = array_map( function( $tax_query_term ) {
					if (  $term = get_term_by( 'term_taxonomy_id', $tax_query_term ) ) {
						$tax_query_term = absint( $term->term_id );
					}
					return $tax_query_term;
				}, (array)$tax_query_el[ 'terms' ] );
			}
		}

		return $tax_query_el;

    }, $tax_query );
}

/**
 * Add 'product_visibility' taxonomy to elasticpress
 *
 * @param $taxonomies
 * @param $post
 *
 * @return array
 */
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
