<?php
/**
 * Admin page for Abilities API endpoints.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

use Npcink_Abilities_Toolkit\Registry\Ability_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a compact operator-facing Abilities surface.
 */
final class Test_Page {
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
	 * Admin hook suffixes that should load this page's assets.
	 *
	 * @var array<int,string>
	 */
	private $hook_suffixes = array();

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
		add_action( 'admin_menu', array( $this, 'register_menu' ), 40 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_npcink_abilities_toolkit_readonly_check', array( $this, 'run_readonly_check' ) );
	}

	/**
	 * Enqueues the admin page assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( (string) $hook_suffix, $this->hook_suffixes, true ) ) {
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
			$this->hook_suffixes[] = add_submenu_page(
				self::PARENT_MENU_SLUG,
				__( 'Abilities Toolkit Diagnostics', 'npcink-abilities-toolkit' ),
				__( 'Ability Diagnostics', 'npcink-abilities-toolkit' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render' ),
				40
			);
			return;
		}

		$this->hook_suffixes[] = add_management_page(
			__( 'Abilities Toolkit Diagnostics', 'npcink-abilities-toolkit' ),
			__( 'Abilities Toolkit Diagnostics', 'npcink-abilities-toolkit' ),
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
		$contract_url   = rest_url( 'npcink-abilities-toolkit/v1/contract' );
		$status         = $this->get_environment_status();
		$registered     = $this->abilities->all();
		$active_tab     = $this->get_active_tab();
		?>
		<div class="wrap npcink-abilities-toolkit-admin" data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" data-admin-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-admin-nonce="<?php echo esc_attr( wp_create_nonce( self::ADMIN_REQUEST_ACTION ) ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'npcink-abilities-toolkit' ); ?>" data-requesting-label="<?php echo esc_attr__( 'Requesting', 'npcink-abilities-toolkit' ); ?>" data-running-label="<?php echo esc_attr__( 'Running', 'npcink-abilities-toolkit' ); ?>">
			<h1><?php echo esc_html__( 'Abilities Toolkit Diagnostics', 'npcink-abilities-toolkit' ); ?></h1>
			<p><?php echo esc_html__( 'Review package health, registered abilities, REST endpoints, and host handoff values.', 'npcink-abilities-toolkit' ); ?></p>

			<?php $this->render_tab_nav( $active_tab ); ?>

			<div class="npcink-abilities-toolkit-tab-panel">
				<?php
				if ( 'catalog' === $active_tab ) {
					$this->render_ability_catalog( $registered );
				} elseif ( 'connections' === $active_tab ) {
					$this->render_smoke_tests( $abilities_url, $categories_url, $contract_url, $registered );
				} else {
					$this->render_status_summary( $status, $registered );
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
			'overview'    => __( 'Overview', 'npcink-abilities-toolkit' ),
			'catalog'     => __( 'Catalog', 'npcink-abilities-toolkit' ),
			'connections' => __( 'Connections', 'npcink-abilities-toolkit' ),
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
				<?php $tab_url = $this->get_tab_url( $tab ); ?>
				<a class="<?php echo esc_attr( 'nav-tab ' . ( $active_tab === $tab ? 'nav-tab-active' : '' ) ); ?>" href="<?php echo esc_url( $tab_url ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Returns an admin URL for one page tab.
	 *
	 * @param string $tab Tab key.
	 * @return string
	 */
	private function get_tab_url( $tab, $fragment = '' ) {
		$base_url = menu_page_url( self::MENU_SLUG, false );

		if ( 'overview' === $tab ) {
			$url = $base_url;
		} else {
			$url = add_query_arg( 'npcink_abilities_toolkit_tab', $tab, $base_url );
		}

		if ( '' !== $fragment ) {
			$url .= '#' . rawurlencode( $fragment );
		}

		return $url;
	}

