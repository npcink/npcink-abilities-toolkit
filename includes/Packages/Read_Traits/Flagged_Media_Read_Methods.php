<?php
/**
 * Bounded flagged-media review evidence.
 * @package NpcinkAbilitiesToolkit
 */
namespace Npcink_Abilities_Toolkit\Packages;
if ( ! defined( 'ABSPATH' ) ) { exit; }
trait Flagged_Media_Read_Methods {
	/**
	 * Descriptive evidence only. These bounded scans are explicitly not exhaustive.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function flagged_media_reference_summary( int $attachment_id ) {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return new \WP_Error( 'npcink_abilities_toolkit_permission_denied', __( 'You do not have permission to review flagged media references.', 'npcink-abilities-toolkit' ), array( 'status' => 403 ) );
		}
		$usage = $this->build_media_usage_context( $attachment_id );
		$url = (string) wp_get_attachment_url( $attachment_id );
		$settings = array();
		if ( '' !== $url ) {
			foreach ( $this->media_settings_reference_repair_candidates( array(), 50 ) as $candidate ) {
				if ( false !== strpos( $this->media_settings_reference_value_text( $candidate['value'] ?? null ), $url ) ) {
					$settings[] = array( 'target_type' => $candidate['target_type'], 'target_name' => $candidate['target_name'] );
				}
			}
		}
		return array( 'posts' => (array) ( $usage['content_reference_posts'] ?? array() ), 'featured_images' => (array) ( $usage['featured_image_posts'] ?? array() ), 'settings' => $settings, 'scan_complete' => false, 'scan_limits' => array( 'content_candidates' => 50, 'content_matches' => 10, 'featured_matches' => 5, 'settings_candidates' => 50 ), 'operator_review_required' => true );
	}
}
