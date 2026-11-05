<?php
/**
 * Removes everything Pratiks Block Grammar Editor stored. Runs when the plugin is deleted from wp-admin.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes all plugin configuration options.
 */
function pratiks_block_grammar_editor_uninstall(): void {
	$delete_plugin_data = static function () {
		delete_option( 'pratiks_block_grammar_editor_settings' );
	};

	if ( is_multisite() ) {
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			$delete_plugin_data();
			restore_current_blog();
		}
	} else {
		$delete_plugin_data();
	}
}

pratiks_block_grammar_editor_uninstall();
