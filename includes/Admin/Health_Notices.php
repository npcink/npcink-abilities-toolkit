<?php
/**
 * Fail-loud admin health notices for ability registration.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Surfaces broken ability registration on core admin screens.
 *
 * The admin page shows the same conditions on demand; these notices push the
 * two silent-failure modes (missing Abilities API functions, empty catalog)
 * to the plugins and dashboard screens where an operator will see them.
 */
final class Health_Notices {
	const DISMISS_ACTION = 'npcink_abilities_toolkit_dismiss_health_notice';
	const USER_META_KEY  = 'npcink_abilities_toolkit_health_notices_dismissed';
	const DOCS_TROUBLESHOOTING_URL = 'https://github.com/npcink/npcink-abilities-toolkit/blob/master/docs/troubleshooting.md';

	/**
	 * Ability registrar.
	 *
	 * @var Ability_Registrar
	 */
	private $abilities;

	/**
	 * Constructor.
	 *
	 * @param Ability_Registrar $abilities Ability registrar.
	 */
	public function __construct( Ability_Registrar $abilities ) {
		$this->abilities = $abilities;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss_request' ) );
	}

	/**
	 * Returns the currently active health notices keyed by notice id.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_active_notices() {
		$notices = array();
		if ( ! function_exists( 'wp_register_ability' ) || ! function_exists( 'wp_register_ability_category' ) ) {
			$notices['abilities_api_missing'] = array(
				'type'    => 'error',
				'message' => __( 'Npcink Abilities Toolkit is active, but the WordPress Abilities API registration functions are unavailable. Update WordPress to 6.9 or later, or enable the WordPress Abilities API baseline, before AI clients can discover this site\'s abilities.', 'npcink-abilities-toolkit' ),
			);
		}

		if ( empty( $this->abilities->all() ) ) {
			$notices['catalog_empty'] = array(
				'type'    => 'warning',
				'message' => __( 'Npcink Abilities Toolkit is active, but no abilities are registered on this site yet. Review the ability status page, or check that a host product has not disabled the built-in ability packages.', 'npcink-abilities-toolkit' ),
			);
		}

		if ( function_exists( 'apply_filters' ) && apply_filters( 'npcink_abilities_toolkit_write_commit_allowed', false, 'npcink-abilities-toolkit/host-approval-probe', array(), array() ) ) {
			$notices['host_approval_unconditional'] = array(
				'type'    => 'warning',
				'message' => __( 'A host integration allows every Toolkit write and destructive commit through the npcink_abilities_toolkit_write_commit_allowed filter. Scope it to recorded approvals for specific ability ids and idempotency keys, as the host approval contract describes.', 'npcink-abilities-toolkit' ),
			);
		}
		return $notices;
	}

	/**
	 * Renders active health notices on supported screens.
	 *
	 * @return void
	 */
	public function render_notices() {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! $this->is_supported_screen() ) {
			return;
		}

