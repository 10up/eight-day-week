<?php
/**
 * Handles the sections functionality
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Sections;

use Eight_Day_Week\Core as Core;
use Eight_Day_Week\User_Roles as User;
use Eight_Day_Week\Print_Issue as Print_Issue;

	/**
	 * Sections are used as an "in between" p2p relationship
	 * Sections are managed via a metabox on the print issue CPT
	 * They basically serve to group articles within a print issue
	 * The relationship is Print Issue -> Sections -> Articles
	 */

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 */
function setup() {
	/**
	 * A function that returns the fully qualified namespace of a given function.
	 *
	 * @param string $func The name of the function.
	 * @return string The fully qualified namespace of the function.
	 */
	function ns( $func ) {
		return __NAMESPACE__ . "\\$func";
	}

	/**
	 * Add an action hook and associate it with a callback function.
	 *
	 * @param string $func The name of the action hook to add.
	 */
	function a( $func ) {
		add_action( $func, ns( $func ) );
	}

	add_action( 'Eight_Day_Week\Core\init', ns( 'register_post_type' ) );

	a( 'edit_form_after_title' );

	add_action( 'add_meta_boxes_' . EDW_PRINT_ISSUE_CPT, ns( 'add_sections_meta_box' ), 10, 1 );
	add_action( 'edit_form_advanced', ns( 'add_section_output' ) );

	add_action( 'wp_ajax_pp-create-section', ns( 'Section_Factory::create_ajax' ) );
	add_action( 'wp_ajax_pp-update-section-title', ns( 'Section_Factory::update_title_ajax' ) );
	add_action( 'save_print_issue', ns( 'update_print_issue_sections' ), 10, 1 );

	add_action( 'wp_ajax_meta-box-order', ns( 'save_metabox_order' ), 0 );

	add_filter( 'get_user_option_meta-box-order_' . EDW_PRINT_ISSUE_CPT, ns( 'get_section_order' ) );

	add_action( 'edw_section_metabox', ns( 'section_save_button' ), 999 );
}

/**
 * Register section post type
 */
function register_post_type() {

	$args = array(
		'public'   => false,
		'supports' => array(),
	);

	\register_post_type( EDW_SECTION_CPT, $args );
}

/**
 * Outputs information after the print issue title
 *
 * Current outputs:
 * 1. The "Sections" title
 * 2. Error messages for interactions that take place in sections
 * 3. An action with which other parts can hook to output
 *
 * @param object $post The post object.
 */
function edit_form_after_title( $post ) {
	if ( EDW_PRINT_ISSUE_CPT !== $post->post_type ) {
		return;
	}
	echo '<h2>' . esc_html__( 'Sections', 'eight-day-week-print-workflow' ) . '</h2>';
	echo '<p id="pi-section-error" class="pi-error-msg"></p>';
	do_action( 'edw_sections_top' );
}

/**
 * Adds the sections metaboxes
 *
 * When no sections are present for the print issue,
 * this outputs a template for the JS to duplicate when adding the first section
 *
 * @uses add_meta_box
 *
 * @param \WP_Post $post Current post.
 */
function add_sections_meta_box( $post ) {
	$sections = explode( ',', get_sections( $post->ID ) );

	// This is used as a template for duplicating metaboxes via JS.
	// It's also used in metabox saving to retrieve the post ID. So don't remove this!
	array_unshift( $sections, $post->ID );

	$i = 0;

	foreach ( (array) $sections as $section_id ) {

		// Only allow 0 on first pass.
		if ( $i > 0 && ! $section_id ) {
			continue;
		}

		$section_id = absint( $section_id );
		if ( 0 === $i || get_post( $section_id ) ) {

			// The "template" is used in metabox saving to retrieve the post ID. So don't remove this!
			// Don't change the ID either; it's what designates it to retreive the post ID.
			$id = ( 0 === $i ) ? "pi-sections-template-{$section_id}" : "pi-sections-box-{$section_id}";
			add_meta_box(
				$id,
				( 0 === $i ? 'Template' : get_the_title( $section_id ) ),
				__NAMESPACE__ . '\\sections_meta_box',
				EDW_PRINT_ISSUE_CPT,
				'normal',
				'high',
				array(
					'section_id' => $section_id,
				)
			);
		}
		++$i;
	}
}

