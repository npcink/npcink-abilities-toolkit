<?php
/**
 * Overview status, attention notes, and next actions for the admin page.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

use Npcink_Abilities_Toolkit\Support\Docs_Links;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps overview rendering in the owning page context.
 */
trait Overview_Sections {
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
		$write_count       = $this->count_abilities_by_risk( $registered, array( 'write', 'destructive' ) );
		$package_ready     = $ability_api_ready && $rest_ready && ! empty( $registered ) && 0 === $callback_issues;
		$host_detected     = ! empty( $status['has_host_menu'] );
		$status_label      = $package_ready ? __( 'working', 'npcink-abilities-toolkit' ) : __( 'needs attention', 'npcink-abilities-toolkit' );
		$status_state      = $package_ready ? 'ok' : 'warning';
		$status_detail     = $package_ready
			? __( "AI clients can discover this site's abilities.", 'npcink-abilities-toolkit' )
			: __( 'Review the attention notes below.', 'npcink-abilities-toolkit' );
		$write_value       = $write_count > 0 ? __( 'host approval', 'npcink-abilities-toolkit' ) : __( 'read-only', 'npcink-abilities-toolkit' );
		$write_detail      = $write_count > 0
			? sprintf(
				/* translators: %d: write or destructive ability count. */
				__( '%d abilities can change content only through host governance.', 'npcink-abilities-toolkit' ),
				$write_count
			)
			: __( 'No write or destructive abilities are currently registered.', 'npcink-abilities-toolkit' );
		$host_value        = $host_detected ? __( 'detected', 'npcink-abilities-toolkit' ) : __( 'standalone', 'npcink-abilities-toolkit' );
		$host_detail       = $host_detected
			? __( 'A Npcink AI host menu is available.', 'npcink-abilities-toolkit' )
			: __( 'This package provides abilities; use a host product to run AI workflows.', 'npcink-abilities-toolkit' );
		?>
		<section class="npcink-abilities-toolkit-overview-panel" aria-labelledby="npcink-abilities-toolkit-status-title">
			<h2 id="npcink-abilities-toolkit-status-title"><?php echo esc_html__( 'Site ability status', 'npcink-abilities-toolkit' ); ?></h2>
			<div class="npcink-abilities-toolkit-metrics" role="list">
				<?php
				$this->render_status_metric(
					__( 'Status', 'npcink-abilities-toolkit' ),
					$status_label,
					$status_state,
					$status_detail,
					true
				);
				$this->render_status_metric(
					__( 'Available abilities', 'npcink-abilities-toolkit' ),
					(string) count( $registered ),
					empty( $registered ) ? 'warning' : 'ok',
					__( 'Abilities exposed by this WordPress site.', 'npcink-abilities-toolkit' )
				);
				$this->render_status_metric(
					__( 'Write safeguards', 'npcink-abilities-toolkit' ),
					$write_value,
					'ok',
					$write_detail
				);
				$this->render_status_metric(
					__( 'Host', 'npcink-abilities-toolkit' ),
					$host_value,
					$host_detected ? 'ok' : 'inactive',
					$host_detail
				);
				?>
			</div>
			<p class="npcink-abilities-toolkit-overview-panel__meta description">
				<?php
				printf(
					/* translators: %s: WordPress version. */
					esc_html__( 'WordPress %s', 'npcink-abilities-toolkit' ),
					esc_html( (string) $status['wp_version'] )
				);
				?>
			</p>
		</section>

		<?php $this->render_status_attention( $status, $registered ); ?>

		<?php $this->render_capability_summary( $registered ); ?>

		<?php $this->render_package_status(); ?>

		<section class="npcink-abilities-toolkit-next" aria-labelledby="npcink-abilities-toolkit-next-title">
			<h2 id="npcink-abilities-toolkit-next-title"><?php echo esc_html__( 'Next actions', 'npcink-abilities-toolkit' ); ?></h2>
			<div class="npcink-abilities-toolkit-actions">
				<a class="button button-primary" href="<?php echo esc_url( $this->get_technical_url( 'catalog', 'npcink-abilities-toolkit-ability-catalog' ) ); ?>"><?php echo esc_html__( 'View Abilities', 'npcink-abilities-toolkit' ); ?></a>
				<a class="button" href="<?php echo esc_url( $this->get_technical_url( 'checks', 'npcink-abilities-toolkit-readonly-checks' ) ); ?>"><?php echo esc_html__( 'Open Checks', 'npcink-abilities-toolkit' ); ?></a>
				<?php if ( function_exists( 'npcink_abilities_toolkit_get_workflow_definitions' ) ) : ?>
					<?php $scenario_count = count( (array) ( npcink_abilities_toolkit_get_workflow_definitions()['cases'] ?? array() ) ); ?>
					<?php if ( $scenario_count > 0 ) : ?>
						<a class="button" href="<?php echo esc_url( $this->get_technical_url( 'scenarios', 'npcink-abilities-toolkit-scenarios' ) ); ?>">
							<?php
							printf(
								/* translators: %d: published workflow scenario count. */
								esc_html( _n( 'View %d Workflow Scenario', 'View %d Workflow Scenarios', $scenario_count, 'npcink-abilities-toolkit' ) ),
								absint( $scenario_count )
							);
							?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
				<a class="button" href="<?php echo esc_url( $this->get_technical_url( 'connection', 'npcink-abilities-toolkit-connection-values' ) ); ?>"><?php echo esc_html__( 'View Connection Info', 'npcink-abilities-toolkit' ); ?></a>
			</div>
		</section>
		<?php
	}

	/**
	 * Renders one Quiet overview metric.
	 *
	 * @param string $label Metric label.
	 * @param string $value Metric value.
	 * @param string $state Metric state (ok|warning|error|inactive).
	 * @param string $detail Visible supporting detail.
	 * @param bool   $as_badge When true, value renders as a status badge.
	 * @return void
	 */
	private function render_status_metric( $label, $value, $state, $detail, $as_badge = false ) {
		?>
		<div class="npcink-abilities-toolkit-metric is-<?php echo esc_attr( $state ); ?>" role="listitem">
			<span class="npcink-abilities-toolkit-metric__label"><?php echo esc_html( $label ); ?></span>
			<span class="npcink-abilities-toolkit-metric__value"<?php echo '' !== $detail ? ' title="' . esc_attr( $detail ) . '"' : ''; ?>>
				<?php if ( $as_badge ) : ?>
					<span class="npcink-abilities-toolkit-badge is-<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $value ); ?></span>
				<?php else : ?>
					<?php echo esc_html( $value ); ?>
				<?php endif; ?>
			</span>
			<span class="npcink-abilities-toolkit-status__detail screen-reader-text"><?php echo esc_html( $detail ); ?></span>
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
			<p>
				<a href="<?php echo esc_url( Docs_Links::troubleshooting() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Troubleshooting docs', 'npcink-abilities-toolkit' ); ?></a>
			</p>
		</div>
		<?php
	}
}
