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
 * add product_visibility to index with term_taxonomy_id
 * code modified from https://github.com/10up/ElasticPress/blob/2.1/classes/class-ep-api.php#L599
 *
 * @param $post_args
 * @param $post_id
 *
 * @return mixed
 */
function ep_product_visibility_add_tax( $post_args, $post_id ) {
    if ( 'product' == $post_args[ 'post_type' ] ) {

	    $taxonomy = get_taxonomy( 'product_visibility' );

	    $object_terms = get_the_terms( $post_id, $taxonomy->name );

	    if ( ! $object_terms || is_wp_error( $object_terms ) ) {
		    return $post_args;
	    }

	    $terms_dic = array();

	    foreach ( $object_terms as $term ) {
		    if( ! isset( $terms_dic[ $term->term_id ] ) ) {
			    $terms_dic[ $term->term_id ] = array(
				    'term_id'  => $term->term_id,
				    'slug'     => $term->slug,
				    'name'     => $term->name,
				    'parent'   => $term->parent,
                    'term_taxonomy_id'   => $term->term_taxonomy_id
			    );
		    }
	    }

	    $post_args['terms'][ $taxonomy->name ] = array_values( $terms_dic );

	    return $post_args;
    }
}

/**
 * Setup
 */
function ep_product_visibility_setup() {
	add_filter( 'ep_post_sync_args', 'ep_product_visibility_add_tax', 10, 2 );
}

/**
 * Register the module
 */
add_action( 'plugins_loaded', function() {
	ep_register_feature( 'product_visibility', array(
		'title'                     => 'Woocommerce Product Visibility',
		'setup_cb'                  => 'ep_product_visibility_setup',
		'feature_box_summary_cb'    => 'ep_product_visibility_module_box_summary',
		'feature_box_long_cb'       => 'ep_product_visibility_module_box_long',
		'requirements_status_cb'    => 'ep_product_visibility_dependencies_met_cb',
		'requires_install_reindex'  => true
	) );
} );