/**
 * Callback for the section metabox
 *
 * Outputs:
 * 1. An action for 3rd party output
 * 2. The hidden input for the current section ID
 * 3. A button to delete the section
 *
 * @param mixed $post The post.
 * @param mixed $args Metabox arguments.
 */
function sections_meta_box( $post, $args ) {
	$section_id = $args['args']['section_id'];
	do_action( 'edw_section_metabox', $section_id );

	if ( User\current_user_can_edit_print_issue() ) :
		?>
		<input type="hidden" class="section_id" name="section_id" value="<?php echo absint( $section_id ); ?>"/>
		<p class="pi-section-delete">
			<a href="#"><?php esc_html_e( 'Delete section', 'eight-day-week-print-workflow' ); ?></a>
		</p>
		<?php
	endif;
}

/**
 * Gets the sections for the provided print issue
 *
 * @param int $post_id The current post's ID.
 *
 * @return string Comma separated section IDs, or an empty string
 */
function get_sections( $post_id ) {
	$section_ids = get_post_meta( $post_id, 'sections', true );
	// Sanitize - only allow comma delimited integers.
	if ( ! ctype_digit( str_replace( ',', '', $section_ids ) ) ) {
		return '';
	}

	return $section_ids;
}

/**
 * Outputs controls to add a section
 *
 * Also outputs the hidden input containing the print issue's section ids
 * This is necessary to save the sections to the print issue
 *
 * @todo Consider how to better save sections to print issues, or perhaps even do away with the p2p2p (print issue -> section -> post) relationship
 *
 * @param \WP_Post $post The current post.
 */
function add_section_output( $post ) {
	if (
		EDW_PRINT_ISSUE_CPT !== $post->post_type ||
		! User\current_user_can_edit_print_issue()
	) {
		return;
	}

	$section_ids = get_sections( $post->ID );

	?>
	<button
		class="button button-secondary"
		id="pi-section-add"><?php esc_html_e( 'Add Section', 'eight-day-week-print-workflow' ); ?>
	</button>
	<div id="pi-section-add-info">
		<input
			type="text"
			name="pi-section-name"
			id="pi-section-name"
			placeholder="<?php esc_attr_e( 'Enter a name for the new section.', 'eight-day-week-print-workflow' ); ?>"
			/>
		<button
			title="<?php esc_attr_e( 'Click to confirm', 'eight-day-week-print-workflow' ); ?>"
			id="pi-section-add-confirm"
			class="button button-secondary dashicons dashicons-yes"></button>
	</div>
	<input
		type="hidden"
		name="pi-section-ids"
		id="pi-section-ids"
		value="<?php echo esc_attr( $section_ids ); ?>"
		/>
	<?php
}

/**
 * Saves sections to the print issue, and deletes removed ones
 *
 * @todo Consider handling this via ajax so that sections are added to/removed from a print issue immediately.
 * @todo Otherwise, if one adds a section and leaves the post without saving it, orphaned sections pollute the DB, which ain't good.
 *
 * @param int $post_id The print issue post ID.
 */
function update_print_issue_sections( $post_id ) {

	if ( ! isset( $_POST['pi-section-ids'] ) ) {
		return;
	}

	$section_ids = sanitize_text_field( wp_unslash( $_POST['pi-section-ids'] ) );

	$existing = get_sections( $post_id );
	$delete   = array_diff( explode( ',', $existing ), explode( ',', $section_ids ) );
	if ( $delete ) {
		foreach ( $delete as $id ) {
			wp_delete_post( absint( $id ), true );
		}
	}

	set_print_issue_sections( $section_ids, $post_id );
}

/**
 * Saves section IDs to the DB
 *
 * @param string $section_ids Comma separated section IDs.
 * @param int    $print_issue_id The Print Issue post ID.
 */
function set_print_issue_sections( $section_ids, $print_issue_id ) {

	// Sanitize - only allow comma delimited integers.
	if ( ! ctype_digit( str_replace( ',', '', $section_ids ) ) ) {
		return;
	}

	update_post_meta( $print_issue_id, 'sections', $section_ids );

	// Allow other parts to hook.
	do_action( 'save_print_issue_sections', $print_issue_id, $section_ids );
}

