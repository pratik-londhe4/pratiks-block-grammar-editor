<?php
/**
 * Plugin bootstrap.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor
 */

namespace PratikLondhe\PratiksBlockGrammarEditor;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the services once and registers their hooks.
 */
final class Plugin {

	/**
	 * The running instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Registered services list.
	 *
	 * @var Service[]
	 */
	private array $services = array();

	/**
	 * Starts the plugin. Runs on `plugins_loaded`.
	 */
	public static function boot(): void {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register();
		}
	}

	/**
	 * The running instance, for other plugins.
	 *
	 * @return Plugin|null
	 */
	public static function instance(): ?Plugin {
		return self::$instance;
	}

	/**
	 * The plugin's services: one per concern (assets, settings, abilities, WP-CLI, privacy).
	 *
	 * @return Service[]
	 */
	private function services(): array {
		if ( empty( $this->services ) ) {
			$this->services = array(
				new Editor\Assets(),
				new Settings\SettingsPage(),
				new Cli\Command(),
				new Abilities(),
				new Privacy(),
			);
		}

		return $this->services;
	}

	/**
	 * Registers every service and fires loaded action.
	 */
	private function register(): void {
		foreach ( $this->services() as $service ) {
			$service->register();
		}

		/**
		 * Action triggered once Block Grammar Editor has booted all services.
		 *
		 * @param Plugin $instance The plugin bootstrap instance.
		 */
		do_action( 'pratiks_block_grammar_editor_loaded', $this );
	}
}
