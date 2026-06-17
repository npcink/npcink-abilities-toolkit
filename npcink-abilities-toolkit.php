<?php
/**
 * Plugin Name: Npcink Abilities Toolkit
 * Description: Standalone WordPress Abilities API package toolkit for safely exposing agent-callable abilities.
 * Version: 0.5.1
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Npcink
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: npcink-abilities-toolkit
 * Domain Path: /languages
 *
 * @package NpcinkAbilitiesToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NPCINK_ABILITIES_TOOLKIT_VERSION', '0.5.1' );
define( 'NPCINK_ABILITIES_TOOLKIT_FILE', __FILE__ );
define( 'NPCINK_ABILITIES_TOOLKIT_DIR', plugin_dir_path( __FILE__ ) );

require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Registry/Schema_Normalizer.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Registry/Annotation_Normalizer.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Registry/Contract_Normalizer.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Security/Permission_Callbacks.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Support/Gutenberg_Block_Document.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Registry/Category_Registrar.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Registry/Ability_Registrar.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Integration/Npcink_Catalog_Bridge.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Workflow/Workflow_Definition_Provider.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Read_Pack_Classifier.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Article_Block_Plan_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Article_Optimization_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Article_Production_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Block_Theme_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Comment_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Content_Intent_Router_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Content_Inventory_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Content_Refresh_SEO_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Diagnostics_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Gutenberg_Block_Capability_Catalog_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Gutenberg_Composer_Repair_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Gutenberg_Recipe_Evaluation_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Internal_Link_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Media_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Page_Pattern_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Page_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Post_Primitives_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Publishing_Workflow_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Style_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Traits/Taxonomy_Read_Methods.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Definitions/Agent_Usage_Metadata.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Definitions/Core_WordPress_Read_Definitions.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Read_Definitions/WordPress_Diagnostics_Definitions.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Read_Package.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Write_Package.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Destructive_Package.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Comment_Pack_Classifier.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Comment_Definitions/Core_Comment_Definitions.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Packages/Core_Comment_Package.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Rest/Contract_Controller.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Admin/Test_Page.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/Plugin.php';
require_once NPCINK_ABILITIES_TOOLKIT_DIR . 'includes/functions.php';

add_action(
	'init',
	static function () {
		Npcink_Abilities_Toolkit\Plugin::instance()->boot();
	},
	0
);

if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook(
		__FILE__,
		static function () {
			$plugin = Npcink_Abilities_Toolkit\Plugin::instance();
			$plugin->boot();
			$plugin->abilities()->emit_manual_catalog_refresh( 'activation' );
		}
	);
}
