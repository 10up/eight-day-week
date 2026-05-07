<?php
/**
 * Article_Zip_Factory
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Export;

use Eight_Day_Week\Plugins\Article_Export\Article_XML;
use Eight_Day_Week\Plugins\Article_Export\File;

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
	 * Article IDs
	 *
	 * @var int[] Article IDs
	 */
	private $ids;

	/**
	 * Print issue ID
	 *
	 * @var int Print issue ID
	 */
	private $print_issue_id;

	/**
	 * Print issue title
	 *
	 * @var string Print issue title
	 */
	private $print_issue_title;

	/**
	 * Article array
	 *
	 * @var \WP_Post[] Articles
	 */
	public $articles;

	/**
	 * Files for articles
	 *
	 * @var File[] Files for all articles
	 */
	public $files;

	/**
	 * Image for articles
	 *
	 * @var array Images for all articles
	 */
	public $images;

	/**
	 * Sets up object properties
	 *
	 * @param array  $ids Array of post IDs.
	 * @param int    $print_issue_id ID of parent print issue.
	 * @param string $print_issue_title Title of parent print issue (to avoid lookup).
	 *
	 * @throws \Exception Various points of failure in constructing the factory.
	 */
	public function __construct( $ids, $print_issue_id, $print_issue_title ) {

		$article_ids = array_filter( $ids, 'is_numeric' );
		if ( count( $ids ) !== count( $article_ids ) ) {
			throw new \Exception( esc_html__( 'Invalid article IDs specified in the request.', 'eight-day-week-print-workflow' ) );
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
	public function import_articles() {
		$articles = array();
		foreach ( $this->ids as $id ) {
			$article = get_post( $id );
			if ( ! $article || ! current_user_can( 'read_post', $id ) ) {
				continue;
			}
			$articles[ $id ] = $article;
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
	public function get_articles() {
		if ( $this->articles ) {
			return $this->articles;
		}

		$this->articles = $this->import_articles();
		return $this->articles;
	}

	/**
	 * Gets export files for an article
	 *
	 * Provides a filter so that 3rd parties can hook in
	 * and determine what files to export vs the standard XML.
	 *
	 * @return array File[] Set of export files
	 */
	public function build_file_sets() {

		$articles = $this->get_articles();

		$file_sets = array();
		foreach ( $articles as $article ) {

			// Allow articles to export an alternative file.
			$files = apply_filters( __NAMESPACE__ . '\short_circuit_article_export_files', false, $article, $this->print_issue_id, $this->print_issue_title ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			// But fall back to XML.
			if ( ! $files ) {
				$files = $this->get_xml_file( $article );
			}

			$file_sets[ $article->ID ] = $files;

		}

		return $file_sets;
	}


	/**
	 * Retrieve an XML file for the given article.
	 *
	 * @param mixed $article The article to retrieve the XML file for.
	 * @return array An array containing the XML file.
	 */
	public function get_xml_file( $article ) {
		$xml = $this->get_xml( $article );

		$file_name  = apply_filters( __NAMESPACE__ . '\xml_filename', $xml->root_element->getAttribute( 'title' ), $article, $xml ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$file_name .= '.xml';

		$file_contents = $xml->xml_document->saveXML();

		$fileset   = array();
		$fileset[] = new File( $file_contents, apply_filters( __NAMESPACE__ . '\xml_full_filename', $file_name, $article ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		return $fileset;
	}

	/**
	 * Builds and returns an XML file for an article
	 *
	 * @param \WP_Post $article the current post.
	 *
	 * @return object Contains properties of a DOMDocument object for easy access
	 */
	public function get_xml( $article ) {
		$xml                          = new Article_XML( $article );
		$xml_content                  = $xml->build_xml();
		$this->images[ $article->ID ] = $xml->images;
		return $xml_content;
	}

	/**
	 * Gets a set of files for the current set of articles
	 *
	 * @return File[] Set of files
	 */
	public function get_file_sets() {
		if ( $this->files ) {
			return $this->files;
		}

		$this->files = $this->build_file_sets();
		return $this->files;
	}

	/**
	 * Generates a zip file containing the output of the function.
	 */
	public function output_zip() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Prepare folder.
		$tmp_folder = 'export_xml_' . get_current_user_id();
		$tmp_dir    = get_temp_dir() . $tmp_folder;

		if ( ! $wp_filesystem->is_dir( $tmp_dir ) ) {
			$wp_filesystem->mkdir( $tmp_dir );
		}

		// Create zip.
		$tmp_zip_file = get_temp_dir() . $tmp_folder . '/output.zip';
		if ( file_exists( $tmp_zip_file ) ) {
			wp_delete_file( $tmp_zip_file );
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

		// Add xml files.
		foreach ( $file_sets as $article_id => $files ) {

			$zip->addEmptyDir( $sub_folders[ $article_id ] );

			// Force an array.
			$files = is_array( $files ) ? $files : array( $files );
			foreach ( $files as $file ) {
				$zip->addFromString( $sub_folders[ $article_id ] . '/' . $file->filename, $file->contents );
			}
		}

		// Add images (by path).
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
	 * Outputs the contents of a zip file as a download.
	 *
	 * @param string $filename The path to the zip file.
	 * @throws \Exception If the file cannot be opened.
	 * @return void
	 */
	public function out_zip_file( $filename ) {
		header( 'Content-type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $this->get_zip_file_name() . '.zip"' );
		$handle = fopen( $filename, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( $handle ) {
			while ( ! feof( $handle ) ) {
				echo fread( $handle, 4096 ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions.file_system_operations_fread
				ob_flush();
				flush();
			}

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
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
	public function get_zip_file_name() {
		/* translators: 1: title of the print issue, 2: date of the export (m-d-y), 3: time of the export (h:ia) */
		return sprintf( __( 'Issue %1$s exported on %2$s at %3$s', 'eight-day-week' ), $this->print_issue_title, wp_date( 'm-d-y' ), wp_date( 'h:ia' ) );
	}
}
