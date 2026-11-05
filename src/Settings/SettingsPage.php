<?php
/**
 * Settings admin page.
 *
 * @package PratikLondhe\PratiksBlockGrammarEditor\Settings
 */

namespace PratikLondhe\PratiksBlockGrammarEditor\Settings;

use PratikLondhe\PratiksBlockGrammarEditor\Service;
use const PratikLondhe\PratiksBlockGrammarEditor\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Manages plugin settings screen under Settings > Block Grammar Editor.
 */
class SettingsPage implements Service {

	public const OPTION_NAME = 'pratiks_block_grammar_editor_settings';
	public const MENU_SLUG   = 'pratiks-block-grammar-editor';

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Adds settings submenu page under Settings.
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ),
			__( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers settings with schema and sanitization.
	 */
	public function register_settings(): void {
		register_setting(
			'pratiks_block_grammar_editor_group',
			self::OPTION_NAME,
			array(
				'type'              => 'object',
				'description'       => __( 'Block Grammar Editor preferences and capabilities', 'pratiks-block-grammar-editor' ),
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'pbge_main_section',
			__( 'General Configuration', 'pratiks-block-grammar-editor' ),
			'__return_empty_string',
			self::MENU_SLUG
		);

		add_settings_field(
			'required_capability',
			__( 'Minimum Required Role', 'pratiks-block-grammar-editor' ),
			array( $this, 'render_capability_field' ),
			self::MENU_SLUG,
			'pbge_main_section'
		);

		add_settings_field(
			'auto_format_attributes',
			__( 'Auto-format Attributes', 'pratiks-block-grammar-editor' ),
			array( $this, 'render_auto_format_field' ),
			self::MENU_SLUG,
			'pbge_main_section'
		);

		add_settings_field(
			'enable_copy_button',
			__( 'Clipboard Copy', 'pratiks-block-grammar-editor' ),
			array( $this, 'render_copy_button_field' ),
			self::MENU_SLUG,
			'pbge_main_section'
		);
	}

	/**
	 * Gets default settings.
	 *
	 * @return array<string, mixed> Default settings.
	 */
	public function get_defaults(): array {
		$defaults = array(
			'required_capability'    => 'edit_posts',
			'auto_format_attributes' => 0,
			'enable_copy_button'     => 1,
		);

		/**
		 * Filters the default settings.
		 *
		 * @param array $defaults Default settings.
		 */
		return (array) apply_filters( 'pratiks_block_grammar_editor_default_settings', $defaults );
	}

	/**
	 * Sanitizes input settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public function sanitize_settings( $input ): array {
		$defaults  = $this->get_defaults();
		$sanitized = array();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$cap = isset( $input['required_capability'] ) ? sanitize_key( $input['required_capability'] ) : 'edit_posts';
		if ( ! in_array( $cap, array( 'edit_posts', 'manage_options' ), true ) ) {
			$cap = 'edit_posts';
		}
		$sanitized['required_capability'] = $cap;

		$sanitized['auto_format_attributes'] = ! empty( $input['auto_format_attributes'] ) ? 1 : 0;
		$sanitized['enable_copy_button']     = ! empty( $input['enable_copy_button'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Renders capability select field.
	 */
	public function render_capability_field(): void {
		$settings = (array) get_option( self::OPTION_NAME, $this->get_defaults() );
		$current  = ! empty( $settings['required_capability'] ) ? $settings['required_capability'] : 'edit_posts';
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php esc_html_e( 'Minimum Required Role', 'pratiks-block-grammar-editor' ); ?></span>
			</legend>
			<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[required_capability]" id="pbge_capability">
				<option value="edit_posts" <?php selected( $current, 'edit_posts' ); ?>>
					<?php esc_html_e( 'Authors, Editors, and Administrators (edit_posts)', 'pratiks-block-grammar-editor' ); ?>
				</option>
				<option value="manage_options" <?php selected( $current, 'manage_options' ); ?>>
					<?php esc_html_e( 'Administrators Only (manage_options)', 'pratiks-block-grammar-editor' ); ?>
				</option>
			</select>
			<p class="description">
				<?php esc_html_e( 'Control who can access the Block Grammar Editor action in the block toolbar.', 'pratiks-block-grammar-editor' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Renders auto-format attributes checkbox.
	 */
	public function render_auto_format_field(): void {
		$settings = (array) get_option( self::OPTION_NAME, $this->get_defaults() );
		$checked  = ! empty( $settings['auto_format_attributes'] );
		?>
		<label for="pbge_auto_format">
			<input
				type="checkbox"
				id="pbge_auto_format"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[auto_format_attributes]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Automatically pretty-print JSON block attributes when opening grammar modal', 'pratiks-block-grammar-editor' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders copy button checkbox.
	 */
	public function render_copy_button_field(): void {
		$settings = (array) get_option( self::OPTION_NAME, $this->get_defaults() );
		$checked  = ! isset( $settings['enable_copy_button'] ) || ! empty( $settings['enable_copy_button'] );
		?>
		<label for="pbge_copy_button">
			<input
				type="checkbox"
				id="pbge_copy_button"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_copy_button]"
				value="1"
				<?php checked( $checked, true ); ?>
			/>
			<?php esc_html_e( 'Display "Copy Grammar" button in editor modal', 'pratiks-block-grammar-editor' ); ?>
		</label>
		<?php
	}

	/**
	 * Adds a "Settings" link in plugins list.
	 *
	 * @param string[] $links Action links.
	 * @return string[] Updated action links.
	 */
	public function add_action_links( array $links ): array {
		$settings_url  = admin_url( 'options-general.php?page=' . self::MENU_SLUG );
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $settings_url ),
			esc_html__( 'Settings', 'pratiks-block-grammar-editor' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Renders settings admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Block Grammar Editor', 'pratiks-block-grammar-editor' ); ?></h1>
			<p>
				<?php esc_html_e( 'Configure access permissions and default editor behavior when viewing or modifying isolated block comment grammar.', 'pratiks-block-grammar-editor' ); ?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'pratiks_block_grammar_editor_group' );
				do_settings_sections( self::MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
