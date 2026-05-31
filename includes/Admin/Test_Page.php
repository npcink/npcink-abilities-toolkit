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
		?>
		<div class="wrap magick-ai-abilities-admin">
			<h1><?php echo esc_html__( 'Magick AI Abilities', 'magick-ai-abilities' ); ?></h1>
			<p><?php echo esc_html__( 'Ability package status, schema visibility, and callback readiness for WordPress Abilities API.', 'magick-ai-abilities' ); ?></p>

			<?php $this->render_status_summary( $status, $registered, $demo_enabled ); ?>
			<?php $this->render_ability_catalog( $registered ); ?>

			<h2><?php echo esc_html__( 'Advanced Checks', 'magick-ai-abilities' ); ?></h2>
			<details style="max-width: 960px; margin-bottom: 12px;">
				<summary style="cursor: pointer;">
					<strong><?php echo esc_html__( 'REST endpoints and browser tests', 'magick-ai-abilities' ); ?></strong>
					<span style="color: #646970;"><?php echo esc_html__( 'Use the current wp-admin REST nonce for manual smoke checks.', 'magick-ai-abilities' ); ?></span>
				</summary>

				<table class="widefat striped" style="margin-top: 8px;">
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Current User', 'magick-ai-abilities' ); ?></th>
							<td><?php echo esc_html( wp_get_current_user()->user_login ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Browser Auth', 'magick-ai-abilities' ); ?></th>
							<td><?php echo esc_html__( 'The buttons below use the current wp-admin session with an X-WP-Nonce header. External clients should use WordPress REST authentication such as application passwords.', 'magick-ai-abilities' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Magick App Key', 'magick-ai-abilities' ); ?></th>
							<td><?php echo esc_html__( 'Not used by wp-abilities/v1 endpoints.', 'magick-ai-abilities' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Abilities Endpoint', 'magick-ai-abilities' ); ?></th>
							<td><code><?php echo esc_html( $abilities_url ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Categories Endpoint', 'magick-ai-abilities' ); ?></th>
							<td><code><?php echo esc_html( $categories_url ); ?></code></td>
						</tr>
					</tbody>
				</table>

				<p>
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

				<textarea id="magick-ai-abilities-admin-output" readonly rows="14" style="width: 100%; max-width: 960px; font-family: monospace;"></textarea>
			</details>

			<details style="max-width: 960px; margin-bottom: 12px;">
				<summary style="cursor: pointer;">
					<strong><?php echo esc_html__( 'Demo ability control', 'magick-ai-abilities' ); ?></strong>
					<span style="color: #646970;"><?php echo esc_html__( 'Enable or disable the read-only smoke ability.', 'magick-ai-abilities' ); ?></span>
				</summary>
				<form method="post" action="options.php" style="margin-top: 8px;">
					<?php settings_fields( 'magick_ai_abilities_test' ); ?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_DEMO_ENABLED ); ?>" value="1" <?php checked( $demo_enabled ); ?> />
						<?php echo esc_html__( 'Enable demo read-only ability: magick-ai-abilities/site-summary', 'magick-ai-abilities' ); ?>
					</label>
					<?php submit_button( __( 'Save', 'magick-ai-abilities' ), 'secondary', 'submit', false ); ?>
				</form>
			</details>

			<details style="max-width: 960px;">
				<summary style="cursor: pointer;">
					<strong><?php echo esc_html__( 'Raw ability ids', 'magick-ai-abilities' ); ?></strong>
					<span style="color: #646970;"><?php echo esc_html__( 'Compatibility dump for catalog audits.', 'magick-ai-abilities' ); ?></span>
				</summary>
				<pre style="overflow: auto; padding: 12px; background: #fff; border: 1px solid #c3c4c7;"><?php echo esc_html( wp_json_encode( array_keys( $registered ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			</details>
		</div>

		<script>
		(function () {
			const nonce = <?php echo wp_json_encode( $nonce ); ?>;
			const output = document.getElementById('magick-ai-abilities-admin-output');

			async function runRequest(url) {
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
	 * Renders compact status for the ability package.
	 *
	 * @param array<string,mixed> $status Environment status.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @param bool                $demo_enabled Whether demo ability is enabled.
	 * @return void
	 */
	private function render_status_summary( array $status, array $registered, bool $demo_enabled ) {
		?>
		<h2><?php echo esc_html__( 'Status', 'magick-ai-abilities' ); ?></h2>
		<table class="widefat fixed striped" style="max-width: 960px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'WordPress', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Ability API', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'REST routes', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Registered abilities', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Demo ability', 'magick-ai-abilities' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo esc_html( (string) $status['wp_version'] ); ?></td>
					<td><?php echo esc_html( $status['has_ability_registration'] && $status['has_category_registration'] ? __( 'available', 'magick-ai-abilities' ) : __( 'unavailable', 'magick-ai-abilities' ) ); ?></td>
					<td><?php echo esc_html( $status['has_rest_abilities_route'] && $status['has_rest_categories_route'] ? __( 'available', 'magick-ai-abilities' ) : __( 'missing', 'magick-ai-abilities' ) ); ?></td>
					<td><?php echo esc_html( (string) count( $registered ) ); ?></td>
					<td><?php echo esc_html( $demo_enabled ? __( 'enabled', 'magick-ai-abilities' ) : __( 'disabled', 'magick-ai-abilities' ) ); ?></td>
				</tr>
			</tbody>
		</table>
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
		?>
		<h2><?php echo esc_html__( 'Registered Ability Catalog', 'magick-ai-abilities' ); ?></h2>
		<table class="widefat striped" style="max-width: 1100px;">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html__( 'Ability ID', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Category', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Risk', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Callback', 'magick-ai-abilities' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Schemas', 'magick-ai-abilities' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $registered ) ) : ?>
					<tr>
						<td colspan="5"><?php echo esc_html__( 'No abilities are registered by this package yet.', 'magick-ai-abilities' ); ?></td>
					</tr>
				<?php endif; ?>
				<?php foreach ( $registered as $ability_id => $definition ) : ?>
					<?php
					$definition = is_array( $definition ) ? $definition : array();
					$has_input  = is_array( $definition['input_schema'] ?? null );
					$has_output = is_array( $definition['output_schema'] ?? null );
					?>
					<tr>
						<td><code><?php echo esc_html( (string) $ability_id ); ?></code></td>
						<td><code><?php echo esc_html( (string) ( $definition['category'] ?? '-' ) ); ?></code></td>
						<td><code><?php echo esc_html( (string) ( $definition['risk_level'] ?? '-' ) ); ?></code></td>
						<td><?php echo esc_html( is_callable( $definition['execute_callback'] ?? null ) ? __( 'available', 'magick-ai-abilities' ) : __( 'missing', 'magick-ai-abilities' ) ); ?></td>
						<td><?php echo esc_html( sprintf( 'input:%s output:%s', $has_input ? 'yes' : 'no', $has_output ? 'yes' : 'no' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
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