/**
 * Class Section_Factory
 *
 * @package Eight_Day_Week\Sections
 *
 * Factory that creates + updates sections
 *
 * @todo Refactor this (possibly trash it). Was just an experiment, really.
 */
class Section_Factory {

	/**
	 * Creates a section
	 *
	 * @param string $name The name of the section (title).
	 *
	 * @return int|Section|\WP_Error
	 */
	public static function create( $name ) {

		$info       = array(
			'post_title' => $name,
			'post_type'  => EDW_SECTION_CPT,
		);
		$section_id = wp_insert_post( $info );
		if ( $section_id ) {
			return new Section( $section_id );
		}

		return $section_id;
	}

	/**
	 * Assigns a section to a print issue.
	 *
	 * @param mixed $section The section to be assigned.
	 * @param mixed $print_issue The print issue to assign the section to.
	 * @return mixed The updated sections.
	 */
	public static function assign_to_print_issue( $section, $print_issue ) {
		$current_sections = get_sections( $print_issue->ID );
		$new_sections     = $current_sections ? $current_sections . ',' . $section->ID : $section->ID;
		set_print_issue_sections( $new_sections, $print_issue->ID );
		return $new_sections;
	}

	/**
	 * Creates an AJAX request handler for creating a section.
	 *
	 * @throws \Exception When the print issue ID is invalid or an exception is thrown during section creation.
	 */
	public static function create_ajax() {

		Core\check_elevated_ajax_referer();

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : false;
		if ( ! $name ) {
			Core\send_json_error( array( 'message' => __( 'Please enter a section name.', 'eight-day-week-print-workflow' ) ) );
		}

		$print_issue_id = isset( $_POST['print_issue_id'] ) ? absint( $_POST['print_issue_id'] ) : false;

		$print_issue = get_post( $print_issue_id );
		if ( ! $print_issue ) {
			throw new \Exception( 'Invalid print issue specified.' );
		}

		try {
			$section = self::create( $name );
		} catch ( \Exception $e ) {
			// Let the whoops message run its course.
			$section = null;
		}

		if ( $section instanceof Section ) {
			self::assign_to_print_issue( $section, $print_issue );
			Core\send_json_success( array( 'section_id' => $section->ID ) );
		}

		Core\send_json_error( array( 'message' => __( 'Whoops! Something went awry.', 'eight-day-week-print-workflow' ) ) );
	}

