<?php
/**
 * Workflow scenario card renderer for the admin Developer Tools tab.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the read-only workflow scenario overview from the static recipe definitions.
 *
 * The block only lists scenarios for review. Hosts own execution, approvals,
 * and final writes; this surface never runs a workflow step. Recipe titles and
 * tasks are contract data in English, so the card view localizes them at
 * render time instead of changing the discovery payloads.
 */
final class Scenario_Cards {
	/**
	 * Renders the scenario overview grid, or its explicit empty state.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! function_exists( 'npcink_abilities_toolkit_get_workflow_definitions' ) ) {
			return;
		}

		$manifest = npcink_abilities_toolkit_get_workflow_definitions();
		$cases    = isset( $manifest['cases'] ) && is_array( $manifest['cases'] ) ? $manifest['cases'] : array();
		if ( empty( $cases ) ) {
			?>
			<section id="npcink-abilities-toolkit-scenarios" class="npcink-abilities-toolkit-scenarios" aria-labelledby="npcink-abilities-toolkit-scenarios-title">
				<h2 id="npcink-abilities-toolkit-scenarios-title"><?php echo esc_html__( 'Workflow scenarios', 'npcink-abilities-toolkit' ); ?></h2>
				<p class="description"><?php echo esc_html__( 'No workflow scenarios are published on this site yet. Scenarios appear here when the bundled recipe catalog provides them.', 'npcink-abilities-toolkit' ); ?></p>
			</section>
			<?php
			return;
		}
		?>
		<section id="npcink-abilities-toolkit-scenarios" class="npcink-abilities-toolkit-scenarios" aria-labelledby="npcink-abilities-toolkit-scenarios-title">
			<h2 id="npcink-abilities-toolkit-scenarios-title"><?php echo esc_html__( 'Workflow scenarios', 'npcink-abilities-toolkit' ); ?></h2>
			<p class="description">
				<?php echo esc_html__( 'Recommended ability chains a host product can run for common site tasks. This page lists them for review only; hosts own execution, approvals, and final writes.', 'npcink-abilities-toolkit' ); ?>
			</p>
			<div class="npcink-abilities-toolkit-scenarios__grid">
				<?php foreach ( $cases as $case ) : ?>
					<?php $case = is_array( $case ) ? $case : array(); ?>
					<div class="npcink-abilities-toolkit-scenarios__item">
						<h3><?php echo esc_html__( (string) ( $case['title'] ?? '' ), 'npcink-abilities-toolkit' ); ?></h3>
						<ul>
							<?php foreach ( array_slice( (array) ( $case['natural_tasks'] ?? array() ), 0, 3 ) as $task ) : ?>
								<li><?php echo esc_html__( (string) $task, 'npcink-abilities-toolkit' ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="description">
							<?php echo esc_html__( 'Entry ability:', 'npcink-abilities-toolkit' ); ?>
							<code><?php echo esc_html( (string) ( $case['entrypoint_ability_id'] ?? '' ) ); ?></code>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
