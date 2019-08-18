<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SIP_Render {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_register_style( 'sip-style', SIP_URL . '/css/style.css', array(), SIP_VERSION );
		wp_register_script( 'sip-script', SIP_URL . '/js/script.js', array( 'jquery' ), SIP_VERSION );
	}
}

return new SIP_Render();