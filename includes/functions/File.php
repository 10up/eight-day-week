<?php
/**
 * File
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Export;

/**
 * Class File
 *
 * @package Eight_Day_Week\Plugins\Article_Export
 *
 * Builds a "File" based on either a string or a readable, actual file
 */
class File {

	/**
	 * Filename
	 *
	 * @var string The File's name
	 */
	public $filename;

	/**
	 * File contents
	 *
	 * @var string The File's contents
	 */
	public $contents;

	/**
	 * Constructs a new instance of the class.
	 *
	 * If given a readable file path, builds the file name + contents via the actual file
	 * Otherwise assumes provision of explicit file contents + name
	 *
	 * @param mixed  $contents_or_file_path The contents of the file or the file path.
	 * @param string $filename The name of the file.
	 */
	public function __construct( $contents_or_file_path, $filename = '' ) {
		if ( is_readable( $contents_or_file_path ) ) {
			$this->contents = file_get_contents( $contents_or_file_path ); // phpcs:ignore
			$this->filename = basename( $contents_or_file_path );
		} else {
			$this->contents = $contents_or_file_path;
			$this->filename = $filename;
		}
	}
}
