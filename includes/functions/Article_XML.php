<?php
/**
 * Article_XML
 *
 * @package Eight_Day_Week
 */

namespace Eight_Day_Week\Plugins\Article_Export;

/**
 * Class Article_XML
 *
 * @package Eight_Day_Week\Plugins\Article_Export
 *
 * Builds an XML DOMDocument based on a WP_Post
 */
class Article_XML {

	/**
	 * Article
	 *
	 * @var \WP_Post
	 */
	public $article;

	/**
	 * Article ID
	 *
	 * @var int Article ID
	 */
	private $id;

	/**
	 * Images
	 *
	 * @var array Images
	 */
	public $images;

	/**
	 * Sets object properties
	 *
	 * @param \WP_Post $article A post to export.
	 */
	public function __construct( \WP_Post $article ) {
		$this->article = $article;
		$this->id      = $article->ID;
	}

	/**
	 * Builds XML document from a WP_Post
	 *
	 * @return object DOMDocument + children elements for easy access
	 * @throws \Exception Various points of failure.
	 */
	public function build_xml() {

		global $post;

		// Store global post backup.
		$old = $post;

		// And set global post to this post.
		$post = $this->article; // phpcs:ignore

		$content = $this->get_article_content();
		if ( ! $content ) {
			throw new \Exception( 'Post ' . absint( $this->id ) . ' was empty.' );
		}

		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

		$dom = new \DOMDocument();
		$dom->loadHTML(
			mb_convert_encoding(
				$content,
				apply_filters( __NAMESPACE__ . '\dom_encoding_from', 'HTML-ENTITIES' ), // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
				apply_filters( __NAMESPACE__ . '\dom_encoding_to', 'UTF-8' ) // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			)
		);

		// Perform dom manipulations.
		$dom = $this->manipulate_dom( $dom );

		// Do html_to_xml before adding elements so that the html wrap stuff is removed first.
		$xml_elements = $this->html_to_xml( $dom );

		$elements = apply_filters(
			__NAMESPACE__ . '\xml_outer_elements', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$this->get_outer_elements( $this->article ),
			$this->article
		);

		if ( $elements ) {
			$this->add_outer_elements( $xml_elements, $elements );
		}

		$this->add_article_attributes( $xml_elements->root_element, $elements );

		// Reset global post.
		$post = $old; // phpcs:ignore

		$GLOBALS['recentComment'] = $elements['comment'] ? $elements['comment'] : false;

		return $xml_elements;
	}

	/**
	 * Get prepared article content
	 *
	 * @return string article content
	 */
	public function get_article_content() {

		$content = $this->article->post_content;

		$post = get_post( get_post_thumbnail_id( $this->id ) );
		if ( $post ) {
			$image = $this->get_image_name( $post->ID );
			if ( $image ) {
				$content = $this->get_image_tag( $image[1], $post->post_excerpt ) . $content;
			}
		}

		$dom = new \DOMDocument();
		$dom->loadHTML( $content );

		$captions = $dom->getElementsByTagName( 'caption' );

		foreach ( $captions as $caption ) {
			$image        = null;
			$caption_text = '';

			// Assuming the attachment id is an attribute in the caption tag.
			$attachment_id = $caption->getAttribute( 'attachment' );
			if ( $attachment_id ) {
				$image = $this->get_image_name( (int) $attachment_id );
			}

			// Assuming the caption text is inside the caption tag.
			if ( $caption->nodeValue ) { // phpcs:ignore
				$caption_text = trim( $caption->nodeValue ); // phpcs:ignore
			}

			if ( $image ) {
				$image_tag = $this->get_image_tag( $image[1], $caption_text );
				$new_node  = $dom->createDocumentFragment();
				$new_node->appendXML( $image_tag );
				$caption->parentNode->replaceChild( $new_node, $caption ); // phpcs:ignore
			} else {
				$caption->parentNode->removeChild( $caption ); // phpcs:ignore
			}
		}

		$content = $dom->saveHTML();

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
	 * @param object $article WP_Post article.
	 *
	 * @return array with elements
	 */
	public function get_outer_elements( $article ) {

		$res = array( 'headline' => get_the_title( $article ) );

		$featured_id = get_post_thumbnail_id( $this->id );
		if ( $featured_id ) {
			$image = $this->get_image_name( $featured_id );
			if ( $image ) {
				$res['featured'] = $image;
			}
		}

		if ( $this->images ) {
			$res['image'] = $this->images;
		}

		if ( function_exists( 'get_field' ) && $this->id ) {
			$comment = get_field( 'opombe_za_dtp', $this->id );
			if ( $comment ) {
				$res['comment'] = $comment;
			}
		}
		return $res;
	}

	/**
	 * Retrieves the name of an image based on the given attachment ID or path.
	 *
	 * @param bool|int    $attachment_id The ID of the attachment. Defaults to false.
	 * @param bool|string $attachment_path The path of the attachment. Defaults to false.
	 * @return array An array containing the image path and filename.
	 */
	public function get_image_name( $attachment_id = false, $attachment_path = false ) {
		$image_filename = '';
		$image_path     = '';
		$image_src      = $attachment_path ? $attachment_path : get_attached_file( $attachment_id );

		if ( preg_match( '/^(.*[\\/])([^\\/]+)\.(.*)$/', $image_src, $matches ) ) {
			// [-_]\d+x\d+
			$image_path     = $matches[1];
			$image_filename = ( ! $attachment_id && preg_match( '/^(.+)[-_]\d+x\d+$/i', $matches[2], $matches2 ) ? $matches2[1] : $matches[2] ) . '.' . $matches[3];
		}

		if ( ! $image_filename ) {
			return array();
		}

		$this->images[ $image_path . $image_filename ] = $image_filename;

		return array( $image_path, $image_filename );
	}

	/**
	 * Formats tag for attachment name and caption
	 *
	 * @param string $attachment_name Attachment name.
	 * @param string $attachment_caption Attachment caption (optional).
	 *
	 * @return String with formatted image tag
	 */
	public function get_image_tag( $attachment_name, $attachment_caption = false ) {
		return ' ## ' . remove_accents( $attachment_name ) . ' ## ' . ( $attachment_caption ? trim( $attachment_caption ) : '' ) . ' ';
	}

	/**
	 * Appends various elements
	 *
	 * @param object $xml_elements XML Document + children.
	 * @param array  $outer_elements Elements to add to the root element.
	 */
	public function add_outer_elements( $xml_elements, $outer_elements ) {
		foreach ( $outer_elements as $tag_name => $value ) {
			if ( ! $value ) {
				continue;
			}

			if ( 'headline' === $tag_name ) {

				$element            = $xml_elements->xml_document->createElement( $tag_name );
				$element->nodeValue = $value; // phpcs:ignore
				$after_sibling      = $xml_elements->root_element->firstChild;
				$xml_elements->root_element->insertBefore( $element, $after_sibling );

			} elseif ( 'featured' === $tag_name || 'image' === $tag_name ) {

				if ( 'featured' === $tag_name ) {
					$element = $xml_elements->xml_document->createElement( 'image' );
					$value   = array_values( $value );
					$element->setAttribute( 'href', 'file:///' . html_entity_decode( remove_accents( array_pop( $value ) ) ) );
					$element->nodeValue = ''; // phpcs:ignore
					$after_sibling      = $xml_elements->xml_document->getElementsByTagName( 'content' );
					$xml_elements->root_element->insertBefore( $element, $after_sibling[0] );
					$xml_elements->root_element->insertBefore( $xml_elements->xml_document->createTextNode( "\n" ), $after_sibling[0] );
				} else {
					foreach ( $value as $image_full_path => $image_name ) {
						if ( $outer_elements['featured'] && $outer_elements['featured'][1] === $image_name ) {
							continue;
						}
						$element = $xml_elements->xml_document->createElement( 'image' );
						$element->setAttribute( 'href', 'file:///' . html_entity_decode( remove_accents( $image_name ) ) );
						$element->nodeValue = ''; // phpcs:ignore
						$xml_elements->root_element->appendChild( $element );
					}
				}
			} else {

				$element            = $xml_elements->xml_document->createElement( $tag_name );
				$element->nodeValue = $value; // phpcs:ignore
				$xml_elements->root_element->appendChild( $element );

			}
		}
	}

	/**
	 * Get the post's first author's name
	 *
	 * @return string The author's name (last name if set, but has fallbacks)
	 */
	public function get_first_author_name() {
		if ( function_exists( 'get_coauthors' ) ) {
			$authors = \get_coauthors( $this->id );
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
	 * @param \DOMElement $article_element The root article element.
	 */
	public function add_author_name( $article_element ) {
		$article_element->setAttribute( 'author', html_entity_decode( $this->get_first_author_name() ) );
	}

	/**
	 * Adds the post title to the article element
	 *
	 * @param \DOMElement $article_element The root article element.
	 * @param string      $title The post title to add.
	 */
	public function add_post_title( $article_element, $title ) {
		$article_element->setAttribute( 'title', html_entity_decode( $title ) );
	}

	/**
	 * Adds the post comment to the article element
	 *
	 * @param \DOMElement $article_element The root article element.
	 * @param string      $comment The post comment to add.
	 */
	public function add_post_comment( $article_element, $comment ) {
		$article_element->setAttribute( 'comment', html_entity_decode( $comment ) );
	}

	/**
	 * Adds attributes to an article element.
	 *
	 * @param mixed $article_element The article element to add attributes to.
	 * @param array $elements An array of elements.
	 * @return void
	 */
	public function add_article_attributes( $article_element, $elements ) {
		$this->add_author_name( $article_element );

		if ( isset( $elements['headline'] ) ) {
			$this->add_post_title( $article_element, $elements['headline'] );
		}
	}

	/**
	 * Manipulates the DOM.
	 *
	 * This function extracts elements by XPath and removes elements from the DOM.
	 * It also allows third-party modification of the entire DOM.
	 *
	 * @param mixed $dom The DOM to be manipulated.
	 *
	 * @return mixed The manipulated DOM.
	 */
	public function manipulate_dom( $dom ) {
		$this->extract_elements_by_xpath( $dom );
		$this->remove_elements( $dom );

		// Allow third party modification of the entire dom.
		$dom = apply_filters( __NAMESPACE__ . '\dom', $dom ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		return $dom;
	}

	/**
	 * Removes specified elements from the given DOM.
	 *
	 * @param \DOMDocument $dom The DOM document to remove elements from.
	 * @throws \Exception If an error occurs while removing elements.
	 */
	public function remove_elements( $dom ) {
		$elements_to_remove = apply_filters( __NAMESPACE__ . '\remove_elements', array( 'img' ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

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
					$el->parentNode->removeChild( $el ); // phpcs:ignore
				} catch ( \Exception $e ) { // phpcs:ignore
					// Do nothing.
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
	 * @param \DOMDocument $dom The DOM document to extract elements from.
	 *
	 * @throws \Exception Exception thrown if there is an error in the XPath query.
	 *
	 * @return void
	 */
	public function extract_elements_by_xpath( $dom ) {
		$xpath_extract = apply_filters( __NAMESPACE__ . '\xpath_extract', array() ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
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
						$element->nodeValue = $el->nodeValue; // phpcs:ignore
						$wrap->appendChild( $element );
					}
					foreach ( $remove as $el ) {
						$el->parentNode->removeChild( $el ); // phpcs:ignore
					}
				}
			}
		}
	}

	/**
	 * Convert HTML to XML.
	 *
	 * @param \DOMDocument $dom The HTML DOM document.
	 * @throws \Exception If the content is empty.
	 * @return \stdClass The converted XML.
	 */
	public function html_to_xml( $dom ) {
		$content = $dom->getElementsByTagName( 'body' );
		if ( ! $content ) {
			throw new \Exception( 'Empty content' );
		}

		$content = $content->item( 0 );

		$xml_document = new \DOMDocument();

		$article_element = $xml_document->createElement( apply_filters( __NAMESPACE__ . '\xml_root_element', 'article' ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$xml_document->appendChild( $article_element );

		$content_element = $xml_document->createElement( apply_filters( __NAMESPACE__ . '\xml_content_element', 'content' ) ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$article_element->appendChild( $content_element );

		foreach ( $content->childNodes as $el ) { // phpcs:ignore
			$content_element->appendChild( $xml_document->importNode( $el, true ) );
		}

		$article_xml                  = new \stdClass();
		$article_xml->xml_document    = $xml_document;
		$article_xml->root_element    = $article_element;
		$article_xml->content_element = $content_element;

		return $article_xml;
	}
}
