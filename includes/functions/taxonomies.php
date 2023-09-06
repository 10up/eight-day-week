<?php
/**
 * Handles custom taxonomy metaboxes
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Taxonomies;

use Eight_Day_Week\User_Roles as User;

/**
 * Swaps out default taxonomy metabox for a dropdown metabox
 *
 * @param string $tax_slug The register taxonomy slug.
 */
function add_taxonomy_dropdown_meta_box( $tax_slug ) {
	$taxonomy = get_taxonomy( $tax_slug );
	if ( ! $taxonomy ) {
		return;
	}

	// Remove default category type metabox.
	remove_meta_box( $tax_slug . 'div', EDW_PRINT_ISSUE_CPT, 'side' );

	// Add our custom radio box.
	add_meta_box(
		$tax_slug . '_dropdown',
		$taxonomy->labels->singular_name,
		__NAMESPACE__ . '\create_taxonomy_dropdown_metabox',
		EDW_PRINT_ISSUE_CPT,
		'side',
		'default',
		array( 'taxonomy' => $taxonomy )
	);
}

/**
 * Displays a taxonomy dropdown box metabox
 *
 * @param \WP_Post $post The currently edited post.
 * @param array    $metabox Contains args from the add_meta_box func. Important one: pass in a Taxonomy object.
 */
function create_taxonomy_dropdown_metabox( $post, $metabox ) {

	$taxonomy = $metabox['args']['taxonomy'];

	// Bail if invalid taxonomy or not an object.
	if ( ! is_object( $taxonomy ) || ( is_object( $taxonomy ) && ! get_taxonomy( $taxonomy->name ) ) ) {
		return;
	}

	// Uses same noncename as default box so no save_post hook needed.
	wp_nonce_field( 'taxonomy_' . $taxonomy->name, 'taxonomy_noncename' );

	// Get terms associated with this post.
	$names = get_the_terms( get_the_ID(), $taxonomy->name );

	// Get all terms in this taxonomy.
	$terms = (array) get_terms( $taxonomy->name, 'hide_empty=0' );

	if ( ! $terms ) {
		echo '<p>' .
			sprintf(
				/* translators: %s: Name of the taxonomy. */
				esc_html_x( 'No %s created.', 'eight-day-week-print-workflow' ),
				esc_html( $taxonomy->labels->name )
			) .
		'</p>';
		return;
	}

	// Filter the ids out of the terms.
	$existing = ( ! is_wp_error( $names ) && ! empty( $names ) )
		? (array) wp_list_pluck( $names, 'term_id' )
		: array();

	// Check if taxonomy is hierarchical.
	// Terms are saved differently between types.
	$h = $taxonomy->hierarchical;

	// Default value.
	$default_val = $h ? 0 : '';

	// Input name.
	$name = $h ? 'tax_input[' . $taxonomy->name . '][]' : 'tax_input[' . $taxonomy->name . ']';

	$default_text = sprintf(
		/* translators: %s: Singular name of the taxonomy. */
		_x( 'No %s', 'eight-day-week-print-workflow' ),
		esc_html( $taxonomy->labels->singular_name )
	);

	$select        = '';
	$selected_term = false;

	$select .= '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $taxonomy->name ) . '_dropdownlist">';

	if ( count( $terms ) > 1 ) {
		// Default option.
		$select .= '<option value="' . esc_attr( $default_val ) . '"';
		$select .= esc_attr( selected( empty( $existing ), true, false ) );
		$select .= '>' . esc_html( $default_text ) . '</option>';
	}

	// Loop terms and check if they're associated with this post.
	if ( $terms ) {
		foreach ( $terms as $term ) {

			$val = $h ? $term->term_id : $term->slug;

			$select .= '<option value="' . absint( $val ) . '"';

			// If so, they get "checked".
			$selected = ! empty( $existing ) && in_array( (int) $term->term_id, $existing, true ) || count( $terms ) <= 1;
			$select  .= selected( $selected, true, false );

			$select .= '> ' . esc_html( $term->name ) . '</option>';

			// This is used for print prod users.
			if ( $selected ) {
				$selected_term = $term->name;
			}
		}
	}

	$select .= '</select>';

	if ( User\current_user_can_edit_print_issue() ) {
		echo wp_kses(
			$select,
			array(
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
				'select' => array(
					'name' => array(),
					'id'   => array(),
				),
			)
		);
	} else {
		echo '<p> ' . esc_html( $selected_term ? $selected_term : $default_text ) . '</p>';
	}
}
