<?php
/**
 * Read-only mode form, separate from option and permission handling.
 *
 * @package NpcinkAbilitiesToolkit
 */

namespace Npcink_Abilities_Toolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the operator's narrowing-only package choice.
 */
final class Read_Only_Mode_View {
	/**
	 * @param bool $enabled Whether read-only mode is enabled.
	 * @return void
	 */
	public static function render( $enabled ) {
		?>
		<form class="npcink-abilities-toolkit-readonly-mode" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( Read_Only_Mode::TOGGLE_ACTION ); ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( menu_page_url( Test_Page::MENU_SLUG, false ) ); ?>" />
			<?php echo wp_nonce_field( Read_Only_Mode::TOGGLE_ACTION, '_wpnonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="npcink-abilities-toolkit-setting-row">
				<div class="npcink-abilities-toolkit-setting-row__copy">
					<?php $readonly_blurb = __( 'Stop registering write and destructive abilities on this site. Read abilities, comment helpers, and this status page keep working. Host products cannot widen past this choice, and it takes effect on the next request.', 'npcink-abilities-toolkit' ); ?>
					<label class="npcink-abilities-toolkit-setting-row__title" for="npcink-abilities-toolkit-readonly-mode" title="<?php echo esc_attr( $readonly_blurb ); ?>"><?php echo esc_html__( 'Read-only mode', 'npcink-abilities-toolkit' ); ?></label>
					<span class="screen-reader-text"><?php echo esc_html( $readonly_blurb ); ?></span>
					<?php if ( $enabled ) : ?>
						<p class="description npcink-abilities-toolkit-readonly-mode__note"><?php echo esc_html__( 'Read-only mode is on: the write and destructive packages above show Off because of this choice.', 'npcink-abilities-toolkit' ); ?></p>
					<?php endif; ?>
				</div>
				<div class="npcink-abilities-toolkit-setting-row__control">
					<input id="npcink-abilities-toolkit-readonly-mode" type="checkbox" name="read_only_mode" value="1" <?php checked( $enabled ); ?> />
					<button type="submit" class="button"><?php echo esc_html__( 'Apply', 'npcink-abilities-toolkit' ); ?></button>
				</div>
			</div>
		</form>
		<?php
	}
}
