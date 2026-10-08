<?php
/**
 * One-time post-activation welcome notice for site operators.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Points a fresh activation to the ability status page.
 *
 * The notice is per-user and one-time: activation marks it pending for the
 * activating admin, and viewing the status page (or the dismiss link) clears
 * it. Activation without an admin session (CLI, bulk network activation)
 * marks nothing, so no stale notice appears later.
 */
final class Welcome_Notice {
	const PENDING_META_KEY = 'npcink_abilities_toolkit_welcome_pending';
	const DISMISS_ACTION   = 'npcink_abilities_toolkit_dismiss_welcome_notice';

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'admin_init', array( $this, 'maybe_clear_for_current_request' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss_request' ) );
	}

	/**
	 * Marks the welcome notice pending for the activating admin.
	 *
	 * @return void
	 */
	public static function mark_pending_on_activation() {
		$packages = \Npcink_Abilities_Toolkit\Plugin::instance()->get_enabled_packages();
		if ( empty( $packages['admin_test_page'] ) ) {
			// Without the status page the notice has nowhere to point.
			return;
		}
		if ( ! function_exists( 'update_user_meta' ) || ! function_exists( 'get_current_user_id' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::PENDING_META_KEY, '1' );
	}

	/**
	 * Clears the pending flag when the current request views the status page.
	 *
	 * @return void
	 */
	public function maybe_clear_for_current_request() {
		$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
		if ( ! is_string( $page ) ) {
			return;
		}

		$this->clear_pending_for_page( sanitize_text_field( $page ) );
	}

	/**
	 * Clears the pending flag when the viewed page is the status page.
	 *
	 * @param string $page_slug Admin page slug.
	 * @return void
	 */
	public function clear_pending_for_page( $page_slug ) {
		if ( Test_Page::MENU_SLUG !== (string) $page_slug ) {
			return;
		}

		$this->clear_pending();
	}

	/**
	 * Renders the welcome notice on supported screens.
	 *
	 * @return void
	 */
	public function render_notice() {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! $this->is_supported_screen() || ! $this->is_pending() ) {
			return;
		}
		?>
		<div class="notice notice-success npcink-abilities-toolkit-welcome-notice">
			<p>
				<strong><?php echo esc_html( 'Npcink Abilities Toolkit' ); ?></strong>
				<?php echo esc_html__( 'Review the WordPress abilities this site exposes to AI clients, including which actions are read-only and which require host approval.', 'npcink-abilities-toolkit' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $this->status_page_url() ); ?>"><?php echo esc_html__( 'Open AI Ability Set', 'npcink-abilities-toolkit' ); ?></a>
				<a class="npcink-abilities-toolkit-welcome-notice__dismiss" href="<?php echo esc_url( $this->dismiss_url() ); ?>"><?php echo esc_html__( 'Dismiss', 'npcink-abilities-toolkit' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles the dismiss link and returns the admin to where they were.
	 *
	 * @return void
	 */
	public function handle_dismiss_request() {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			if ( function_exists( 'wp_die' ) ) {
				wp_die( esc_html__( 'You do not have permission to do that.', 'npcink-abilities-toolkit' ) );
			}
			return;
		}

		$dismiss_nonce = isset( $_REQUEST['_wpnonce'] ) ? (string) wp_unslash( $_REQUEST['_wpnonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $dismiss_nonce || ! function_exists( 'wp_verify_nonce' ) || ! wp_verify_nonce( $dismiss_nonce, self::DISMISS_ACTION ) ) {
			$this->redirect_back();
			return;
		}

		$this->clear_pending();
		$this->redirect_back();
	}

	/**
	 * Returns whether the welcome notice is pending for the current admin.
	 *
	 * @return bool
	 */
	private function is_pending() {
		if ( ! function_exists( 'get_user_meta' ) || ! function_exists( 'get_current_user_id' ) ) {
			return false;
		}

		return '1' === (string) get_user_meta( get_current_user_id(), self::PENDING_META_KEY, true );
	}

	/**
	 * Clears the pending flag for the current admin.
	 *
	 * @return void
	 */
	private function clear_pending() {
		if ( ! function_exists( 'delete_user_meta' ) || ! function_exists( 'get_current_user_id' ) ) {
			return;
		}

		delete_user_meta( get_current_user_id(), self::PENDING_META_KEY );
	}

	/**
	 * Returns whether the current admin screen should show the welcome notice.
	 *
	 * @return bool
	 */
	private function is_supported_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! is_object( $screen ) ) {
			return false;
		}

		return in_array( (string) $screen->id, array( 'plugins', 'dashboard' ), true );
	}

	/**
	 * Redirects the dismissal flow back to its referer.
	 *
	 * @return void
	 */
	private function redirect_back() {
		if ( ! function_exists( 'wp_safe_redirect' ) ) {
			return;
		}

		$referer = function_exists( 'wp_get_referer' ) ? (string) wp_get_referer() : '';
		$url     = '' !== $referer ? $referer : admin_url( 'plugins.php' );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Returns the admin status page URL.
	 *
	 * @return string
	 */
	private function status_page_url() {
		if ( function_exists( 'menu_page_url' ) ) {
			$url = menu_page_url( Test_Page::MENU_SLUG, false );
			if ( '' !== $url ) {
				return $url;
			}
		}

		return admin_url( 'tools.php?page=' . Test_Page::MENU_SLUG );
	}

	/**
	 * Returns the dismiss URL for the welcome notice.
	 *
	 * @return string
	 */
	private function dismiss_url() {
		$url = admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION );

		return function_exists( 'wp_nonce_url' ) ? (string) wp_nonce_url( $url, self::DISMISS_ACTION ) : $url;
	}
}