	/**
	 * Returns a sanitized admin query arg.
	 *
	 * @param string $key Query arg key.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_admin_query_arg( $key, $default = '' ) {
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
	 * @return void
	 */
	private function render_status_summary( array $status, array $registered ) {
		$ability_api_ready = $status['has_ability_registration'] && $status['has_category_registration'];
		$rest_ready        = $status['has_rest_abilities_route'] && $status['has_rest_categories_route'];
		$callback_issues   = $this->get_callback_issue_count( $registered );
		$package_ready     = $ability_api_ready && $rest_ready && ! empty( $registered ) && 0 === $callback_issues;
		?>
		<h2><?php echo esc_html__( 'Health summary', 'npcink-abilities-toolkit' ); ?></h2>
		<div class="npcink-abilities-toolkit-summary" role="list">
			<?php $this->render_status_tile( __( 'Package', 'npcink-abilities-toolkit' ), $package_ready ? __( 'ready', 'npcink-abilities-toolkit' ) : __( 'needs review', 'npcink-abilities-toolkit' ), $package_ready ? 'ok' : 'warning', $package_ready ? __( 'Discovery and callbacks are available.', 'npcink-abilities-toolkit' ) : __( 'Review the attention notes below.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'REST discovery', 'npcink-abilities-toolkit' ), $rest_ready ? __( 'available', 'npcink-abilities-toolkit' ) : __( 'missing', 'npcink-abilities-toolkit' ), $rest_ready ? 'ok' : 'error', __( 'Client discovery endpoints.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'Registered abilities', 'npcink-abilities-toolkit' ), (string) count( $registered ), empty( $registered ) ? 'warning' : 'ok', __( 'Catalog rows available for inspection.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'Callback issues', 'npcink-abilities-toolkit' ), (string) $callback_issues, $callback_issues > 0 ? 'error' : 'ok', $callback_issues > 0 ? __( 'Execution callbacks missing from registered rows.', 'npcink-abilities-toolkit' ) : __( 'No missing callbacks detected.', 'npcink-abilities-toolkit' ) ); ?>
			<?php $this->render_status_tile( __( 'WordPress', 'npcink-abilities-toolkit' ), (string) $status['wp_version'], 'ok', __( 'Runtime version detected.', 'npcink-abilities-toolkit' ) ); ?>
		</div>

		<?php $this->render_status_attention( $status, $registered ); ?>

		<section class="npcink-abilities-toolkit-next" aria-labelledby="npcink-abilities-toolkit-next-title">
			<h2 id="npcink-abilities-toolkit-next-title"><?php echo esc_html__( 'Next actions', 'npcink-abilities-toolkit' ); ?></h2>
			<div class="npcink-abilities-toolkit-next__grid">
				<div class="npcink-abilities-toolkit-next__item">
					<h3><?php echo esc_html__( 'Inspect abilities', 'npcink-abilities-toolkit' ); ?></h3>
					<p><?php echo esc_html__( 'Review labels, IDs, risk levels, schemas, and callback readiness before connecting a host.', 'npcink-abilities-toolkit' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( $this->get_tab_url( 'catalog' ) ); ?>"><?php echo esc_html__( 'Open Catalog', 'npcink-abilities-toolkit' ); ?></a>
				</div>
				<div class="npcink-abilities-toolkit-next__item">
					<h3><?php echo esc_html__( 'Copy host endpoints', 'npcink-abilities-toolkit' ); ?></h3>
					<p><?php echo esc_html__( 'Use stable REST endpoint values when configuring a host client.', 'npcink-abilities-toolkit' ); ?></p>
					<a class="button" href="<?php echo esc_url( $this->get_tab_url( 'connections', 'npcink-abilities-toolkit-connection-values' ) ); ?>"><?php echo esc_html__( 'View Endpoints', 'npcink-abilities-toolkit' ); ?></a>
				</div>
				<div class="npcink-abilities-toolkit-next__item">
					<h3><?php echo esc_html__( 'Run read-only checks', 'npcink-abilities-toolkit' ); ?></h3>
					<p><?php echo esc_html__( 'Verify bounded site info and diagnostics with the current wp-admin session.', 'npcink-abilities-toolkit' ); ?></p>
					<a class="button" href="<?php echo esc_url( $this->get_tab_url( 'connections', 'npcink-abilities-toolkit-readonly-checks' ) ); ?>"><?php echo esc_html__( 'Open Checks', 'npcink-abilities-toolkit' ); ?></a>
				</div>
				<div class="npcink-abilities-toolkit-next__item">
					<h3><?php echo esc_html__( 'Export ability IDs', 'npcink-abilities-toolkit' ); ?></h3>
					<p><?php echo esc_html__( 'Copy the registered ability ID list for host audits and catalog comparisons.', 'npcink-abilities-toolkit' ); ?></p>
					<a class="button" href="<?php echo esc_url( $this->get_tab_url( 'connections', 'npcink-abilities-toolkit-catalog-export' ) ); ?>"><?php echo esc_html__( 'Open Export', 'npcink-abilities-toolkit' ); ?></a>
				</div>
			</div>
			<p class="description">
				<?php echo esc_html__( 'Final write approval stays with the host runtime. This package exposes reusable WordPress abilities and discovery metadata.', 'npcink-abilities-toolkit' ); ?>
			</p>
		</section>
		<?php
	}

	/**
	 * Counts registered abilities without callable execute callbacks.
	 *
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return int
	 */
	private function get_callback_issue_count( array $registered ) {
		$issues = 0;

		foreach ( $registered as $definition ) {
			$definition = is_array( $definition ) ? $definition : array();
			if ( ! is_callable( $definition['execute_callback'] ?? null ) ) {
				++$issues;
			}
		}

		return $issues;
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

		if ( isset( $status['home_url'], $status['site_url'] ) && $status['home_url'] !== $status['site_url'] ) {
			$messages[] = __( 'Home URL and Site URL differ; verify local site routing before testing endpoint values.', 'npcink-abilities-toolkit' );
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

			<label>
				<?php echo esc_html__( 'Search', 'npcink-abilities-toolkit' ); ?>
				<input type="search" name="npcink_abilities_toolkit_query" value="<?php echo esc_attr( (string) $filters['query'] ); ?>" placeholder="<?php echo esc_attr__( 'ID, label, description, or category', 'npcink-abilities-toolkit' ); ?>" />
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
				<a class="button" href="<?php echo esc_url( add_query_arg( 'npcink_abilities_toolkit_tab', 'catalog', $base_url ) ); ?>"><?php echo esc_html__( 'Reset', 'npcink-abilities-toolkit' ); ?></a>
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
				'show_all'  => $total_pages <= 9,
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
					<th scope="col"><?php echo esc_html__( 'Ability', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Category', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Risk', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Schemas', 'npcink-abilities-toolkit' ); ?></th>
					<th scope="col"><?php echo esc_html__( 'Callback', 'npcink-abilities-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $abilities as $ability_id => $definition ) : ?>
					<?php
					$has_callback = is_callable( $definition['execute_callback'] ?? null );
					$has_input    = is_array( $definition['input_schema'] ?? null );
					$has_output   = is_array( $definition['output_schema'] ?? null );
					$label        = (string) ( $definition['label'] ?? '' );
					$description  = (string) ( $definition['description'] ?? '' );
					?>
					<tr>
						<td class="npcink-abilities-toolkit-ability-cell">
							<code><?php echo esc_html( (string) $ability_id ); ?></code>
							<?php if ( '' !== $label ) : ?>
								<strong><?php echo esc_html( $label ); ?></strong>
							<?php endif; ?>
							<?php if ( '' !== $description ) : ?>
								<span><?php echo esc_html( $description ); ?></span>
							<?php endif; ?>
						</td>
						<td><code><?php echo esc_html( (string) ( $definition['category'] ?? '-' ) ); ?></code></td>
						<td><code><?php echo esc_html( (string) ( $definition['risk_level'] ?? '-' ) ); ?></code></td>
						<td>
							<span class="<?php echo esc_attr( $has_input ? 'npcink-abilities-toolkit-ready' : 'npcink-abilities-toolkit-missing' ); ?>">
								<?php echo esc_html( sprintf( '%1$s: %2$s', __( 'Input', 'npcink-abilities-toolkit' ), $has_input ? __( 'yes', 'npcink-abilities-toolkit' ) : __( 'no', 'npcink-abilities-toolkit' ) ) ); ?>
							</span>
							<br />
							<span class="<?php echo esc_attr( $has_output ? 'npcink-abilities-toolkit-ready' : 'npcink-abilities-toolkit-missing' ); ?>">
								<?php echo esc_html( sprintf( '%1$s: %2$s', __( 'Output', 'npcink-abilities-toolkit' ), $has_output ? __( 'yes', 'npcink-abilities-toolkit' ) : __( 'no', 'npcink-abilities-toolkit' ) ) ); ?>
							</span>
						</td>
						<td>
							<span class="<?php echo esc_attr( $has_callback ? 'npcink-abilities-toolkit-ready' : 'npcink-abilities-toolkit-missing' ); ?>">
								<?php echo esc_html( $has_callback ? __( 'available', 'npcink-abilities-toolkit' ) : __( 'missing', 'npcink-abilities-toolkit' ) ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Renders REST discovery check controls.
	 *
	 * @param string $abilities_url Abilities endpoint URL.
	 * @param string $categories_url Categories endpoint URL.
	 * @param string $contract_url Contract endpoint URL.
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return void
	 */
	private function render_smoke_tests( $abilities_url, $categories_url, $contract_url, array $registered ) {
		$site_info_available = isset( $registered['npcink-abilities-toolkit/site-info'] );
		$diagnostics_available = isset( $registered['npcink-abilities-toolkit/wp-diagnostics-summary'] );
		?>
		<h2><?php echo esc_html__( 'Connections', 'npcink-abilities-toolkit' ); ?></h2>
		<p class="description">
			<?php echo esc_html__( 'Copy host connection values, run bounded read-only checks, and inspect REST discovery responses.', 'npcink-abilities-toolkit' ); ?>
		</p>

		<section id="npcink-abilities-toolkit-connection-values" class="npcink-abilities-toolkit-section">
			<h3><?php echo esc_html__( 'Host connection values', 'npcink-abilities-toolkit' ); ?></h3>
			<table class="widefat striped npcink-abilities-toolkit-connection-table">
				<tbody>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Abilities Endpoint', 'npcink-abilities-toolkit' ); ?></th>
						<td><code id="npcink-abilities-toolkit-abilities-endpoint"><?php echo esc_html( $abilities_url ); ?></code></td>
						<td>
							<button type="button" class="button" data-npcink-abilities-toolkit-copy="npcink-abilities-toolkit-abilities-endpoint">
								<?php echo esc_html__( 'Copy Abilities Endpoint', 'npcink-abilities-toolkit' ); ?>
							</button>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Categories Endpoint', 'npcink-abilities-toolkit' ); ?></th>
						<td><code id="npcink-abilities-toolkit-categories-endpoint"><?php echo esc_html( $categories_url ); ?></code></td>
						<td>
							<button type="button" class="button" data-npcink-abilities-toolkit-copy="npcink-abilities-toolkit-categories-endpoint">
								<?php echo esc_html__( 'Copy Categories Endpoint', 'npcink-abilities-toolkit' ); ?>
							</button>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Contract Endpoint', 'npcink-abilities-toolkit' ); ?></th>
						<td><code id="npcink-abilities-toolkit-contract-endpoint"><?php echo esc_html( $contract_url ); ?></code></td>
						<td>
							<button type="button" class="button" data-npcink-abilities-toolkit-copy="npcink-abilities-toolkit-contract-endpoint">
								<?php echo esc_html__( 'Copy Contract Endpoint', 'npcink-abilities-toolkit' ); ?>
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</section>

		<section id="npcink-abilities-toolkit-readonly-checks" class="npcink-abilities-toolkit-section">
			<h3><?php echo esc_html__( 'Read-only ability checks', 'npcink-abilities-toolkit' ); ?></h3>
			<p class="description">
				<?php echo esc_html__( 'These checks use bounded admin input. They do not write content, call models, or contact external services.', 'npcink-abilities-toolkit' ); ?>
			</p>
			<p class="npcink-abilities-toolkit-actions">
				<button type="button" class="button button-primary" data-npcink-abilities-toolkit-readonly-check="site-info" <?php disabled( ! $site_info_available ); ?>>
					<?php echo esc_html__( 'Run Site Info', 'npcink-abilities-toolkit' ); ?>
				</button>
				<button type="button" class="button" data-npcink-abilities-toolkit-readonly-check="diagnostics-summary" <?php disabled( ! $diagnostics_available ); ?>>
					<?php echo esc_html__( 'Run Diagnostics Summary', 'npcink-abilities-toolkit' ); ?>
				</button>
			</p>
		</section>

		<section id="npcink-abilities-toolkit-discovery-checks" class="npcink-abilities-toolkit-section">
			<h3><?php echo esc_html__( 'Discovery fetches', 'npcink-abilities-toolkit' ); ?></h3>
			<p class="description">
				<?php echo esc_html__( 'These buttons use the current wp-admin session with an X-WP-Nonce header. External clients should use WordPress REST authentication.', 'npcink-abilities-toolkit' ); ?>
			</p>
			<p class="npcink-abilities-toolkit-actions">
				<button type="button" class="button button-primary" data-npcink-abilities-toolkit-fetch="<?php echo esc_url( $abilities_url ); ?>">
					<?php echo esc_html__( 'Fetch Abilities', 'npcink-abilities-toolkit' ); ?>
				</button>
				<button type="button" class="button" data-npcink-abilities-toolkit-fetch="<?php echo esc_url( $categories_url ); ?>">
					<?php echo esc_html__( 'Fetch Categories', 'npcink-abilities-toolkit' ); ?>
				</button>
			</p>

			<textarea id="npcink-abilities-toolkit-admin-output" class="npcink-abilities-toolkit-output" readonly rows="14"></textarea>
		</section>

		<?php $this->render_advanced_checks( $registered ); ?>
		<?php
	}

	/**
	 * Renders endpoint details and copyable ID lists.
	 *
	 * @param array<string,mixed> $registered Registered abilities.
	 * @return void
	 */
	private function render_advanced_checks( array $registered ) {
		?>
		<section id="npcink-abilities-toolkit-catalog-export" class="npcink-abilities-toolkit-section">
			<h3><?php echo esc_html__( 'Catalog export', 'npcink-abilities-toolkit' ); ?></h3>

			<details class="npcink-abilities-toolkit-disclosure">
				<summary>
					<strong><?php echo esc_html__( 'Ability ID export', 'npcink-abilities-toolkit' ); ?></strong>
					<span class="npcink-abilities-toolkit-disclosure__meta"><?php echo esc_html__( 'Copyable ID list for host and catalog audits.', 'npcink-abilities-toolkit' ); ?></span>
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
		</section>
		<?php
	}

	/**
	 * Runs one allowlisted read-only ability check from the admin page.
	 *
	 * @return void
	 */
	public function run_readonly_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to run this check.', 'npcink-abilities-toolkit' ) ), 403 );
		}

		check_ajax_referer( self::ADMIN_REQUEST_ACTION, 'nonce' );

		$check = isset( $_POST['check'] ) ? sanitize_key( wp_unslash( $_POST['check'] ) ) : '';
		$allowed = array(
			'site-info'           => 'npcink-abilities-toolkit/site-info',
			'diagnostics-summary' => 'npcink-abilities-toolkit/wp-diagnostics-summary',
		);
		if ( ! isset( $allowed[ $check ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown read-only check.', 'npcink-abilities-toolkit' ) ), 400 );
		}

		$ability_id = $allowed[ $check ];
		$request    = new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/' . $ability_id . '/run' );
		$request->set_query_params( array( 'input' => $this->get_readonly_check_input( $check ) ) );
		$response = rest_do_request( $request );

		if ( function_exists( 'is_wp_error' ) && is_wp_error( $response ) ) {
			wp_send_json(
				array(
					'ability_id' => $ability_id,
					'status'     => 500,
					'body'       => array(
						'code'    => $response->get_error_code(),
						'message' => $response->get_error_message(),
					),
				),
				500
			);
		}

		$status = is_object( $response ) && method_exists( $response, 'get_status' ) ? (int) $response->get_status() : 500;
		$body   = is_object( $response ) && method_exists( $response, 'get_data' ) ? $response->get_data() : null;

		wp_send_json(
			array(
				'ability_id' => $ability_id,
				'status'     => $status,
				'body'       => $body,
			),
			$status >= 400 ? $status : 200
		);
	}

	/**
	 * Returns bounded input for one admin read-only check.
	 *
	 * @param string $check Check key.
	 * @return array<string,mixed>
	 */
	private function get_readonly_check_input( $check ) {
		if ( 'diagnostics-summary' !== $check ) {
			return array();
		}

		return array(
			'include_plugins'      => false,
			'include_theme'        => true,
			'include_cron'         => false,
			'include_updates'      => false,
			'include_current_user' => false,
			'include_object_cache' => true,
			'include_rewrite'      => true,
			'include_https'        => true,
		);
	}

	/**
	 * Returns environment status for the admin page.
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
			'home_url'                   => function_exists( 'home_url' ) ? (string) home_url() : '',
			'site_url'                   => function_exists( 'site_url' ) ? (string) site_url() : '',
			'has_ability_registration'   => function_exists( 'wp_register_ability' ),
			'has_category_registration'  => function_exists( 'wp_register_ability_category' ),
			'has_rest_abilities_route'   => isset( $routes['/wp-abilities/v1/abilities'] ),
			'has_rest_categories_route'  => isset( $routes['/wp-abilities/v1/categories'] ),
		);
	}

}
