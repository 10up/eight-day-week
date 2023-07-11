<?php
/**
 * Handles the article export functionality
 *
 * @package eight-day-week
 */

namespace Eight_Day_Week\Plugins\Article_Export;

use Eight_Day_Week\Core as Core;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {

	add_action(
		'Eight_Day_Week\Core\plugin_init',
		function () {

			function ns( $function ) {
				return __NAMESPACE__ . "\\$function";
			}

			function a( $function ) {
				add_action( $function, ns( $function ) );
			}
			add_action( 'edw_sections_top', ns( 'output_article_export_buttons' ), 11 );
			add_action( 'wp_ajax_pp-article-export', ns( 'export_articles' ) );

			add_action( 'wp_ajax_pp-article-export-update', ns( 'article_status_response' ) );

			// add export status column
			add_filter( 'Eight_Day_Week\Articles\article_columns', ns( 'article_export_status_column' ), 10 );

			// retrieve export status + build string for output
			add_filter( 'Eight_Day_Week\Articles\article_meta_export_status', ns( 'filter_export_status' ), 10, 2 );

			add_filter( __NAMESPACE__ . '\short_circuit_article_export_files', ns( 'maybe_export_article_attachments' ), 10, 4 );

			add_filter( __NAMESPACE__ . '\xml_filename', ns( 'filter_xml_filename_author' ), 10, 3 );
		}
	);
}

/**
 * Output export buttons!
 */
function output_article_export_buttons() {
	?>
	<div class="alignleft actions bulkactions article-export-buttons">
		<h3><?php esc_html_e( 'Export for InDesign', 'eight-day-week-print-workflow' ); ?></h3>
		<button id="article-export-checked" class="button button-secondary"><?php esc_html_e( 'Export checked', 'eight-day-week-print-workflow' ); ?></button>
		<button id="article-export-all" class="button button-secondary"><?php esc_html_e( 'Export all', 'eight-day-week-print-workflow' ); ?></button>
	</div>
	<?php
}

/**
 * Big ol' export article controller
 */
