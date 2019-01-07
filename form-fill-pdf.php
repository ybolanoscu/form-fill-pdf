<?php
/**
 * Plugin Name: FormFill PDF Integration
 * Description: The FormFill PDF Integration add-on allows sending submitted forms in PDF format or Use a template PDF to fill.
 * Version: 0.2
 * Author:
 * Author URI:
 * License: GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'FORMFILL_PDF_VERSION', '0.2' );
define( 'FORMFILL_PDF_PREFIX', 'formfill' );
define( 'FORMFILL_PDF', plugin_basename( __FILE__ ) );
define( 'FORMFILL_PDF_DIR', WP_PLUGIN_DIR . "/" . plugin_basename( dirname( __FILE__ ) ) );
define( 'FORMFILL_PDF_URL', plugins_url( plugin_basename( dirname( __FILE__ ) ) ) );

final class FORMFILLPDF {
	/**
	 * FORMFILLPDF Constructor.
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'ff_pdf_activate' ) );
		add_action( 'fm_init_addons', array( $this, 'ff_pdf_int_init' ) );
	}

	function ff_pdf_activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$ff_pdf_options  = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "formfill_pdf_options` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `form_id` int(11) NOT NULL,
			  `enable_pdf` tinyint(4) NOT NULL,
			  `send_to` varchar(128) NOT NULL,
			  `pdf_path` varchar(250) NOT NULL,
			  `pdf_template` varchar(250) NOT NULL,
			  `pdf_content` longtext NOT NULL,
			   PRIMARY KEY (`id`)
			)  " . $charset_collate . ";";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $ff_pdf_options );
		$ff_pdf_data = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "formfill_pdf` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `form_id` int(11) NOT NULL,
			  `group_id` int(20) NOT NULL,
			  `file_url` varchar(255) NOT NULL,
			   PRIMARY KEY (`id`)
			)   " . $charset_collate . ";";
		dbDelta( $ff_pdf_data );
		$upload_dir = wp_upload_dir();
		if ( ! is_dir( $upload_dir['basedir'] . '/formfill/' . plugin_basename( dirname( __FILE__ ) ) ) ) {
			mkdir( $upload_dir['basedir'] . '/formfill/' . plugin_basename( dirname( __FILE__ ) ), 0777 );
		}
	}

	function ff_pdf_int_init() {
		add_filter( 'fm_get_addon_init', array( $this, 'ff_pdf_int_addon_tab' ), 10, 2 );
		add_filter( 'ff_get_templates', array($this, 'ff_get_templates') );
		add_action( 'fm_save_addon_init', array( $this, 'ff_pdf_int_save_addon' ) );
		add_action( 'fm_delete_addon_init', array( $this, 'ff_pdf_int_delete_addon' ) );
		add_action( 'WD_FM_PDF_init', array( $this, 'ff_pdf_int_frontend' ) );
		add_action( 'admin_print_scripts', array( $this, 'ff_pdf_int_load_scripts' ) );
	}

	function ff_get_templates($options) {

		$files = glob(FORMFILL_PDF_DIR . '/templates/*.pdf');
		foreach ( $files as $file ) {
			$option = array();
			$output = array();
			$fields = array();
			exec("pdftk '$file' dump_data_fields", $output );
			foreach ( $output as $item ) {
				if (strpos($item, "FieldName:") !== false) {
					$fields[] = substr($item, 11);
				};
			}
			$path_parts = pathinfo($file);
			$option = array(
				'label' => $path_parts['filename'],
				'value' => $path_parts['basename'],
				'fields' => $fields
			);
			$options[] = $option;
		}

		return $options;
	}

	/**
	 * Frontend.
	 *
	 * @param array $params
	 */
	function ff_pdf_int_frontend( $params ) {
		require_once( WDFM()->plugin_dir . '/framework/WDW_FM_Library.php' );
		require_once( 'controller.php' );
		$controller = new FORMFILL_PDF_controller;
		$controller->frontend( $params );
	}

	/**
	 * Addon tab.
	 *
	 * @param $addons
	 * @param $params
	 *
	 * @return mixed
	 */
	function ff_pdf_int_addon_tab( $addons, $params ) {
		require_once( WDFM()->plugin_dir . '/framework/WDW_FM_Library.php' );
		require_once( 'controller.php' );
		$controller = new FORMFILL_PDF_controller;
		ob_start();
		$controller->display( $params );
		$addon_key                    = 'WD_FM_PDF_INT';
		$addons['tabs'][ $addon_key ] = __( 'PDF/PDFILL Integration', WDFM()->prefix );
		$addons['html'][ $addon_key ] = ob_get_clean();

		return $addons;
	}

	/**
	 * Save addon.
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	function ff_pdf_int_save_addon( $id ) {
		require_once( WDFM()->plugin_dir . '/framework/WDW_FM_Library.php' );
		require_once( 'controller.php' );
		$controller = new FORMFILL_PDF_controller;
		$save       = $controller->save( $id );
	}

	/**
	 * Delete addon.
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	function ff_pdf_int_delete_addon( $id ) {
		require_once( WDFM()->plugin_dir . '/framework/WDW_FM_Library.php' );
		require_once( 'controller.php' );
		$controller = new FORMFILL_PDF_controller;
		$controller->delete( $id );
	}

	function ff_pdf_int_load_scripts() {
		wp_register_style( 'ff-pdf_int', FORMFILL_PDF_URL . '/css/FMJsPDFInt.css', array(), WDFM()->plugin_version );
		wp_register_script( 'ff-pdf_int', FORMFILL_PDF_URL . '/js/FMJsPDFInt.js', array(), WDFM()->plugin_version );
	}
}

$wdfmpdfi = new FORMFILLPDF();

add_action( 'plugins_loaded', 'ff_pdf_int_form_maker_check' );

function ff_pdf_int_form_maker_check() {
	if ( ! class_exists( 'WDFM' ) ) {
		add_action( 'fm_addon_print_msg', 'ff_addon_get_msg' );
	}
}

add_filter( 'fm_addon_msg', 'ff_pdf_int_message' );

function ff_pdf_int_message( $fm_addon_msg ) {
	$fm_addon_msg[] = 'PDF/PDFill Integration';

	return $fm_addon_msg;
}

if ( ! function_exists( 'ff_addon_get_msg' ) ) {
	// Call this function for output message
	function ff_addon_get_msg() {
		$fm_addon_msg = apply_filters( 'fm_addon_msg', array() );
		$addon_names  = implode( $fm_addon_msg, ', ' );
		$count        = count( $fm_addon_msg );

		$single = __( 'Please install Form Maker plugin version 2.12.0 and higher to start using %s add-on.', FORMFILL_PDF_PREFIX );
		$plural = __( 'Please install Form Maker plugin version 2.12.0 and higher to start using %s add-ons.', FORMFILL_PDF_PREFIX );
		echo '<div class="error"><p>' . sprintf( _n( $single, $plural, $count, FORMFILL_PDF_PREFIX ), $addon_names ) . '</p></div>';
	}

	function ff_print_addon_msg() {
		do_action( 'fm_addon_print_msg' );
	}

	add_action( 'admin_notices', 'ff_print_addon_msg' );
}

require_once __DIR__ . '/actions.php';