		$this->render_redirect_error();
		$notices = $this->get_active_notices();
		if ( empty( $notices ) ) {
			return;
		}
		$dismissed = $this->get_dismissed_notices();
		foreach ( $notices as $id => $notice ) {
			if ( $this->is_dismissed( $dismissed, (string) $id ) ) {
				continue;
			}
			$this->render_notice( (string) $id, $notice );
		}
	}

	/**
	 * Persists one notice dismissal for the current admin user.
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
			$this->redirect_back( 'nonce' );
			return;
		}

		$notice_id = isset( $_REQUEST['notice'] ) ? sanitize_key( wp_unslash( $_REQUEST['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $notice_id ) {
			$this->redirect_back();
			return;
		}
		$dismissed = $this->get_dismissed_notices();
		if ( ! array_key_exists( $notice_id, $this->get_active_notices() ) ) {
			$this->redirect_back();
			return;
		}
		if ( ! function_exists( 'update_user_meta' ) || ! function_exists( 'get_current_user_id' ) ) {
			// Persistence is impossible in this context; still send the user back.
			$this->redirect_back();
			return;
		}
		$dismissed[ $notice_id ] = $this->plugin_version();
		update_user_meta( get_current_user_id(), self::USER_META_KEY, $dismissed );

		$this->redirect_back();
	}

	/**
	 * Redirects the dismissal flow back to its referer so admin-post never
	 * renders a blank dead-end page.
	 *
	 * @param string $error_code Optional machine error code surfaced after
	 *                           the redirect.
	 * @return void
	 */
	private function redirect_back( $error_code = '' ) {
		if ( ! function_exists( 'wp_safe_redirect' ) ) {
			return;
		}
		$referer = function_exists( 'wp_get_referer' ) ? (string) wp_get_referer() : '';
		$url     = '' !== $referer ? $referer : admin_url( 'plugins.php' );
		if ( function_exists( 'remove_query_arg' ) ) {
			$url = remove_query_arg( 'npcink_abilities_toolkit_notice_error', $url );
		}
		if ( '' !== $error_code && function_exists( 'add_query_arg' ) ) {
			$url = add_query_arg( 'npcink_abilities_toolkit_notice_error', sanitize_key( $error_code ), $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Renders the inline error for a failed dismissal redirect.
	 *
	 * @return void
	 */
	private function render_redirect_error() {
		$error_code = isset( $_REQUEST['npcink_abilities_toolkit_notice_error'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( wp_unslash( $_REQUEST['npcink_abilities_toolkit_notice_error'] ) )
			: '';
		if ( 'nonce' !== $error_code ) {
			return;
		}
		?>
		<div class="notice notice-error inline npcink-abilities-toolkit-health-notice">
			<p><?php echo esc_html__( 'The dismissal link expired. Reload the page and try again.', 'npcink-abilities-toolkit' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders one dismissible health notice.
	 *
	 * @param string              $id Notice id.
	 * @param array<string,mixed> $notice Notice args.
	 * @return void
	 */
	private function render_notice( $id, array $notice ) {
		$type = isset( $notice['type'] ) ? sanitize_key( (string) $notice['type'] ) : 'warning';
		?>
		<div class="notice notice-<?php echo esc_attr( 'error' === $type ? 'error' : 'warning' ); ?> npcink-abilities-toolkit-health-notice" data-notice-id="<?php echo esc_attr( $id ); ?>">
			<p>
				<?php echo esc_html( (string) $notice['message'] ); ?>
			</p>
			<p>
				<?php if ( $this->status_page_available() ) : ?>
					<a class="button button-small" href="<?php echo esc_url( $this->status_page_url() ); ?>"><?php echo esc_html__( 'Review ability status', 'npcink-abilities-toolkit' ); ?></a>
				<?php endif; ?>
				<a class="button button-small" href="<?php echo esc_url( $this->troubleshooting_url() ); ?>"><?php echo esc_html__( 'Troubleshooting docs', 'npcink-abilities-toolkit' ); ?></a>
				<a class="npcink-abilities-toolkit-health-notice__dismiss" href="<?php echo esc_url( $this->dismiss_url( $id ) ); ?>"><?php echo esc_html__( 'Dismiss', 'npcink-abilities-toolkit' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Returns whether the current admin screen should show health notices.
	 *
	 * @return bool
	 */
	private function is_supported_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		$id     = is_object( $screen ) && isset( $screen->id ) ? (string) $screen->id : '';

		return in_array( $id, array( 'plugins', 'dashboard' ), true );
	}

	/**
	 * Returns per-user dismissed notices keyed by id with the dismissing version.
	 *
	 * @return array<string,string>
	 */
	private function get_dismissed_notices() {
		if ( ! function_exists( 'get_user_meta' ) || ! function_exists( 'get_current_user_id' ) ) {
			return array();
		}

		$stored = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		return is_array( $stored ) ? array_map( 'strval', $stored ) : array();
	}

	/**
	 * Returns whether one notice id was dismissed for the running plugin version.
	 *
	 * @param array<string,string> $dismissed Dismissed notices.
	 * @param string               $id Notice id.
	 * @return bool
	 */
	private function is_dismissed( array $dismissed, $id ) {
		return isset( $dismissed[ $id ] ) && $dismissed[ $id ] === $this->plugin_version();
	}

	/**
	 * Returns the dismiss URL for one notice.
	 *
	 * @param string $id Notice id.
	 * @return string
	 */
	private function dismiss_url( $id ) {
		$url = admin_url( 'admin-post.php?action=' . self::DISMISS_ACTION . '&notice=' . rawurlencode( $id ) );

		return (string) wp_nonce_url( $url, self::DISMISS_ACTION );
	}

	/**
	 * Returns whether the admin status page is registered.
	 *
	 * The notices stay booted when a host disables the admin_test_page
	 * package, so the status-page link only renders when the page exists.
	 *
	 * @return bool
	 */
	private function status_page_available() {
		if ( ! class_exists( \Npcink_Abilities_Toolkit\Plugin::class ) || ! method_exists( \Npcink_Abilities_Toolkit\Plugin::class, 'get_enabled_packages' ) ) {
			return false;
		}

		$packages = \Npcink_Abilities_Toolkit\Plugin::instance()->get_enabled_packages();

		return ! empty( $packages['admin_test_page'] );
	}

	/**
	 * Returns the admin status page URL.
	 *
	 * @return string
	 */
	private function status_page_url() {
		if ( function_exists( 'menu_page_url' ) ) {
			$url = menu_page_url( Test_Page::MENU_SLUG, false );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return admin_url( 'tools.php?page=' . Test_Page::MENU_SLUG );
	}

	/**
	 * Returns the troubleshooting documentation URL.
	 *
	 * @return string
	 */
	private function troubleshooting_url() {
		return self::DOCS_TROUBLESHOOTING_URL;
	}

	/**
	 * Returns the running plugin version.
	 *
	 * @return string
	 */
	private function plugin_version() {
		return defined( 'NPCINK_ABILITIES_TOOLKIT_VERSION' ) ? (string) NPCINK_ABILITIES_TOOLKIT_VERSION : '0.0.0';
	}
}
