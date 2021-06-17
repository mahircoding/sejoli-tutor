<?php
namespace SejoliTutor;

defined('ABSPATH') || die('Hello, World!');


class Ajax {
    public static function activate() {

        $self = new static;

        add_action( "wp_ajax_sejoli_tutor_save_credential", array( $self, 'endpoint_save_credential') );
        add_action( "wp_ajax_nopriv_sejoli_tutor_save_credential",  array( $self, "ajax_authorize_app") );
        add_action( "wp_ajax_sejoli_tutor_save_setting", array( $self, 'endpoint_save_setting') );
        add_action( "wp_ajax_nopriv_sejoli_tutor_save_setting",  array( $self, "ajax_authorize_app") );
        
    }

    public function ajax_authorize_app() {
        if ( !wp_verify_nonce( $_REQUEST['nonce'], "sejoli_tutor" ) ) {
            wp_send_json( ['code' => 400] );
            exit();
        }
    }

    public function endpoint_save_credential() {
        $raw = json_decode( file_get_contents("php://input"), true );

        if ( is_array($raw) ) {
            
            if ( !wp_verify_nonce( $raw['nonce'], "sejoli_tutor" ) ) {
                wp_send_json( ['code' => 400] );
                exit();
            }

            $sid = $raw['data']['sid'] ?? '';
            $token = $raw['data']['token'] ?? '';
            $serial = $raw['data']['serial'] ?? '';

            if ( !empty($sid) && !empty($token) ) {

                update_option( '_sejoli_tutor_authorized', 1 );
                update_option( '_sejoli_tutor_public_key', $sid );
                update_option( '_sejoli_tutor_secret_key', $token );
                update_option( '_sejoli_tutor_serial_number', $serial );
                wp_send_json( ['code' => 200] );
                exit();
            }
        }

        wp_send_json( ['code' => 400] );
        exit();
    }

    public function endpoint_save_setting() {
        $raw = json_decode( file_get_contents("php://input"), true );

        if ( is_array($raw) ) {
            if ( !wp_verify_nonce( $raw['nonce'], "sejoli_tutor" ) ) {
                wp_send_json( ['code' => 400] );
                exit();
            }
            
            if ( isset( $raw['data']['redirect_tutor_dashboard'] ) ) {
                $redirect_tutor_dashboard = boolval( $raw['data']['redirect_tutor_dashboard'] );
                update_option( '_sejoli_tutor_disable_tutor_dashboard', $redirect_tutor_dashboard );

                wp_send_json( ['code' => 200] );
                exit();
            }
        }

        wp_send_json( ['code' => 400] );
        exit();
    }

    
}
