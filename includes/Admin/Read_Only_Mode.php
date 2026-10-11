<?php
/**
 * Operator read-only mode option and Overview toggle (ADR 0009).
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One narrowing-only operator choice over the built-in package map.
 *
 * When read-only mode is on, the write and destructive packages never
 * register, regardless of the host package filter; hosts surface their
 * own conflict messaging.
 */
final class Read_Only_Mode {
	const OPTION_KEY    = 'npcink_abilities_toolkit_read_only_mode';
	const TOGGLE_ACTION = 'npcink_abilities_toolkit_toggle_read_only_mode';

	/**
	 * Returns whether read-only mode is enabled for this site.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}

		return '1' === get_option( self::OPTION_KEY, '' );
	}

	/**
	 * Narrows the resolved package map when read-only mode is on.
	 *
	 * @param array<string,bool> $enabled Package map after host filters.
	 * @return array<string,bool>
	 */
	public static function narrow_packages( array $enabled ) {
		if ( ! self::is_enabled() ) {
			return $enabled;
		}

		$enabled['core_write']       = false;
		$enabled['core_destructive'] = false;

		return $enabled;
	}

	/**
	 * @return void
	 */
	public static function boot() {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_post_' . self::TOGGLE_ACTION, array( self::class, 'handle_toggle' ) );
		}
	}

	/**
	 * Renders the read-only checkbox; the redirect_to field carries this
	 * page's own URL so the toggle returns to the real placement.
	 */
	public static function render() {
		$enabled = self::is_enabled();
		?>
		<form class="npcink-abilities-toolkit-readonly-mode" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::TOGGLE_ACTION ); ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( menu_page_url( Test_Page::MENU_SLUG, false ) ); ?>" />
			<?php echo wp_nonce_field( self::TOGGLE_ACTION, '_wpnonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label for="npcink-abilities-toolkit-readonly-mode">
				<input id="npcink-abilities-toolkit-readonly-mode" type="checkbox" name="read_only_mode" value="1" <?php checked( $enabled ); ?> />
				<?php echo esc_html__( 'Read-only mode', 'npcink-abilities-toolkit' ); ?>
			</label>
			<p class="description"><?php echo esc_html__( 'Stop registering write and destructive abilities on this site. Read abilities, comment helpers, and this status page keep working. Host products cannot widen past this choice, and it takes effect on the next request.', 'npcink-abilities-toolkit' ); ?></p>
			<?php if ( $enabled ) : ?>
				<p class="description npcink-abilities-toolkit-readonly-mode__note"><?php echo esc_html__( 'Read-only mode is on: the write and destructive packages above show Off because of this choice.', 'npcink-abilities-toolkit' ); ?></p>
			<?php endif; ?>
			<p class="npcink-abilities-toolkit-actions">
				<button type="submit" class="button"><?php echo esc_html__( 'Apply', 'npcink-abilities-toolkit' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * @return void
	 */
	public static function handle_toggle() {
		$nonce = isset( $_POST['_wpnonce'] ) ? (string) wp_unslash( $_POST['_wpnonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === $nonce || ! function_exists( 'wp_verify_nonce' ) || ! wp_verify_nonce( $nonce, self::TOGGLE_ACTION ) ) {
			self::redirect_back();
			return;
		}

		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			if ( function_exists( 'wp_die' ) ) {
				wp_die( esc_html__( 'You do not have permission to do that.', 'npcink-abilities-toolkit' ) );
			}
			return;
		}

		$turn_on = isset( $_POST['read_only_mode'] ) && '1' === (string) wp_unslash( $_POST['read_only_mode'] );
		if ( $turn_on && function_exists( 'update_option' ) ) {
			update_option( self::OPTION_KEY, '1', false );
		} elseif ( ! $turn_on && function_exists( 'delete_option' ) ) {
			delete_option( self::OPTION_KEY );
		}

		$target = '';
		if ( isset( $_POST['redirect_to'] ) && function_exists( 'wp_validate_redirect' ) ) {
			// Same-site admin URLs only; anything else falls back to the referer.
			$target = wp_validate_redirect( (string) wp_unslash( $_POST['redirect_to'] ), '' );
		}
		self::redirect_back( $target );
	}

	/**
	 * Returns the admin to the ability status page.
	 *
	 * @param string $target Validated redirect target from the form, if any.
	 * @return void
	 */
	private static function redirect_back( $target = '' ) {
		if ( ! function_exists( 'wp_safe_redirect' ) ) {
			return;
		}

		$referer = function_exists( 'wp_get_referer' ) ? (string) wp_get_referer() : '';
		$url     = '' !== $target ? $target : ( '' !== $referer ? $referer : admin_url( 'tools.php?page=' . Test_Page::MENU_SLUG ) );
		wp_safe_redirect( $url );
		exit;
	}
}
