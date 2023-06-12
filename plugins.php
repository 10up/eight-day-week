<?php
/**
 * Allows filtering of Eight Day Week plugin loading
 *
 * @package eight-day-week
 */

namespace Eight_Day_Week\Plugins;

/**
 * Allows filtering of Eight Day Week plugin loading
 *
 * Use the first filter (Eight_Day_Week\Core\load_plugins) to disable all
 * Use the second (Eight_Day_Week\Core\load_{$plugin}) to disable a specific one
 *
 * @param $plugin string The plugin in question
 *
 * @return mixed|void Whether or not to load the given plugin
 */
function should_load_plugin( $plugin ) {
	return apply_filters( __NAMESPACE__ . '\load_plugins', apply_filters( __NAMESPACE__ . '\load_' . $plugin, true ), $plugin );
}

add_filter(
	'edw_files_to_load',
	function( $files ) {

		foreach ( $files as $file_path => $namespace ) {
			if ( false !== strpos( $file_path, EDW_INC . 'functions/plugins/' ) ) {
				if ( ! should_load_plugin( str_replace( '.php', '', basename( $file_path ) ) ) ) {
					unset( $files[ $file_path ] );
				}
			}
		}

		return $files;
	}
);
