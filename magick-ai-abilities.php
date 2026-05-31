<?php
/**
 * Plugin Name: Magick AI Abilities
 * Description: Standalone WordPress Abilities API package toolkit for safely exposing agent-callable abilities.
 * Version: 0.4.0
 * Requires at least: 6.9
 * Requires PHP: 7.2
 * Author: Magick AI
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: magick-ai-abilities
 *
 * @package MagickAIAbilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MAGICK_AI_ABILITIES_VERSION', '0.4.0' );
define( 'MAGICK_AI_ABILITIES_FILE', __FILE__ );
define( 'MAGICK_AI_ABILITIES_DIR', plugin_dir_path( __FILE__ ) );

require_once MAGICK_AI_ABILITIES_DIR . 'includes/Registry/Schema_Normalizer.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Registry/Annotation_Normalizer.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Registry/Contract_Normalizer.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Security/Permission_Callbacks.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Registry/Category_Registrar.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Registry/Ability_Registrar.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Integration/Magick_Catalog_Bridge.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Workflow/Workflow_Definition_Provider.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Read_Pack_Classifier.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Traits/Content_Inventory_Read_Methods.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Traits/Diagnostics_Read_Methods.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Traits/Media_Read_Methods.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Traits/Page_Read_Methods.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Traits/Taxonomy_Read_Methods.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Definitions/Agent_Usage_Metadata.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Definitions/Core_WordPress_Read_Definitions.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Read_Definitions/WordPress_Diagnostics_Definitions.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Read_Package.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Write_Package.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Destructive_Package.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Comment_Pack_Classifier.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Comment_Definitions/Core_Comment_Definitions.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Packages/Core_Comment_Package.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Admin/Test_Page.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/Plugin.php';
require_once MAGICK_AI_ABILITIES_DIR . 'includes/functions.php';

add_action(
	'init',
	static function () {
		Magick_AI_Abilities\Plugin::instance()->boot();
	},
	0
);
