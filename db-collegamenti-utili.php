<?php
/**
 * Plugin Name:       DB Collegamenti Utili
 * Plugin URI:        https://www.davidebertolino.it/progetti/
 * Description:        Gestisce bandi/PON e collegamenti utili (logo, descrittivo, allegati) e li mostra in home tramite un carosello accessibile che riusa il componente Bootstrap Italia del tema Design Scuola Italia. Shortcode: [db_collegamenti_utili].
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Davide Bertolino
 * Author URI:        https://www.davidebertolino.it
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       db-collegamenti-utili
 * Domain Path:       /languages
 *
 * @package DB_Collegamenti_Utili
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DBCU_VERSION', '1.2.0' );
define( 'DBCU_FILE', __FILE__ );
define( 'DBCU_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBCU_URL', plugin_dir_url( __FILE__ ) );
define( 'DBCU_CPT', 'dbcu_bando' );

require_once DBCU_DIR . 'inc/class-cpt.php';
require_once DBCU_DIR . 'inc/class-metabox.php';
require_once DBCU_DIR . 'inc/class-shortcode.php';

if ( file_exists( DBCU_DIR . 'inc/class-updater.php' ) ) {
	require_once DBCU_DIR . 'inc/class-updater.php';
}

/**
 * Avvio del plugin.
 */
function dbcu_bootstrap() {
	DBCU_CPT_Manager::init();
	DBCU_Metabox::init();
	DBCU_Shortcode::init();

	if ( class_exists( 'DB_GitHub_Updater' ) ) {
		new DB_GitHub_Updater( DBCU_FILE, 'dadebertolino', 'db-collegamenti-utili' );
	}
}
add_action( 'plugins_loaded', 'dbcu_bootstrap' );

/**
 * Carica il template singolo dal plugin se il tema non lo sovrascrive.
 *
 * @param string $template Percorso template corrente.
 * @return string
 */
function dbcu_single_template( $template ) {
	if ( is_singular( DBCU_CPT ) ) {
		$theme_template = locate_template( array( 'single-' . DBCU_CPT . '.php' ) );
		if ( $theme_template ) {
			return $theme_template;
		}
		$plugin_template = DBCU_DIR . 'templates/single-' . DBCU_CPT . '.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	return $template;
}
add_filter( 'single_template', 'dbcu_single_template' );

/**
 * Attivazione: registra il CPT e forza il flush dei rewrite.
 */
function dbcu_activate() {
	require_once DBCU_DIR . 'inc/class-cpt.php';
	DBCU_CPT_Manager::register_cpt();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dbcu_activate' );

/**
 * Disattivazione: pulisce i rewrite.
 */
function dbcu_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dbcu_deactivate' );
