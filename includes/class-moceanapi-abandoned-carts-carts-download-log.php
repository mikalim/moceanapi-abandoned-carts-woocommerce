<?php

class Moceanapi_Carts_Download_log {
	protected $log_directory;

	public function __construct() {
		$upload_dir          = wp_upload_dir();
		$this->log_directory = $upload_dir['basedir'] . '/moceanapi-abandoned-carts-woocommerce-logs/';
	}

	// public function register() {
	// 	add_submenu_page( null, 'MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME', '', 'manage_options', 'moceanapi_abandoned_carts-carts-download-file', array( $this, 'download' ) );
	// }

	public function download() {
		if ( isset( $_GET['file'] ) ) {
			$logFile = $this->log_directory . $_GET['file'] . '.log';
			echo "HIHI" . $logFile;
			if ( file_exists( $logFile ) ) {
				echo "<br> Exists" ;
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: text/plain' );
				header( 'Content-Disposition: attachment; filename="' . basename( $logFile ) . '"' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Length: ' . filesize( $logFile ) );
				ob_clean();
				flush();
				echo file_get_contents( $logFile );
			}
		}
		exit;
	}
}