function export_articles() {

	Core\check_ajax_referer();

	if ( ! isset( $_POST['article_ids'] ) ) {
		die( __( 'No article IDs sent', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = $_POST['article_ids'];

	$print_issue_id    = absint( $_POST['print_issue_id'] );
	$print_issue_title = sanitize_text_field( $_POST['print_issue_title'] );

	// sanitize - only allow comma delimited integers
	if ( ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
		die( __( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = explode( ',', $article_ids );

	do_action( __NAMESPACE__ . '\before_export_articles', $article_ids, $print_issue_id, $print_issue_title );

	try {
		$factory = new Article_Zip_Factory( $article_ids, $print_issue_id, $print_issue_title );
	} catch ( \Exception $e ) {
		Core\send_json_error( $e->getMessage() );
		return;
	}

	try {
		$factory->output_zip();
	} catch ( \Exception $e ) {
		Core\send_json_error( $e->getMessage() );
	}

	do_action( __NAMESPACE__ . '\_after_export_articles', $article_ids, $print_issue_id, $print_issue_title );
}

/**
 * Adds exports status to the list table columns
 *
 * @param array $columns
 *
 * @return array Modified columns
 */
function article_export_status_column( $columns ) {
	$columns['export_status'] = __( 'Export Status', 'eight-day-week-print-workflow' );
	return $columns;
}

/**
 * Updates post meta with export status and retrieves the built status string
 *
 * @param $article_ids array Post IDs
 *
 * @uses set_export_status
 * @uses get_export_status
 *
 * @return string The export status
 */
function set_and_get_export_status( $article_ids ) {
	set_export_status( $article_ids );
	return get_export_status( reset( $article_ids ) );
}

/**
 * Manages the ajax response for setting/getting article export status
 */
function article_status_response() {
	Core\check_ajax_referer();
	try {
		$export_status = article_status_handler();
		Core\send_json_success( array( 'export_status' => $export_status ) );
	} catch ( \Exception $e ) {
		Core\send_json_error();
	}
}

/**
 * Pulls data from the http request and sets/gets article export status
 *
 * @uses set_and_get_export_status
 *
 * @return string The export status
 * @throws \Exception Points of failure in the process
 */
function article_status_handler() {

	if ( ! isset( $_POST['article_ids'] ) ) {
		throw new \Exception( __( 'No article IDs sent', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = $_POST['article_ids'];

	// sanitize - only allow comma delimited integers
	if ( ! ctype_digit( str_replace( ',', '', $article_ids ) ) ) {
		throw new \Exception( __( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
	}

	$article_ids = explode( ',', $article_ids );

	return set_and_get_export_status( $article_ids );
}

/**
 * Updates each exported article with meta
 * about who last exported it, and when
 *
 * @param array $article_ids IDs of articles that were exported
 */
function set_export_status( $article_ids ) {
	$article_ids = array_map( 'absint', $article_ids );
	foreach ( $article_ids as $article_id ) {
		update_post_meta( $article_id, 'export_status_timestamp', time() );
		update_post_meta( $article_id, 'export_status_user_id', get_current_user_id() );
	}
}

/**
 * Gets the article's export status
 *
 * @param $incoming bool The incoming meta value (false by default)
 * @param $article \WP_Post The current post
 *
 * @return string The article's export status, or the incoming value
 */
function filter_export_status( $incoming, $article ) {
	$status = get_export_status( $article->ID );
	return $status ? $status : $incoming;
}

/**
 * Builds the export status string
 *
 * @param int $article_id The article post ID
 *
 * @return string The export status
 */
function get_export_status( $article_id ) {

	$timestamp = get_post_meta( $article_id, 'export_status_timestamp', true );

	if ( ! $timestamp ) {
		return false;
	}

	$user_id = get_post_meta( $article_id, 'export_status_user_id', true );
	$user    = get_user_by( 'id', absint( $user_id ) );

	$zone = new \DateTimeZone( Core\get_timezone() );

	$export_datetime = new \DateTime( 'now', $zone );
	$export_datetime->setTimestamp( $timestamp );

	$now = new \DateTime( 'now', $zone );

	$not_today = $export_datetime->diff( $now )->format( '%R%a' );

	if ( ! $not_today ) {
		$export_status = sprintf( __( 'Exported on %1$s by %2$s', 'eight-day-week-print-workflow' ), $export_datetime->format( get_option( 'date_format' ) ), $user->display_name );
	} else {
		$export_status = sprintf( __( 'Exported at %1$s by %2$s', 'eight-day-week-print-workflow' ), $export_datetime->format( _x( 'g:ia', 'Format for article export timestamp', 'eight-day-week' ) ), $user->display_name );
	}

	return $export_status;
}

/**
 * Class Article_Zip_Factory
 *
 * @package Eight_Day_Week\Plugins\Article_Export
 *
 * Factory for building an export zip for an article
 *
 * @todo Pull out XML-related functions so this class is for a generic ZIP;
 * @todo Perhaps introduce a fallback filter vs explicitly requesting an XML file fallback
 */
class Article_Zip_Factory {

	/**
	 * @var int[] Article IDs
	 */
	private $ids;

	/**
	 * @var \WP_Post[] Articles
	 */
	var $articles;

	/**
	 * @var File[] Files for all articles
	 */
	var $files;

	/**
	 * @var array Images for all articles
	 */
	var $images;

	/**
	 * Sets up object properties
	 *
	 * @param array  $ids Array of post IDs
	 * @param int    $print_issue_id ID of parent print issue
	 * @param string $print_issue_title Title of parent print issue (to avoid lookup)
	 *
	 * @throws \Exception Various points of failure in constructing the factory
	 */
	function __construct( $ids, $print_issue_id, $print_issue_title ) {

		$article_ids = array_filter( $ids, 'is_numeric' );
		if ( count( $ids ) !== count( $article_ids ) ) {
			throw new \Exception( __( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
		}

		$this->ids               = $ids;
		$this->print_issue_id    = absint( $print_issue_id );
		$this->print_issue_title = sanitize_text_field( $print_issue_title );
	}

	/**
	 * Builds an array of WP_Post objects via the object's set of IDs
	 *
	 * @return \WP_Post[]
	 */
	function import_articles() {
		$articles = array();
		foreach ( $this->ids as $id ) {
			$article = get_post( $id );
			if ( $article ) {
				$articles[ $id ] = $article;
			}
		}

		return $articles;
	}

	/**
	 * Gets the object's set of \WP_Posts
	 *
	 * @uses import_articles
	 *
	 * @return \WP_Post[]
	 */
	function get_articles() {
		if ( $this->articles ) {
			return $this->articles;
		}

		return $this->articles = $this->import_articles();
	}

	/**
	 * Gets export files for an article
	 *
	 * Provides a filter so that 3rd parties can hook in
	 * and determine what files to export vs the standard XML.
	 *
	 * @return array File[] Set of export files
	 */
	function build_file_sets() {

		$articles = $this->get_articles();

		$file_sets = array();
		foreach ( $articles as $article ) {

			// allow articles to export an alternative file
			$files = apply_filters( __NAMESPACE__ . '\short_circuit_article_export_files', false, $article, $this->print_issue_id, $this->print_issue_title );

			// but fall back to XML
			if ( ! $files ) {
				$files = $this->get_xml_file( $article );
			}

			$file_sets[ $article->ID ] = $files;

		}

		return $file_sets;
	}

	/**
	 * Gets an xml export file for an article
	 *
	 * @param $article \WP_Post the current post
	 *
	 * @return File The XML file for the provided post
	 */
	function get_xml_file( $article ) {
		$xml = $this->get_xml( $article );

		$file_name  = apply_filters( __NAMESPACE__ . '\xml_filename', $xml->root_element->getAttribute( 'title' ), $article, $xml );
		$file_name .= '.xml';

		$file_contents = $xml->xml_document->saveXML();

		$fileset   = array();
		$fileset[] = new File( $file_contents, apply_filters( __NAMESPACE__ . '\xml_full_filename', $file_name, $article ) );

		return $fileset;
	}

	/**
	 * Builds and returns an XML file for an article
	 *
	 * @param $article \WP_Post the current post
	 *
	 * @return object Contains properties of a DOMDocument object for easy access
	 * @throws \Exception
	 */
	function get_xml( $article ) {
		$xml                          = new Article_XML( $article );
		$xmlContent                   = $xml->build_xml();
		$this->images[ $article->ID ] = $xml->images;
		return $xmlContent;
	}

	/**
	 * Gets a set of files for the current set of articles
	 *
	 * @return File[] Set of files
	 */
	function get_file_sets() {
		if ( $this->files ) {
			return $this->files;
		}

		return $this->files = $this->build_file_sets();
	}

	function output_zip() {
		// prepare folder
		$tmp_folder = 'export_xml_' . get_current_user_id();

		if ( ! is_dir( get_temp_dir() . $tmp_folder ) ) {
			mkdir( get_temp_dir() . $tmp_folder );
		}

		// create zip
		$tmp_zip_file = get_temp_dir() . $tmp_folder . '/output.zip';
		if ( file_exists( $tmp_zip_file ) ) {
			unlink( $tmp_zip_file );
		}
		$zip  = new \ZipArchive( $this->print_issue_title );
		$code = $zip->open( $tmp_zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

		$file_sets = $this->get_file_sets();

		$sub_folders = array();
		foreach ( $file_sets as $article_id => $files ) {
			foreach ( $files as $file ) {
				if ( stripos( $file->filename, '.xml' ) !== false ) {
					$filename                   = explode( '.', $file->filename );
					$sub_folders[ $article_id ] = $filename[0];
					break;
				}
			}

			if ( ! $sub_folders[ $article_id ] ) {
				$sub_folders[ $article_id ] = $article_id;
			}
		}

		// add xml files
		foreach ( $file_sets as $article_id => $files ) {

			$zip->addEmptyDir( $sub_folders[ $article_id ] );

			// force an array
			$files = is_array( $files ) ? $files : array( $files );
			foreach ( $files as $file ) {
				$zip->addFromString( $sub_folders[ $article_id ] . '/' . $file->filename, $file->contents );
			}
		}

		// add images (by path)
		if ( is_array( $this->images ) ) {
			foreach ( $this->images as $article_id => $images ) {
				foreach ( $images as $image_full_path => $image_name ) {
					$zip->addFile( $image_full_path, $sub_folders[ $article_id ] . '/' . remove_accents( $image_name ) );
				}
			}
		}

		$zip->close();

		$this->out_zip_file( $tmp_zip_file );
	}

	/**
	 * Builds the file name for the zip
	 * Uses the print issue title & day/time
	 *
	 * @uses get_timezone
	 *
	 * @return string The zip file name
	 */
	function out_zip_file( $filename ) {
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $this->get_zip_file_name() . '.zip"' );
		$handle = fopen( $filename, 'rb' );
		if ( $handle ) {
			while ( ! feof( $handle ) ) {
				echo fread( $handle, 4096 );
				ob_flush();
				flush();
			}
		}

		fclose( $handle );
		exit;
	}


	/**
	 * Builds the file name for the zip
	 * Uses the print issue title & day/time
	 *
	 * @uses get_timezone
	 *
	 * @return string The zip file name
	 */
	function get_zip_file_name() {
		date_default_timezone_set( Core\get_timezone() );
		return sprintf( __( 'Issue %1$s exported on %2$s at %3$s', 'eight-day-week' ), $this->print_issue_title, date( 'm-d-y' ), date( 'h:ia' ) );
	}

}

/**
 * Class Article_XML
 *
 * @package Eight_Day_Week\Plugins\Article_Export
 *
 * Builds an XML DOMDocument based on a WP_Post
 */
class Article_XML {

	/**
	 * @var \WP_Post
	 */
	var $article;

	/**
	 * Sets object properties
	 *
	 * @param \WP_Post $article A post to export
	 */
	function __construct( \WP_Post $article ) {
		$this->article = $article;
		$this->id      = $article->ID;
	}

	/**
	 * Builds XML document from a WP_Post
	 *
	 * @return object DOMDocument + children elements for easy access
	 * @throws \Exception Various points of failure
	 */
	function build_xml() {

		global $post;

		// store global post backup
		$old = $post;

		// and set global post to this post
		$post = $this->article;

		$content = $this->get_article_content( $this->article );
		if ( ! $content ) {
			throw new \Exception( 'Post ' . $this->id . ' was empty.' );
		}

		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

		$dom = new \DOMDocument();
		@$dom->loadHTML(
			mb_convert_encoding(
				$content,
				apply_filters( __NAMESPACE__ . '\dom_encoding_from', 'HTML-ENTITIES' ),
				apply_filters( __NAMESPACE__ . '\dom_encoding_to', 'UTF-8' )
			)
		);

		// perform dom manipulations
		$dom = $this->manipulate_dom( $dom );

		// do html_to_xml before adding elements so that the html wrap stuff is removed first
		$xml_elements = $this->html_to_xml( $dom );

		$elements = apply_filters(
			__NAMESPACE__ . '\xml_outer_elements',
			$this->get_outer_elements( $this->article ),
			$this->article
		);

		if ( $elements ) {
			$this->add_outer_elements( $xml_elements, $elements );
		}

		$this->add_article_attributes( $xml_elements->root_element, $elements );

		// reset global post
		$post = $old;

		$GLOBALS['recentComment'] = $elements['comment'] ? $elements['comment'] : false;

		return $xml_elements;
	}

	/**
	 * Get prepared article content
	 *
	 * @param object $article WP_Post article
	 *
	 * @return string article content
	 */
	function get_article_content( $article ) {

		$content = $this->article->post_content;

		if ( $post = get_post( get_post_thumbnail_id( $this->id ) ) ) {
			if ( $image = $this->get_image_name( $post->ID ) ) {
				$content = $this->get_image_tag( $image[1], $post->post_excerpt ) . $content;
			}
		}

		$content = preg_replace_callback(
			'/\[caption.*\[\/caption\]/Usi',
			function ( $matches ) {
				if ( preg_match( '/attachment_(\d+)\D/i', $matches[0], $matches2 ) ) {
					$image = $this->get_image_name( (int) $matches2[1] );
				}
				if ( preg_match( '/\[caption[^\]]*](.*)\[\/caption\]/Usi', strip_tags( $matches[0] ), $matches2 ) ) {
					$caption = trim( $matches2[1] );
				}
				return $image ? $this->get_image_tag( $image[1], $caption ) : '';
			},
			$content
		);

		$content = preg_replace_callback(
			'/\[gallery[^]]*ids="([\d,]+)"[^]]*\]/Usi',
			function ( $matches ) {

				if ( $matches[1] ) {
					$gallery_images = explode( ',', $matches[1] );
					foreach ( $gallery_images as $img_id ) {
						$image = $this->get_image_name( (int) $img_id );
					}
				}
				// return $image ? $this->get_image_tag($image[1], $caption) : '';
				return '';
			},
			$content
		);

		$content = strip_shortcodes( $content );

		$content = preg_replace_callback(
			'/((<img[^>]*>)([^<]*<\/img>|))/Usi',
			function ( $matches ) {
				$image = false;
				if ( preg_match( '/wp\-image\-(\d+)\D/i', $matches[0], $matches2 ) ) {
					$image = $this->get_image_name( (int) $matches2[1] );
				}
				return $image ? $this->get_image_tag( $image[1] ) : '';
			},
			$content
		);

		return $content;
	}

	/**
	 * Get array with various elements
	 *
	 * @param object $article WP_Post article
	 *
	 * @return array with elements
	 */
	function get_outer_elements( $article ) {

		$res = array( 'headline' => get_the_title( $article ) );

		if ( $featured_id = get_post_thumbnail_id( $this->id ) ) {
			if ( $image = $this->get_image_name( $featured_id ) ) {
				$res['featured'] = $image;
			}
		}

		if ( $this->images ) {
			$res['image'] = $this->images;
		}

		if ( function_exists( 'get_field' ) && $this->id && $comment = get_field( 'opombe_za_dtp', $this->id ) ) {
			$res['comment'] = $comment;
		}
		return $res;
	}

	/**
	 * Retrieves path and full filename for atatchment id
	 *
	 * @param int $attachment_id Attachment id to process
	 *
	 * @return Array with path and name
	 */
	function get_image_name( $attachment_id = false, $attachment_path = false ) {
		$imageSrc = $attachment_path ? $attachment_path : get_attached_file( $attachment_id );

		if ( preg_match( '/^(.*[\\/])([^\\/]+)\.(.*)$/', $imageSrc, $matches ) ) {
			// [-_]\d+x\d+
			$imagePath     = $matches[1];
			$imageFilename = ( ! $attachment_id && preg_match( '/^(.+)[-_]\d+x\d+$/i', $matches[2], $matches2 ) ? $matches2[1] : $matches[2] ) . '.' . $matches[3];
		}

		if ( ! $imageFilename ) {
			return array();
		}

		$this->images[ $imagePath . $imageFilename ] = $imageFilename;

		return array( $imagePath, $imageFilename );
	}

	/**
	 * Formats tag for atatchment name and caption
	 *
	 * @param string $attachment_name Attachment name
	 * @param string $attachment_caption Attachment caption (optional)
	 *
	 * @return String with formatted image tag
	 */
	function get_image_tag( $attachment_name, $attachment_caption = false ) {
		return ' ## ' . remove_accents( $attachment_name ) . ' ## ' . ( $attachment_caption ? trim( $attachment_caption ) : '' ) . ' ';
	}

	/**
	 * Appends various elements
	 *
	 * @param object $xml_elements XML Document + children
	 * @param array  $outer_elements Elements to add to the root element
	 *
	 * @return \DOMDocument XML Doc with elements appended to the root
	 */
	function add_outer_elements( $xml_elements, $outer_elements ) {
		foreach ( $outer_elements as $tag_name => $value ) {
			if ( ! $value ) {
				continue;
			}

			if ( $tag_name == 'headline' ) {

				$element            = $xml_elements->xml_document->createElement( $tag_name );
				$element->nodeValue = $value;
				$afterSibling       = $xml_elements->root_element->firstChild;
				$xml_elements->root_element->insertBefore( $element, $afterSibling );

			} elseif ( $tag_name == 'featured' || $tag_name == 'image' ) {

				if ( $tag_name == 'featured' ) {
					$element = $xml_elements->xml_document->createElement( 'image' );
					$value   = array_values( $value );
					$element->setAttribute( 'href', 'file:///' . html_entity_decode( remove_accents( array_pop( $value ) ) ) );
					$element->nodeValue = '';
					$afterSibling       = $xml_elements->xml_document->getElementsByTagName( 'content' );
					$xml_elements->root_element->insertBefore( $element, $afterSibling[0] );
					$xml_elements->root_element->insertBefore( $xml_elements->xml_document->createTextNode( "\n" ), $afterSibling[0] );
				} else {
					foreach ( $value as $image_full_path => $image_name ) {
						if ( $outer_elements['featured'] && $outer_elements['featured'][1] == $image_name ) {
							continue;
						}
						$element = $xml_elements->xml_document->createElement( 'image' );
						$element->setAttribute( 'href', 'file:///' . html_entity_decode( remove_accents( $image_name ) ) );
						$element->nodeValue = '';
						$xml_elements->root_element->appendChild( $element );
					}
				}
			} else {

				$element            = $xml_elements->xml_document->createElement( $tag_name );
				$element->nodeValue = $value;
				$xml_elements->root_element->appendChild( $element );

			}
		}
	}

	/**
	 * Get the post's first author's name
	 *
	 * @return string The author's name (last name if set, but has fallbacks)
	 */
	function get_first_author_name() {
		if ( function_exists( 'get_coauthors' ) ) {
			$authors = get_coauthors( $this->id );
		} else {
			$authors = array( get_userdata( $this->id ) );
		}

		if ( ! $authors ) {
			return '';
		}

		$author = $authors[0];

		if ( ! $author ) {
			return '';
		}

		if ( $author->last_name ) {
			return $author->last_name;
		}

		return $author->display_name;
	}

	/**
	 * Adds the first author's name to the article element
	 *
	 * @param \DOMElement $article_element The root article element
	 */
	function add_author_name( $article_element ) {
		$article_element->setAttribute( 'author', html_entity_decode( $this->get_first_author_name() ) );
	}

	/**
	 * Adds the post title to the article element
	 *
	 * @param \DOMElement $article_element The root article element
	 * @param string      $title The post title to add
	 */
	function add_post_title( $article_element, $title ) {
		$article_element->setAttribute( 'title', html_entity_decode( $title ) );
	}

	/**
	 * Adds the post comment to the article element
	 *
	 * @param \DOMElement $article_element The root article element
	 * @param string      $title The post comment to add
	 */
	function add_post_comment( $article_element, $comment ) {
		$article_element->setAttribute( 'comment', html_entity_decode( $comment ) );
	}

	/**
	 * Adds various attributes to the article element
	 *
	 * @param \DOMElement $article_element
	 * @param array       $elements Predefined attribute values
	 */
	function add_article_attributes( $article_element, $elements ) {
		$this->add_author_name( $article_element );

		if ( isset( $elements['headline'] ) ) {
			$this->add_post_title( $article_element, $elements['headline'] );
		}
	}

	/**
	 * Performs various DOM manipulations
	 *
	 * @param \DOMDocument $dom
	 *
	 * @return \DOMDocument The modified DOM
	 */
	function manipulate_dom( $dom ) {
		$this->extract_elements_by_xpath( $dom );
		$this->remove_elements( $dom );

		// allow third party modification of the entire dom
		$dom = apply_filters( __NAMESPACE__ . '\dom', $dom );

		return $dom;
	}

	/**
	 * Removes elements from the DOM
	 * Doesn't need to return anything because the DOM is aliiiiiive
	 *
	 * @param $dom \DOMDocument
	 */
	function remove_elements( $dom ) {
		$elements_to_remove = apply_filters( __NAMESPACE__ . '\remove_elements', array( 'img' ) );

		$remove = array();
		foreach ( $elements_to_remove as $tag_name ) {
			$found = $dom->getElementsByTagName( $tag_name );
			foreach ( $found as $el ) {
				$remove[ $tag_name ][] = $el;
			}
		}

		foreach ( $remove as $tag_name => $els ) {
			foreach ( $els as $el ) {
				try {
					$el->parentNode->removeChild( $el );
				} catch ( \Exception $e ) {

				}
			}
		}
	}

	/**
	 * Extracts elements within the content to the root of the document via Xpath queries
	 *
	 * Using the filter, a "query set" can be added like:
	 * [
	 *  'tag_name'  => 'pullQuote',
	 *  'container' => 'pullQuotes',
	 *  'query'     => '//p[contains(@class, "pullquote")]'
	 * ]
	 *
	 * The above array would extract all paragraphs with the "pullquote" class
	 * Create a new root element in the DOM called "pullQuotes"
	 * and add each found paragraph to the pullQuotes element
	 * as a newly created "pullQuote" element with the content of the paragraph
	 *
	 * @param $dom \DOMDocument
	 * Doesn't need to return anything because the DOM is aliiiiiive
	 */
	function extract_elements_by_xpath( $dom ) {
		$xpath_extract = apply_filters( __NAMESPACE__ . '\xpath_extract', array() );
		if ( $xpath_extract ) {
			$domxpath = new \DOMXPath( $dom );

			foreach ( $xpath_extract as $set ) {
				$remove   = array();
				$elements = $domxpath->query( $set['query'] );
				if ( $elements->length ) {
					$wrap = $dom->createElement( $set['container'] );
					$dom->appendChild( $wrap );
					foreach ( $elements as $el ) {
						$remove[]           = $el;
						$element            = $dom->createElement( $set['tag_name'] );
						$element->nodeValue = $el->nodeValue;
						$wrap->appendChild( $element );
					}
					foreach ( $remove as $el ) {
						$el->parentNode->removeChild( $el );
					}
				}
			}
		}
	}

	/**
	 * Converts the html document to valid xml document
	 * with a root element of 'article'
	 *
	 * @param \DOMDocument $dom
	 *
	 * @throws \Exception Various points of failure
	 *
	 * @return object DOMDocument + child elements for easy access
	 */
	function html_to_xml( $dom ) {
		$content = $dom->getElementsByTagName( 'body' );
		if ( ! $content ) {
			throw new \Exception( 'Empty content' );
		}

		$content = $content->item( 0 );

		$xml_document = new \DOMDocument();

		$article_element = $xml_document->createElement( apply_filters( __NAMESPACE__ . '\xml_root_element', 'article' ) );
		$xml_document->appendChild( $article_element );

		$content_element = $xml_document->createElement( apply_filters( __NAMESPACE__ . '\xml_content_element', 'content' ) );
		$article_element->appendChild( $content_element );

		foreach ( $content->childNodes as $el ) {
			$content_element->appendChild( $xml_document->importNode( $el, true ) );
		}

		$article_xml                  = new \stdClass();
		$article_xml->xml_document    = $xml_document;
		$article_xml->root_element    = $article_element;
		$article_xml->content_element = $content_element;

		return $article_xml;
	}

}

/**
 * Class File
 *
 * @package Eight_Day_Week\Plugins\Article_Export
 *
 * Builds a "File" based on either a string or a readable, actual file
 */
class File {

	/**
	 * @var string The File's name
	 */
	var $filename;

	/**
	 * @var string The File's contents
	 */
	var $contents;

	/**
	 * Sets object properties
	 *
	 * If given a readable file path, builds the file name + contents via the actual file
	 * Otherwise assumes provision of explicit file contents + name
	 *
	 * @param $contents_or_file_path
	 * @param string                $filename
	 */
	function __construct( $contents_or_file_path, $filename = '' ) {
		if ( is_readable( $contents_or_file_path ) ) {
			$this->contents = file_get_contents( $contents_or_file_path );
			$this->filename = basename( $contents_or_file_path );
		} else {
			$this->contents = $contents_or_file_path;
			$this->filename = $filename;
		}
	}
}

/**
 * Returns a set of Files if there are documents attached to provided article
 *
 * @param $incoming mixed|bool The previous filter value
 * @param $article \WP_Post The post to retrieve attachments from
 * @param $issue_id int The print issue's ID
 * @param $issue_title string The print issue's title
 *
 * @return File[] Set of Attachment Files, or $incoming value
 */
function maybe_export_article_attachments( $incoming, $article, $issue_id, $issue_title ) {

	add_filter( 'posts_where', __NAMESPACE__ . '\filter_article_export_attachments_where' );

	$attachments = new \WP_Query(
		array(
			'post_type'              => 'attachment',
			'post_parent'            => absint( $article->ID ),
			'posts_per_page'         => 20,
			'post_status'            => 'inherit',
			// meta will be used, so only disable term cache
			'update_post_term_cache' => false,
		)
	);

	remove_filter( 'posts_where', __NAMESPACE__ . '\filter_article_export_attachments_where' );

	if ( $attachments->have_posts() ) {
		$incoming = get_document_files( $attachments->posts );
	}

	return $incoming;
}

function filter_article_export_attachments_where( $where ) {
	return $where . " AND post_mime_type NOT LIKE 'image%'";
}

/**
 * Gets a set of Files[] from attachments
 *
 * @param $attachments \WP_Post[] Attachments of a parent article
 *
 * @return File[] Attachment files
 */
function get_document_files( $attachments ) {
	return array_map(
		function( $attachment ) {
			return get_attachment_file( $attachment );
		},
		$attachments
	);
}

/**
 * Builds a File from an attachment
 *
 * @param $attachment
 *
 * @return File The File of the attachment
 */
function get_attachment_file( $attachment ) {
	return new File( get_attached_file( $attachment->ID ) );
}

/**
 * Adds a headline (title) element to the root of the XML document
 *
 * @param $elements array Incoming elements
 * @param $article \WP_Post The current post
 *
 * @return array Modified elements
 */
function filter_xml_elements_headline( $elements, $article ) {
	$elements['headline'] = get_the_title( $article );
	return $elements;
}

/**
 * Prefix the xml filename with the post author's name
 *
 * @param $file_name string The original file name
 * @param $article \WP_Post The post
 * @param $xml object Custom DOMDocument wrapper object
 *
 * @return string The modified filename
 */
function filter_xml_filename_author( $file_name, $article, $xml ) {
	$author = $xml->root_element->getAttribute( 'author' );
	if ( $author ) {
		$file_name = $author . '.' . $file_name;
	}

	return $file_name;
}