	/**
	 * Handles an ajax request to update a section's title
	 *
	 * @todo refactor to use exceptions and one json response vs pepper-style
	 */
	public static function update_title_ajax() {

		Core\check_elevated_ajax_referer();

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : false;
		if ( ! $title ) {
			Core\send_json_error( array( 'message' => __( 'Please enter a section name.', 'eight-day-week-print-workflow' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : false;
		if ( ! $post_id ) {
			Core\send_json_error( array( 'message' => __( 'Whoops! This section appears to be invalid.', 'eight-day-week-print-workflow' ) ) );
		}
		try {
			self::update_title( $title, $post_id );
		} catch ( \Exception $e ) {
			Core\send_json_error( array( 'message' => $e->getMessage() ) );
		}
		Core\send_json_success();
	}

	/**
	 * Updates a section's title
	 *
	 * @param string $title The new title.
	 * @param int    $id The section ID.
	 */
	public static function update_title( $title, $id ) {
		$section = new Section( $id );
		$section->update_title( $title );
	}
}

/**
 * Class Section
 *
 * @package Eight_Day_Week\Sections
 *
 * Class that represents a section object + offers utility functions for it
 *
 * @todo Refactor this (possibly trash it). Was just an experiment, really.
 */
class Section {

	/**
	 * Section post ID
	 *
	 * @var int The section's post ID
	 */
	public $ID;

	/**
	 * The section post object
	 *
	 * @var \WP_Post The section's post
	 */
	private $_post;

	/**
	 * Ingests a section based on a post ID
	 *
	 * @param int $id The section's post ID.
	 */
	public function __construct( $id ) {
		$this->ID = absint( $id );
		$this->import_post();
		$this->import_post_info();
	}

	/**
	 * Sets the object's _post property
	 *
	 * @throws \Exception Invalid post ID supplied.
	 */
	private function import_post() {
		$post = get_post( $this->ID );
		if ( ! $post instanceof \WP_Post ) {
			throw new \Exception( __( 'Invalid post ID supplied', 'eight-day-week-print-workflow' ) );
		}
		$this->_post = $post;
	}

	/**
	 * Ingests the \WP_Post
	 * by duplicating its properties to this object's properties
	 *
	 * @todo Refactor away, unnecessary to have/perform
	 */
	private function import_post_info() {

		$info = $this->_post;

		if ( is_object( $info ) ) {
			$info = get_object_vars( $info );
		}
		if ( is_array( $info ) ) {
			foreach ( $info as $key => $value ) {
				if ( ! empty( $key ) ) {
					$this->$key = $value;
				} elseif ( ! empty( $key ) && ! method_exists( $this, $key ) ) {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Updates the section
	 *
	 * @uses wp_update_post
	 * @todo Refactor away, just use wp_update_post
	 *
	 * @param mixed $args The arguments to update the post.
	 * @throws \Exception Failed to update section %d.
	 * @return mixed The result of updating the post.
	 */
	public function update( $args ) {
		$result = wp_update_post( $args );
		if ( $result ) {
			return $result;
		}
		/* translators: %d: The ID of the section that failed to update. */
		throw new \Exception( sprintf( esc_html__( 'Failed to update section %d', 'eight-day-week-print-workflow' ), $this->ID ) );
	}

	/**
	 * Updates a section's title
	 *
	 * @param string $title The new title to set.
	 * @throws \Exception If the title is empty or invalid.
	 * @return void
	 */
	public function update_title( $title ) {
		if ( ! $title ) {
			throw new \Exception( __( 'Please supply a valid, non-empty title', 'eight-day-week-print-workflow' ) );
		}
		$title  = sanitize_text_field( $title );
		$args   = array(
			'ID'         => $this->ID,
			'post_title' => $title,
		);
		$result = $this->update( $args );
	}
}

/**
 * Override the default metabox order for PI CPT
 *
 * By default, metabox order is stored per user, per "$page"
 * We want per post, and not per user.
 * This stores the metabox in post meta instead, allowing cross-user order storage
 */
function save_metabox_order() {
	check_ajax_referer( 'meta-box-order' );
	$order = isset( $_POST['order'] ) ? (array) array_map( 'sanitize_text_field', ( wp_unslash( $_POST['order'] ) ) ) : false;

	if ( ! $order ) {
		return;
	}

	$page = isset( $_POST['page'] ) ? wp_unslash( $_POST['page'] ) : '';

	if ( sanitize_key( $page ) !== $page ) {
		wp_die( 0 );
	}

	// Only intercept PI CPT.
	if ( EDW_PRINT_ISSUE_CPT !== $page ) {
		return;
	}

	$user = wp_get_current_user();
	if ( ! $user ) {
		wp_die( -1 );
	}

	// Don't allow print prod users to re-order.
	if ( ! User\current_user_can_edit_print_issue() ) {
		wp_die( -1 );
	}

	// Grab the post ID from the section template.
	$metaboxes = explode( ',', $order['normal'] );
	$template  = false;
	foreach ( $metaboxes as $metabox ) {
		if ( strpos( $metabox, 'template' ) !== false ) {
			$template = $metabox;
		}
	}

	// Couldnt find PI template, which contains PI post ID.
	if ( ! $template ) {
		return;
	}

	$parts   = explode( '-', $template );
	$post_id = end( $parts );

	$post = get_post( $post_id );

	if ( ! $post || ( $post ) && EDW_PRINT_ISSUE_CPT !== $post->post_type ) {
		return;
	}

	update_post_meta( $post_id, 'section-order', $order );

	wp_die( 1 );
}

/**
 * Gets the order of sections for a print issue
 *
 * @param string $result The incoming order.
 *
 * @return mixed Modified order, if found in post meta, else the incoming value
 */
function get_section_order( $result ) {
	global $post;

	$order = get_post_meta( $post->ID, 'section-order', true );
	if ( $post && $order ) {
		return $order;
	}

	return $result;
}

/**
 * Outputs a Save button
 */
function section_save_button() {
	if ( Print_Issue\is_read_only_view() || ! User\current_user_can_edit_print_issue() ) {
		return;
	}
	echo '<button class="button button-primary">' . esc_html__( 'Save', 'eight-day-week-print-workflow' ) . '</button>';
}
