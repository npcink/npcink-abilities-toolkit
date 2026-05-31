<?php
/**
 * Admin test page for Abilities API endpoints.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Admin;

use Magick_AI_Abilities\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a small operator-facing test surface.
 */
final class Test_Page {
	const OPTION_DEMO_ENABLED = 'magick_ai_abilities_demo_enabled';
	const PARENT_MENU_SLUG    = 'magick-ai';
	const MENU_SLUG           = 'magick-ai-abilities';

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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_menu' ), 40 );

		if ( $this->is_demo_enabled() ) {
			$this->register_demo_ability();
		}
	}

	/**
	 * Registers settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'magick_ai_abilities_test',
			self::OPTION_DEMO_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static function ( $value ) {
					return ! empty( $value ) ? '1' : '';
				},
				'default'           => '',
			)
		);
	}

	/**
	 * Adds the Tools submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		if ( $this->has_magick_parent_menu() ) {
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				__( 'Magick AI Abilities', 'magick-ai-abilities' ),
				__( 'Abilities', 'magick-ai-abilities' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render' ),
				40
			);
			return;
		}

		add_management_page(
			__( 'Magick AI Abilities', 'magick-ai-abilities' ),
			__( 'Abilities API Packages', 'magick-ai-abilities' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Returns whether a Magick AI parent menu was registered by a host plugin.
	 *
	 * @return bool
	 */
	private function has_magick_parent_menu() {
		global $menu;

		foreach ( (array) $menu as $item ) {
			if ( isset( $item[2] ) && self::PARENT_MENU_SLUG === $item[2] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'magick-ai-abilities' ) );
		}

		$abilities_url  = rest_url( 'wp-abilities/v1/abilities' );
		$categories_url = rest_url( 'wp-abilities/v1/categories' );
		$demo_run_url   = rest_url( 'wp-abilities/v1/magick-ai-abilities/site-summary/run' );
		$nonce          = wp_create_nonce( 'wp_rest' );
		$status         = $this->get_environment_status();
		$demo_enabled   = $this->is_demo_enabled();
		$registered     = $this->abilities->all();
		$active_tab     = $this->get_active_tab();
		?>
		<div class="wrap magick-ai-abilities-admin">
			<h1><?php echo esc_html__( 'Magick AI Abilities', 'magick-ai-abilities' ); ?></h1>
			<p><?php echo esc_html__( 'Ability package status, schema visibility, and callback readiness for WordPress Abilities API.', 'magick-ai-abilities' ); ?></p>

			<?php $this->render_admin_styles(); ?>
			<?php $this->render_tab_nav( $active_tab ); ?>

			<div class="magick-ai-abilities-tab-panel">
				<?php
				if ( 'catalog' === $active_tab ) {
					$this->render_ability_catalog( $registered );
				} elseif ( 'smoke' === $active_tab ) {
					$this->render_smoke_tests( $abilities_url, $categories_url, $demo_run_url, $demo_enabled );
				} elseif ( 'advanced' === $active_tab ) {
					$this->render_advanced_checks( $abilities_url, $categories_url, $registered );
				} else {
					$this->render_status_summary( $status, $registered, $demo_enabled );
				}
				?>
			</div>
		</div>

		<script>
		(function () {
			const nonce = <?php echo wp_json_encode( $nonce ); ?>;
			const output = document.getElementById('magick-ai-abilities-admin-output');

			async function runRequest(url) {
				if (!output) {
					return;
				}

				output.value = 'Requesting ' + url + ' ...';
				try {
					const response = await fetch(url, {
						credentials: 'same-origin',
						headers: {
							'X-WP-Nonce': nonce,
							'Accept': 'application/json'
						}
					});
					const text = await response.text();
					let body = text;
					try {
						body = JSON.stringify(JSON.parse(text), null, 2);
					} catch (error) {}
					output.value = 'HTTP ' + response.status + '\\n\\n' + body;
				} catch (error) {
					output.value = String(error && error.message ? error.message : error);
				}
			}

			document.querySelectorAll('[data-magick-ai-abilities-fetch]').forEach(function (button) {
				button.addEventListener('click', function () {
					runRequest(button.getAttribute('data-magick-ai-abilities-fetch'));
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Returns the active admin tab.
	 *
	 * @return string
	 */
	private function get_active_tab() {
		$tabs = array_keys( $this->get_tabs() );
		$tab  = isset( $_GET['maa_tab'] ) ? sanitize_key( wp_unslash( $_GET['maa_tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return in_array( $tab, $tabs, true ) ? $tab : 'overview';
	}

	/**
	 * Returns admin tab labels.
	 *
	 * @return array<string,string>
	 */
	private function get_tabs() {
		return array(
			'overview' => __( 'Overview', 'magick-ai-abilities' ),
			'catalog'  => __( 'Catalog', 'magick-ai-abilities' ),
			'smoke'    => __( 'Smoke tests', 'magick-ai-abilities' ),
			'advanced' => __( 'Advanced', 'magick-ai-abilities' ),
		);
	}

	/**
	 * Renders the tab navigation.
	 *
	 * @param string $active_tab Active tab.
	 * @return void
	 */
	private function render_tab_nav( $active_tab ) {
		$base_url = menu_page_url( self::MENU_SLUG, false );
		?>
		<nav class="nav-tab-wrapper magick-ai-abilities-tabs" aria-label="<?php echo esc_attr__( 'Abilities page sections', 'magick-ai-abilities' ); ?>">
			<?php foreach ( $this->get_tabs() as $tab => $label ) : ?>
				<a class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'maa_tab', $tab, $base_url ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Renders admin page utility styles.
	 *
	 * @return void
	 */
	private function render_admin_styles() {
		?>
		<style>
			.magick-ai-abilities-admin {
				max-width: 1180px;
			}

			.magick-ai-abilities-tabs {
				margin-top: 16px;
			}

			.magick-ai-abilities-tab-panel {
				padding-top: 16px;
			}

			.magick-ai-abilities-summary {
				display: grid;
				gap: 12px;
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
				margin: 12px 0 16px;
				max-width: 960px;
			}

			.magick-ai-abilities-status {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-left-width: 4px;
				padding: 12px;
			}

			.magick-ai-abilities-status.is-ok {
				border-left-color: #00a32a;
			}

			.magick-ai-abilities-status.is-warning,
			.magick-ai-abilities-status.is-inactive {
				border-left-color: #dba617;
			}

			.magick-ai-abilities-status.is-error {
				border-left-color: #d63638;
			}

			.magick-ai-abilities-status__label {
				color: #646970;
				display: block;
				font-size: 12px;
				margin-bottom: 4px;
			}

			.magick-ai-abilities-status__value {
				display: block;
				font-size: 18px;
				font-weight: 600;
				line-height: 1.25;
			}

			.magick-ai-abilities-status__detail {
				color: #646970;
				display: block;
				margin-top: 6px;
			}

			.magick-ai-abilities-actions {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
			}

			.magick-ai-abilities-disclosure {
				background: #fff;
				border: 1px solid #c3c4c7;
				margin-bottom: 12px;
				max-width: 1100px;
			}

			.magick-ai-abilities-disclosure summary {
				cursor: pointer;
				padding: 12px;
			}

			.magick-ai-abilities-disclosure summary:hover {
				background: #f6f7f7;
			}

			.magick-ai-abilities-disclosure__meta {
				color: #646970;
				display: block;
				margin-top: 2px;
			}

			.magick-ai-abilities-disclosure__body {
				border-top: 1px solid #dcdcde;
				padding: 12px;
			}

			.magick-ai-abilities-output,
			.magick-ai-abilities-raw {
				box-sizing: border-box;
				font-family: monospace;
				width: 100%;
			}

			.magick-ai-abilities-output {
				max-width: 960px;
			}

			.magick-ai-abilities-raw {
				background: #fff;
				border: 1px solid #c3c4c7;
				margin: 0;
				overflow: auto;
				padding: 12px;
			}

			.magick-ai-abilities-ready {
				color: #00a32a;
				font-weight: 600;
			}

			.magick-ai-abilities-missing {
				color: #d63638;
				font-weight: 600;
			}
		</style>
		<?php
	}

	/**
	 * Renders compact status for the ability package.
	 *
	 * @param array<string,mixed> $status Environment status.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @param bool                $demo_enabled Whether demo ability is enabled.
	 * @return void
	 */
	private function render_status_summary( array $status, array $registered, bool $demo_enabled ) {
		$ability_api_ready = $status['has_ability_registration'] && $status['has_category_registration'];
		$rest_ready        = $status['has_rest_abilities_route'] && $status['has_rest_categories_route'];
		?>
		<h2><?php echo esc_html__( 'Status', 'magick-ai-abilities' ); ?></h2>
		<div class="magick-ai-abilities-summary" role="list">
			<?php $this->render_status_tile( __( 'WordPress', 'magick-ai-abilities' ), (string) $status['wp_version'], 'ok', __( 'Runtime version detected.', 'magick-ai-abilities' ) ); ?>
			<?php $this->render_status_tile( __( 'Ability API', 'magick-ai-abilities' ), $ability_api_ready ? __( 'available', 'magick-ai-abilities' ) : __( 'unavailable', 'magick-ai-abilities' ), $ability_api_ready ? 'ok' : 'error', __( 'Registration functions and categories.', 'magick-ai-abilities' ) ); ?>
			<?php $this->render_status_tile( __( 'REST routes', 'magick-ai-abilities' ), $rest_ready ? __( 'available', 'magick-ai-abilities' ) : __( 'missing', 'magick-ai-abilities' ), $rest_ready ? 'ok' : 'error', __( 'Discovery endpoints for clients.', 'magick-ai-abilities' ) ); ?>
			<?php $this->render_status_tile( __( 'Registered abilities', 'magick-ai-abilities' ), (string) count( $registered ), empty( $registered ) ? 'warning' : 'ok', __( 'Open Catalog for grouped details.', 'magick-ai-abilities' ) ); ?>
			<?php $this->render_status_tile( __( 'Demo ability', 'magick-ai-abilities' ), $demo_enabled ? __( 'enabled', 'magick-ai-abilities' ) : __( 'disabled', 'magick-ai-abilities' ), $demo_enabled ? 'ok' : 'inactive', __( 'Managed from Smoke tests.', 'magick-ai-abilities' ) ); ?>
		</div>

		<?php $this->render_status_attention( $status, $registered ); ?>

		<p class="description">
			<?php echo esc_html__( 'This page is a package status and smoke-test surface. Catalog rows, REST endpoint values, and raw compatibility dumps are available from the tabs above.', 'magick-ai-abilities' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders one status tile.
	 *
	 * @param string $label Status label.
	 * @param string $value Status value.
	 * @param string $state Status state.
	 * @param string $detail Status detail.
	 * @return void
	 */
	private function render_status_tile( $label, $value, $state, $detail ) {
		?>
		<div class="magick-ai-abilities-status is-<?php echo esc_attr( $state ); ?>" role="listitem">
			<span class="magick-ai-abilities-status__label"><?php echo esc_html( $label ); ?></span>
			<span class="magick-ai-abilities-status__value"><?php echo esc_html( $value ); ?></span>
			<span class="magick-ai-abilities-status__detail"><?php echo esc_html( $detail ); ?></span>
		</div>
		<?php
	}

	/**
	 * Renders only blocking or attention-worthy status notes.
	 *
	 * @param array<string,mixed> $status Environment status.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return void
	 */
	private function render_status_attention( array $status, array $registered ) {
		$messages = array();

		if ( ! $status['has_ability_registration'] || ! $status['has_category_registration'] ) {
			$messages[] = __( 'Abilities API registration functions are unavailable.', 'magick-ai-abilities' );
		}

		if ( ! $status['has_rest_abilities_route'] || ! $status['has_rest_categories_route'] ) {
			$messages[] = __( 'Abilities API REST discovery routes are missing.', 'magick-ai-abilities' );
		}

		if ( empty( $registered ) ) {
			$messages[] = __( 'No abilities are registered by this package yet.', 'magick-ai-abilities' );
		}

		if ( empty( $messages ) ) {
			return;
		}
		?>
		<div class="notice notice-warning inline">
			<ul>
				<?php foreach ( $messages as $message ) : ?>
					<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Renders a scannable registered ability catalog.
	 *
	 * @param array<string,array<string,mixed>> $registered Registered abilities.
	 * @return void
	 */
	private function render_ability_catalog( array $registered ) {
		ksort( $registered );
		$groups = $this->group_abilities_by_risk( $registered );
		?>
		<h2><?php echo esc_html__( 'Registered Ability Catalog', 'magick-ai-abilities' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Catalog rows are grouped by risk and collapsed by default so readiness issues are easier to scan before opening details.', 'magick-ai-abilities' ); ?>
		</p>

		<?php if ( empty( $registered ) ) : ?>
			<table class="widefat striped" style="max-width: 1100px;">
				<tbody>
					<tr>
						<td><?php echo esc_html__( 'No abilities are registered by this package yet.', 'magick-ai-abilities' ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php return; ?>
		<?php endif; ?>

		<?php foreach ( $groups as $group_key => $abilities ) : ?>
			<?php
			if ( empty( $abilities ) ) {
				continue;
			}

			$missing_callbacks = 0;
			foreach ( $abilities as $definition ) {
				if ( ! is_callable( $definition['execute_callback'] ?? null ) ) {
					++$missing_callbacks;
				}
			}
			?>
			<details class="magick-ai-abilities-disclosure">
				<summary>
					<strong><?php echo esc_html( $this->get_risk_group_label( $group_key ) ); ?></strong>
					<span class="magick-ai-abilities-disclosure__meta">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: ability count, 2: missing callback count. */
								__( '%1$d abilities, %2$d callback issues', 'magick-ai-abilities' ),
								count( $abilities ),
								$missing_callbacks
							)
						);
						?>
					</span>
				</summary>
				<div class="magick-ai-abilities-disclosure__body">
					<?php $this->render_ability_table( $abilities ); ?>
				</div>
			</details>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Groups abilities by risk level.
	 *
	 * @param array<string,array<string,mixed>> $registered Registered abilities.
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	private function group_abilities_by_risk( array $registered ) {
		$groups = array(
			'read'        => array(),
			'write'       => array(),
			'destructive' => array(),
			'other'       => array(),
		);

		foreach ( $registered as $ability_id => $definition ) {
			$definition = is_array( $definition ) ? $definition : array();
			$risk       = (string) ( $definition['risk_level'] ?? 'other' );
			$group      = isset( $groups[ $risk ] ) ? $risk : 'other';

			$groups[ $group ][ $ability_id ] = $definition;
		}

		return $groups;
	}

	/**
	 * Returns a risk group label.
	 *
	 * @param string $group_key Group key.
	 * @return string
	 */
	private function get_risk_group_label( $group_key ) {
		$labels = array(
			'read'        => __( 'Read abilities', 'magick-ai-abilities' ),
			'write'       => __( 'Write proposal abilities', 'magick-ai-abilities' ),
			'destructive' => __( 'Destructive abilities', 'magick-ai-abilities' ),
			'other'       => __( 'Other abilities', 'magick-ai-abilities' ),
		);

		return $labels[ $group_key ] ?? $labels['other'];
	}

	/**
	 * Renders one ability table.
	 *
	 * @param array<string,array<string,mixed>> $abilities Ability definitions.
	 * @return void
	 */
	private function render_ability_table( array $abilities ) {
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Ability ID', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Category', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Risk', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Ready', 'magick-ai-abilities' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $abilities as $ability_id => $definition ) : ?>
					<?php
					$has_callback = is_callable( $definition['execute_callback'] ?? null );
					$has_input    = is_array( $definition['input_schema'] ?? null );
					$has_output   = is_array( $definition['output_schema'] ?? null );
					?>
					<tr>
						<td><code><?php echo esc_html( (string) $ability_id ); ?></code></td>
						<td><code><?php echo esc_html( (string) ( $definition['category'] ?? '-' ) ); ?></code></td>
						<td><code><?php echo esc_html( (string) ( $definition['risk_level'] ?? '-' ) ); ?></code></td>
						<td>
							<span class="<?php echo $has_callback ? 'magick-ai-abilities-ready' : 'magick-ai-abilities-missing'; ?>">
								<?php echo esc_html( $has_callback ? __( 'available', 'magick-ai-abilities' ) : __( 'missing', 'magick-ai-abilities' ) ); ?>
							</span>
							<br />
							<small><?php echo esc_html( sprintf( 'input:%s output:%s', $has_input ? 'yes' : 'no', $has_output ? 'yes' : 'no' ) ); ?></small>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders REST smoke-test controls.
	 *
	 * @param string $abilities_url Abilities endpoint URL.
	 * @param string $categories_url Categories endpoint URL.
	 * @param string $demo_run_url Demo run endpoint URL.
	 * @param bool   $demo_enabled Whether demo ability is enabled.
	 * @return void
	 */
	private function render_smoke_tests( $abilities_url, $categories_url, $demo_run_url, $demo_enabled ) {
		?>
		<h2><?php echo esc_html__( 'REST endpoints and browser tests', 'magick-ai-abilities' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Run manual REST checks with the current wp-admin session and REST nonce.', 'magick-ai-abilities' ); ?>
		</p>

		<table class="widefat striped" style="max-width: 960px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Current User', 'magick-ai-abilities' ); ?></th>
					<td><?php echo esc_html( wp_get_current_user()->user_login ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Browser Auth', 'magick-ai-abilities' ); ?></th>
					<td><?php echo esc_html__( 'Buttons use the current wp-admin session with an X-WP-Nonce header. External clients should use WordPress REST authentication such as application passwords.', 'magick-ai-abilities' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p class="magick-ai-abilities-actions">
			<button type="button" class="button button-primary" data-magick-ai-abilities-fetch="<?php echo esc_url( $abilities_url ); ?>">
				<?php echo esc_html__( 'Fetch Abilities', 'magick-ai-abilities' ); ?>
			</button>
			<button type="button" class="button" data-magick-ai-abilities-fetch="<?php echo esc_url( $categories_url ); ?>">
				<?php echo esc_html__( 'Fetch Categories', 'magick-ai-abilities' ); ?>
			</button>
			<button type="button" class="button" data-magick-ai-abilities-fetch="<?php echo esc_url( $demo_run_url ); ?>" <?php disabled( ! $demo_enabled ); ?>>
				<?php echo esc_html__( 'Run Demo Ability', 'magick-ai-abilities' ); ?>
			</button>
		</p>

		<textarea id="magick-ai-abilities-admin-output" class="magick-ai-abilities-output" readonly rows="14"></textarea>

		<details class="magick-ai-abilities-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Demo ability control', 'magick-ai-abilities' ); ?></strong>
				<span class="magick-ai-abilities-disclosure__meta"><?php echo esc_html__( 'Enable or disable the read-only smoke ability.', 'magick-ai-abilities' ); ?></span>
			</summary>
			<div class="magick-ai-abilities-disclosure__body">
				<form method="post" action="options.php">
					<?php settings_fields( 'magick_ai_abilities_test' ); ?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_DEMO_ENABLED ); ?>" value="1" <?php checked( $demo_enabled ); ?> />
						<?php echo esc_html__( 'Enable demo read-only ability: magick-ai-abilities/site-summary', 'magick-ai-abilities' ); ?>
					</label>
					<?php submit_button( __( 'Save', 'magick-ai-abilities' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</details>
		<?php
	}

	/**
	 * Renders advanced checks and raw dumps.
	 *
	 * @param string              $abilities_url Abilities endpoint URL.
	 * @param string              $categories_url Categories endpoint URL.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return void
	 */
	private function render_advanced_checks( $abilities_url, $categories_url, array $registered ) {
		?>
		<h2><?php echo esc_html__( 'Advanced Checks', 'magick-ai-abilities' ); ?></h2>

		<details class="magick-ai-abilities-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Endpoint values', 'magick-ai-abilities' ); ?></strong>
				<span class="magick-ai-abilities-disclosure__meta"><?php echo esc_html__( 'Raw REST values for client setup and support checks.', 'magick-ai-abilities' ); ?></span>
			</summary>
			<div class="magick-ai-abilities-disclosure__body">
				<table class="widefat striped">
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Abilities Endpoint', 'magick-ai-abilities' ); ?></th>
							<td><code><?php echo esc_html( $abilities_url ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Categories Endpoint', 'magick-ai-abilities' ); ?></th>
							<td><code><?php echo esc_html( $categories_url ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Magick App Key', 'magick-ai-abilities' ); ?></th>
							<td><?php echo esc_html__( 'Not used by wp-abilities/v1 endpoints.', 'magick-ai-abilities' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</details>

		<details class="magick-ai-abilities-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Raw ability ids', 'magick-ai-abilities' ); ?></strong>
				<span class="magick-ai-abilities-disclosure__meta"><?php echo esc_html__( 'Compatibility dump for catalog audits.', 'magick-ai-abilities' ); ?></span>
			</summary>
			<div class="magick-ai-abilities-disclosure__body">
				<pre class="magick-ai-abilities-raw"><?php echo esc_html( wp_json_encode( array_keys( $registered ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			</div>
		</details>
		<?php
	}

	/**
	 * Registers a small read-only demo ability.
	 *
	 * @return void
	 */
	private function register_demo_ability() {
		$this->abilities->add_readonly(
			'magick-ai-abilities/site-summary',
			array(
				'label'            => __( 'Abilities API Site Summary', 'magick-ai-abilities' ),
				'description'      => __( 'Returns a small site summary for testing WordPress Abilities API authentication and execution.', 'magick-ai-abilities' ),
				'capability'       => 'manage_options',
				'input_schema'     => array(
					'type'                 => 'object',
					'additionalProperties' => false,
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'site_name'      => array( 'type' => 'string' ),
						'site_url'       => array( 'type' => 'string' ),
						'wp_version'     => array( 'type' => 'string' ),
						'plugin_version' => array( 'type' => 'string' ),
						'user'           => array( 'type' => 'string' ),
					),
				),
				'execute_callback' => static function () {
					return array(
						'site_name'      => get_bloginfo( 'name' ),
						'site_url'       => home_url(),
						'wp_version'     => get_bloginfo( 'version' ),
						'plugin_version' => defined( 'MAGICK_AI_ABILITIES_VERSION' ) ? MAGICK_AI_ABILITIES_VERSION : '',
						'user'           => wp_get_current_user()->user_login,
					);
				},
			)
		);
	}

	/**
	 * Returns environment status for the admin test page.
	 *
	 * @return array<string,mixed>
	 */
	private function get_environment_status() {
		$routes = array();
		if ( function_exists( 'rest_get_server' ) ) {
			$server = rest_get_server();
			$routes = is_object( $server ) && method_exists( $server, 'get_routes' ) ? $server->get_routes() : array();
		}

		return array(
			'wp_version'                 => function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'version' ) : '',
			'has_ability_registration'   => function_exists( 'wp_register_ability' ),
			'has_category_registration'  => function_exists( 'wp_register_ability_category' ),
			'has_rest_abilities_route'   => isset( $routes['/wp-abilities/v1/abilities'] ),
			'has_rest_categories_route'  => isset( $routes['/wp-abilities/v1/categories'] ),
		);
	}

	/**
	 * Returns whether the demo ability is enabled.
	 *
	 * @return bool
	 */
	private function is_demo_enabled() {
		return '1' === (string) get_option( self::OPTION_DEMO_ENABLED, '' );
	}
}
