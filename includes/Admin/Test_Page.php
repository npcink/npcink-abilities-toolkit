<?php
/**
 * Admin test page for Abilities API endpoints.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a small operator-facing test surface.
 */
final class Test_Page {
	const OPTION_DEMO_ENABLED = 'npcink_abilities_toolkit_demo_enabled';
	const PARENT_MENU_SLUG    = 'npcink-ai';
	const MENU_SLUG           = 'npcink-abilities-toolkit';
	const ADMIN_REQUEST_ACTION = 'npcink_abilities_admin_request';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

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
			'npcink_abilities_toolkit_test',
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
	 * Enqueues the admin page assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$allowed_hooks = array(
			self::PARENT_MENU_SLUG . '_page_' . self::MENU_SLUG,
			'tools_page_' . self::MENU_SLUG,
		);
		if ( ! in_array( (string) $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		$handle  = 'npcink-abilities-toolkit-admin';
		$version = defined( 'NPCINK_ABILITIES_TOOLKIT_VERSION' ) ? NPCINK_ABILITIES_TOOLKIT_VERSION : '1.0.0';

		wp_enqueue_style(
			$handle,
			plugins_url( 'assets/admin.css', NPCINK_ABILITIES_TOOLKIT_FILE ),
			array(),
			$version
		);

		wp_enqueue_script(
			$handle,
			plugins_url( 'assets/admin.js', NPCINK_ABILITIES_TOOLKIT_FILE ),
			array(),
			$version,
			true
		);
	}

	/**
	 * Adds the Tools submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		if ( $this->has_npcink_parent_menu() ) {
			add_submenu_page(
				self::PARENT_MENU_SLUG,
				__( 'Npcink Abilities Toolkit', 'npcink-abilities-toolkit' ),
				__( 'Abilities', 'npcink-abilities-toolkit' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render' ),
				40
			);
			return;
		}

		add_management_page(
			__( 'Npcink Abilities Toolkit', 'npcink-abilities-toolkit' ),
			__( 'Abilities API Packages', 'npcink-abilities-toolkit' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Returns whether a Npcink AI parent menu was registered by a host plugin.
	 *
	 * @return bool
	 */
	private function has_npcink_parent_menu() {
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'npcink-abilities-toolkit' ) );
		}

		$abilities_url  = rest_url( 'wp-abilities/v1/abilities' );
		$categories_url = rest_url( 'wp-abilities/v1/categories' );
		$demo_run_url   = rest_url( 'wp-abilities/v1/npcink-abilities-toolkit/site-summary/run' );
		$status         = $this->get_environment_status();
		$demo_enabled   = $this->is_demo_enabled();
		$registered     = $this->abilities->all();
		$active_tab     = $this->get_active_tab();
		?>
		<div class="wrap npcink-abilities-toolkit-admin" data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'npcink-abilities-toolkit' ); ?>">
			<h1><?php echo esc_html__( 'Npcink Abilities Toolkit', 'npcink-abilities-toolkit' ); ?></h1>
			<p><?php echo esc_html__( 'Ability package status, schema visibility, and callback readiness for WordPress Abilities API.', 'npcink-abilities-toolkit' ); ?></p>

			<?php $this->render_tab_nav( $active_tab ); ?>

			<div class="npcink-abilities-toolkit-tab-panel">
				<?php
				if ( 'catalog' === $active_tab ) {
					$this->render_ability_catalog( $registered );
				} elseif ( 'smoke' === $active_tab ) {
					$this->render_smoke_tests( $abilities_url, $categories_url, $demo_run_url, $demo_enabled, $registered );
				} else {
					$this->render_status_summary( $status, $registered, $demo_enabled );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns the active admin tab.
	 *
	 * @return string
	 */
	private function get_active_tab() {
		$tabs = array_keys( $this->get_tabs() );
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return 'overview';
		}

		$tab = $this->get_admin_query_arg( 'npcink_abilities_toolkit_tab', 'overview' );

		return in_array( $tab, $tabs, true ) ? $tab : 'overview';
	}

	/**
	 * Returns admin tab labels.
	 *
	 * @return array<string,string>
	 */
	private function get_tabs() {
		return array(
			'overview' => __( 'Overview', 'npcink-abilities-toolkit' ),
			'catalog'  => __( 'Catalog', 'npcink-abilities-toolkit' ),
			'smoke'    => __( 'Smoke tests', 'npcink-abilities-toolkit' ),
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
		<nav class="nav-tab-wrapper npcink-abilities-toolkit-tabs" aria-label="<?php echo esc_attr__( 'Abilities page sections', 'npcink-abilities-toolkit' ); ?>">
			<?php foreach ( $this->get_tabs() as $tab => $label ) : ?>
				<a class="<?php echo esc_attr( 'nav-tab ' . ( $active_tab === $tab ? 'nav-tab-active' : '' ) ); ?>" href="<?php echo esc_url( $this->add_admin_request_nonce( add_query_arg( 'npcink_abilities_toolkit_tab', $tab, $base_url ) ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Adds the admin request nonce to an internal admin URL.
	 *
	 * @param string $url Admin URL.
	 * @return string
	 */
	private function add_admin_request_nonce( $url ) {
		return add_query_arg( 'npcink_abilities_toolkit_nonce', wp_create_nonce( self::ADMIN_REQUEST_ACTION ), $url );
	}

	/**
	 * Returns whether the current admin request has a valid page nonce.
	 *
	 * @return bool
	 */
	private function has_valid_admin_request_nonce() {
		$nonce = filter_input( INPUT_GET, 'npcink_abilities_toolkit_nonce', FILTER_UNSAFE_RAW );
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( sanitize_text_field( $nonce ), self::ADMIN_REQUEST_ACTION );
	}

	/**
	 * Returns a sanitized admin query arg when the page nonce is valid.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_admin_query_arg( $key, $default = '' ) {
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return $default;
		}

		$value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );
		if ( ! is_string( $value ) ) {
			return $default;
		}

		return sanitize_text_field( $value );
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
		<h2><?php echo esc_html__( 'Status', 'npcink-abilities-toolkit' ); ?></h2>
		<div class="npcink-abilities-toolkit-summary" role="list">
			<?php $this->render_status_tile( __( 'WordPress', 'npcink-abilities-toolkit' ), (string) $status['wp_version'], 'ok', __( 'Runtime version detected.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'Ability API', 'npcink-abilities-toolkit' ), $ability_api_ready ? __( 'available', 'npcink-abilities-toolkit' ) : __( 'unavailable', 'npcink-abilities-toolkit' ), $ability_api_ready ? 'ok' : 'error', __( 'Registration functions and categories.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'REST routes', 'npcink-abilities-toolkit' ), $rest_ready ? __( 'available', 'npcink-abilities-toolkit' ) : __( 'missing', 'npcink-abilities-toolkit' ), $rest_ready ? 'ok' : 'error', __( 'Discovery endpoints for clients.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'Registered abilities', 'npcink-abilities-toolkit' ), (string) count( $registered ), empty( $registered ) ? 'warning' : 'ok', __( 'Open Catalog for grouped details.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'Demo ability', 'npcink-abilities-toolkit' ), $demo_enabled ? __( 'enabled', 'npcink-abilities-toolkit' ) : __( 'disabled', 'npcink-abilities-toolkit' ), $demo_enabled ? 'ok' : 'inactive', __( 'Managed from Smoke tests.', 'npcink-abilities-toolkit' ) ); ?>
		</div>

		<?php $this->render_status_attention( $status, $registered ); ?>

		<p class="description">
			<?php echo esc_html__( 'This page is a package status and smoke-test surface. Catalog rows are separate, and low-frequency REST details stay under Smoke tests.', 'npcink-abilities-toolkit' ); ?>
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
		<div class="npcink-abilities-toolkit-status is-<?php echo esc_attr( $state ); ?>" role="listitem">
			<span class="npcink-abilities-toolkit-status__label"><?php echo esc_html( $label ); ?></span>
			<span class="npcink-abilities-toolkit-status__value"><?php echo esc_html( $value ); ?></span>
			<span class="npcink-abilities-toolkit-status__detail"><?php echo esc_html( $detail ); ?></span>
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
			$messages[] = __( 'Abilities API registration functions are unavailable.', 'npcink-abilities-toolkit' );
		}

		if ( ! $status['has_rest_abilities_route'] || ! $status['has_rest_categories_route'] ) {
			$messages[] = __( 'Abilities API REST discovery routes are missing.', 'npcink-abilities-toolkit' );
		}

		if ( empty( $registered ) ) {
			$messages[] = __( 'No abilities are registered by this package yet.', 'npcink-abilities-toolkit' );
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
		$filters       = $this->get_catalog_filters();
		$filtered      = $this->filter_ability_catalog( $registered, $filters );
		$total         = count( $filtered );
		$per_page      = (int) $filters['per_page'];
		$total_pages   = max( 1, (int) ceil( $total / $per_page ) );
		$current_page  = min( (int) $filters['paged'], $total_pages );
		$offset        = ( $current_page - 1 ) * $per_page;
		$paged_results = array_slice( $filtered, $offset, $per_page, true );
		$groups        = $this->group_abilities_by_risk( $paged_results );
		?>
		<h2><?php echo esc_html__( 'Registered Ability Catalog', 'npcink-abilities-toolkit' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Filter the catalog before opening grouped readiness details.', 'npcink-abilities-toolkit' ); ?>
		</p>

		<?php if ( empty( $registered ) ) : ?>
			<table class="widefat striped" style="max-width: 1100px;">
				<tbody>
					<tr>
						<td><?php echo esc_html__( 'No abilities are registered by this package yet.', 'npcink-abilities-toolkit' ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php return; ?>
		<?php endif; ?>

		<?php $this->render_catalog_filters( $registered, $filters ); ?>

		<p class="npcink-abilities-toolkit-catalog-meta">
			<?php
			if ( $total > 0 ) {
				echo esc_html(
					sprintf(
						/* translators: 1: first visible row, 2: last visible row, 3: total filtered rows. */
						__( 'Showing %1$d-%2$d of %3$d abilities.', 'npcink-abilities-toolkit' ),
						$offset + 1,
						min( $offset + $per_page, $total ),
						$total
					)
				);
			} else {
				echo esc_html__( 'No abilities match the current filters.', 'npcink-abilities-toolkit' );
			}
			?>
		</p>

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
			<details class="npcink-abilities-toolkit-disclosure">
				<summary>
					<strong><?php echo esc_html( $this->get_risk_group_label( $group_key ) ); ?></strong>
					<span class="npcink-abilities-toolkit-disclosure__meta">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: ability count, 2: missing callback count. */
								__( '%1$d abilities, %2$d callback issues', 'npcink-abilities-toolkit' ),
								count( $abilities ),
								$missing_callbacks
							)
						);
						?>
					</span>
				</summary>
				<div class="npcink-abilities-toolkit-disclosure__body">
					<?php $this->render_ability_table( $abilities ); ?>
				</div>
			</details>
		<?php endforeach; ?>

		<?php $this->render_catalog_pagination( $current_page, $total_pages, $filters ); ?>
		<?php
	}

	/**
	 * Returns catalog request filters.
	 *
	 * @return array<string,mixed>
	 */
	private function get_catalog_filters() {
		$allowed_per_page = array( 25, 50, 100 );
		$allowed_risks    = array( '', 'read', 'write', 'destructive', 'other' );
		if ( ! $this->has_valid_admin_request_nonce() ) {
			return array(
				'query'    => '',
				'risk'     => '',
				'category' => '',
				'per_page' => 25,
				'paged'    => 1,
			);
		}

		$per_page         = absint( $this->get_admin_query_arg( 'npcink_abilities_toolkit_per_page', '25' ) );
		$per_page         = in_array( $per_page, $allowed_per_page, true ) ? $per_page : 25;
		$risk             = sanitize_key( $this->get_admin_query_arg( 'npcink_abilities_toolkit_risk' ) );

		return array(
			'query'    => $this->get_admin_query_arg( 'npcink_abilities_toolkit_query' ),
			'risk'     => in_array( $risk, $allowed_risks, true ) ? $risk : '',
			'category' => sanitize_key( $this->get_admin_query_arg( 'npcink_abilities_toolkit_category' ) ),
			'per_page' => $per_page,
			'paged'    => max( 1, absint( $this->get_admin_query_arg( 'npcink_abilities_toolkit_paged', '1' ) ) ),
		);
	}

	/**
	 * Renders catalog filter controls.
	 *
	 * @param array<string,array<string,mixed>> $registered Registered abilities.
	 * @param array<string,mixed>               $filters Active filters.
	 * @return void
	 */
	private function render_catalog_filters( array $registered, array $filters ) {
		$categories = $this->get_catalog_categories( $registered );
		$base_url   = menu_page_url( self::MENU_SLUG, false );
		?>
		<form class="npcink-abilities-toolkit-filter" method="get" action="<?php echo esc_url( $base_url ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
			<input type="hidden" name="npcink_abilities_toolkit_tab" value="catalog" />
			<input type="hidden" name="npcink_abilities_toolkit_paged" value="1" />
			<?php wp_nonce_field( self::ADMIN_REQUEST_ACTION, 'npcink_abilities_toolkit_nonce', false ); ?>

			<label>
				<?php echo esc_html__( 'Search', 'npcink-abilities-toolkit' ); ?>
				<input type="search" name="npcink_abilities_toolkit_query" value="<?php echo esc_attr( (string) $filters['query'] ); ?>" placeholder="<?php echo esc_attr__( 'Ability ID, category, or label', 'npcink-abilities-toolkit' ); ?>" />
			</label>

			<label>
				<?php echo esc_html__( 'Risk', 'npcink-abilities-toolkit' ); ?>
				<select name="npcink_abilities_toolkit_risk">
					<option value=""><?php echo esc_html__( 'All risks', 'npcink-abilities-toolkit' ); ?></option>
					<option value="read" <?php selected( $filters['risk'], 'read' ); ?>><?php echo esc_html__( 'Read', 'npcink-abilities-toolkit' ); ?></option>
					<option value="write" <?php selected( $filters['risk'], 'write' ); ?>><?php echo esc_html__( 'Write', 'npcink-abilities-toolkit' ); ?></option>
					<option value="destructive" <?php selected( $filters['risk'], 'destructive' ); ?>><?php echo esc_html__( 'Destructive', 'npcink-abilities-toolkit' ); ?></option>
					<option value="other" <?php selected( $filters['risk'], 'other' ); ?>><?php echo esc_html__( 'Other', 'npcink-abilities-toolkit' ); ?></option>
				</select>
			</label>

			<label>
				<?php echo esc_html__( 'Category', 'npcink-abilities-toolkit' ); ?>
				<select name="npcink_abilities_toolkit_category">
					<option value=""><?php echo esc_html__( 'All categories', 'npcink-abilities-toolkit' ); ?></option>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo esc_attr( $category ); ?>" <?php selected( $filters['category'], $category ); ?>>
							<?php echo esc_html( $category ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>
				<?php echo esc_html__( 'Per page', 'npcink-abilities-toolkit' ); ?>
				<select name="npcink_abilities_toolkit_per_page">
					<?php foreach ( array( 25, 50, 100 ) as $per_page ) : ?>
						<option value="<?php echo esc_attr( (string) $per_page ); ?>" <?php selected( $filters['per_page'], $per_page ); ?>>
							<?php echo esc_html( (string) $per_page ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<p class="npcink-abilities-toolkit-actions">
				<button type="submit" class="button button-primary"><?php echo esc_html__( 'Apply', 'npcink-abilities-toolkit' ); ?></button>
				<a class="button" href="<?php echo esc_url( $this->add_admin_request_nonce( add_query_arg( 'npcink_abilities_toolkit_tab', 'catalog', $base_url ) ) ); ?>"><?php echo esc_html__( 'Reset', 'npcink-abilities-toolkit' ); ?></a>
			</p>
		</form>
		<?php
	}

	/**
	 * Returns sorted categories for catalog filters.
	 *
	 * @param array<string,array<string,mixed>> $registered Registered abilities.
	 * @return array<int,string>
	 */
	private function get_catalog_categories( array $registered ) {
		$categories = array();

		foreach ( $registered as $definition ) {
			$definition = is_array( $definition ) ? $definition : array();
			$category   = (string) ( $definition['category'] ?? '' );
			if ( '' !== $category ) {
				$categories[ $category ] = true;
			}
		}

		$categories = array_keys( $categories );
		sort( $categories );

		return $categories;
	}

	/**
	 * Filters registered abilities for catalog display.
	 *
	 * @param array<string,array<string,mixed>> $registered Registered abilities.
	 * @param array<string,mixed>               $filters Active filters.
	 * @return array<string,array<string,mixed>>
	 */
	private function filter_ability_catalog( array $registered, array $filters ) {
		$query    = strtolower( (string) $filters['query'] );
		$risk     = (string) $filters['risk'];
		$category = (string) $filters['category'];
		$filtered = array();

		foreach ( $registered as $ability_id => $definition ) {
			$definition          = is_array( $definition ) ? $definition : array();
			$definition_risk     = (string) ( $definition['risk_level'] ?? 'other' );
			$definition_category = (string) ( $definition['category'] ?? '' );

			if ( '' !== $risk && $definition_risk !== $risk ) {
				continue;
			}

			if ( '' !== $category && $definition_category !== $category ) {
				continue;
			}

			if ( '' !== $query ) {
				$haystack = strtolower(
					implode(
						' ',
						array(
							(string) $ability_id,
							$definition_category,
							(string) ( $definition['label'] ?? '' ),
							(string) ( $definition['description'] ?? '' ),
						)
					)
				);

				if ( false === strpos( $haystack, $query ) ) {
					continue;
				}
			}

			$filtered[ $ability_id ] = $definition;
		}

		return $filtered;
	}

	/**
	 * Renders catalog pagination.
	 *
	 * @param int                 $current_page Current page number.
	 * @param int                 $total_pages Total page count.
	 * @param array<string,mixed> $filters Active filters.
	 * @return void
	 */
	private function render_catalog_pagination( $current_page, $total_pages, array $filters ) {
		if ( $total_pages <= 1 ) {
			return;
		}

		$base_url = add_query_arg(
			array(
				'npcink_abilities_toolkit_tab'      => 'catalog',
				'npcink_abilities_toolkit_query'    => (string) $filters['query'],
				'npcink_abilities_toolkit_risk'     => (string) $filters['risk'],
				'npcink_abilities_toolkit_category' => (string) $filters['category'],
				'npcink_abilities_toolkit_per_page' => (int) $filters['per_page'],
				'npcink_abilities_toolkit_paged'    => '%#%',
				'npcink_abilities_toolkit_nonce'    => wp_create_nonce( self::ADMIN_REQUEST_ACTION ),
			),
			menu_page_url( self::MENU_SLUG, false )
		);
		$base_url = str_replace( '%25%23%25', '%#%', $base_url );

		$links = paginate_links(
			array(
				'base'      => $base_url,
				'current'   => $current_page,
				'total'     => $total_pages,
				'prev_text' => __( 'Previous', 'npcink-abilities-toolkit' ),
				'next_text' => __( 'Next', 'npcink-abilities-toolkit' ),
			)
		);

		if ( empty( $links ) ) {
			return;
		}
		?>
		<div class="npcink-abilities-toolkit-pagination">
			<?php echo wp_kses_post( $links ); ?>
		</div>
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
			'read'        => __( 'Read abilities', 'npcink-abilities-toolkit' ),
			'write'       => __( 'Write proposal abilities', 'npcink-abilities-toolkit' ),
			'destructive' => __( 'Destructive abilities', 'npcink-abilities-toolkit' ),
			'other'       => __( 'Other abilities', 'npcink-abilities-toolkit' ),
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
					<th scope="col"><?php echo esc_html__( 'Ability ID', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Category', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Risk', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Ready', 'npcink-abilities-toolkit' ); ?></th>
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
							<span class="<?php echo esc_attr( $has_callback ? 'npcink-abilities-toolkit-ready' : 'npcink-abilities-toolkit-missing' ); ?>">
								<?php echo esc_html( $has_callback ? __( 'available', 'npcink-abilities-toolkit' ) : __( 'missing', 'npcink-abilities-toolkit' ) ); ?>
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
	 * @param bool                $demo_enabled Whether demo ability is enabled.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return void
	 */
	private function render_smoke_tests( $abilities_url, $categories_url, $demo_run_url, $demo_enabled, array $registered ) {
		?>
		<h2><?php echo esc_html__( 'REST endpoints and browser tests', 'npcink-abilities-toolkit' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Run manual REST checks with the current wp-admin session and REST nonce.', 'npcink-abilities-toolkit' ); ?>
		</p>

		<table class="widefat striped" style="max-width: 960px;">
			<tbody>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Browser Auth', 'npcink-abilities-toolkit' ); ?></th>
					<td><?php echo esc_html__( 'Buttons use the current wp-admin session with an X-WP-Nonce header. External clients should use WordPress REST authentication such as application passwords.', 'npcink-abilities-toolkit' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p class="npcink-abilities-toolkit-actions">
			<button type="button" class="button button-primary" data-npcink-abilities-toolkit-fetch="<?php echo esc_url( $abilities_url ); ?>">
				<?php echo esc_html__( 'Fetch Abilities', 'npcink-abilities-toolkit' ); ?>
			</button>
			<button type="button" class="button" data-npcink-abilities-toolkit-fetch="<?php echo esc_url( $categories_url ); ?>">
				<?php echo esc_html__( 'Fetch Categories', 'npcink-abilities-toolkit' ); ?>
			</button>
			<button type="button" class="button" data-npcink-abilities-toolkit-fetch="<?php echo esc_url( $demo_run_url ); ?>" <?php disabled( ! $demo_enabled ); ?>>
				<?php echo esc_html__( 'Run Demo Ability', 'npcink-abilities-toolkit' ); ?>
			</button>
		</p>

		<textarea id="npcink-abilities-toolkit-admin-output" class="npcink-abilities-toolkit-output" readonly rows="14"></textarea>

		<details class="npcink-abilities-toolkit-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Demo ability control', 'npcink-abilities-toolkit' ); ?></strong>
				<span class="npcink-abilities-toolkit-disclosure__meta"><?php echo esc_html__( 'Enable or disable the read-only smoke ability.', 'npcink-abilities-toolkit' ); ?></span>
			</summary>
			<div class="npcink-abilities-toolkit-disclosure__body">
				<form method="post" action="options.php">
					<?php settings_fields( 'npcink_abilities_toolkit_test' ); ?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_DEMO_ENABLED ); ?>" value="1" <?php checked( $demo_enabled ); ?> />
						<?php echo esc_html__( 'Enable demo read-only ability: npcink-abilities-toolkit/site-summary', 'npcink-abilities-toolkit' ); ?>
					</label>
					<?php submit_button( __( 'Save', 'npcink-abilities-toolkit' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</details>

		<?php $this->render_advanced_checks( $abilities_url, $categories_url, $registered ); ?>
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
		<h2><?php echo esc_html__( 'Advanced Checks', 'npcink-abilities-toolkit' ); ?></h2>

		<details class="npcink-abilities-toolkit-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Endpoint values', 'npcink-abilities-toolkit' ); ?></strong>
				<span class="npcink-abilities-toolkit-disclosure__meta"><?php echo esc_html__( 'Raw REST values for client setup and support checks.', 'npcink-abilities-toolkit' ); ?></span>
			</summary>
			<div class="npcink-abilities-toolkit-disclosure__body">
				<table class="widefat striped">
					<tbody>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Abilities Endpoint', 'npcink-abilities-toolkit' ); ?></th>
							<td><code><?php echo esc_html( $abilities_url ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Categories Endpoint', 'npcink-abilities-toolkit' ); ?></th>
							<td><code><?php echo esc_html( $categories_url ); ?></code></td>
						</tr>
					</tbody>
				</table>
			</div>
		</details>

		<details class="npcink-abilities-toolkit-disclosure">
			<summary>
				<strong><?php echo esc_html__( 'Raw ability ids', 'npcink-abilities-toolkit' ); ?></strong>
				<span class="npcink-abilities-toolkit-disclosure__meta"><?php echo esc_html__( 'Compatibility dump for catalog audits.', 'npcink-abilities-toolkit' ); ?></span>
			</summary>
			<div class="npcink-abilities-toolkit-disclosure__body">
				<p class="npcink-abilities-toolkit-actions">
					<button type="button" class="button" data-npcink-abilities-toolkit-copy="npcink-abilities-toolkit-raw-ids">
						<?php echo esc_html__( 'Copy IDs', 'npcink-abilities-toolkit' ); ?>
					</button>
				</p>
				<textarea id="npcink-abilities-toolkit-raw-ids" class="npcink-abilities-toolkit-raw" readonly rows="8"><?php echo esc_textarea( wp_json_encode( array_keys( $registered ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>
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
			'npcink-abilities-toolkit/site-summary',
			array(
				'label'            => __( 'Abilities API Site Summary', 'npcink-abilities-toolkit' ),
				'description'      => __( 'Returns a small site summary for testing WordPress Abilities API authentication and execution.', 'npcink-abilities-toolkit' ),
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
						'plugin_version' => defined( 'NPCINK_ABILITIES_TOOLKIT_VERSION' ) ? NPCINK_ABILITIES_TOOLKIT_VERSION : '',
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
