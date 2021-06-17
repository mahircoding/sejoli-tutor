<?php
namespace SejoliTutor;

defined('ABSPATH') || die('Hello, World!');

class Transient {
    public static function activate() {
        $self = new static;

        add_filter( 'plugins_api', array( $self, '_update_info', 20, 3 ) );
        add_filter( 'site_transient_update_plugins', array( $self, '_plugin_push_update' ) );
        add_action( 'upgrader_process_complete', array( $self, '_action_after_update' ), 10, 2 );
    }

    public function _action_after_update( $upgrader_object, $options ) {
        if ($options[ 'action' ] == 'update' && $options[ 'type' ] === 'plugin') {
            delete_transient( 'sejoli_tutor_get_update' );
        }
    
    }

    public function _update_info_transient() {
        $url = sprintf( 
            'https://plugin.repo.my.id/repository/info/%s.json', 
            get_option( '_sejoli_tutor_public_key', 'sejoli-tutor' ) 
        );

        $remote = wp_remote_post( $url, array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                ),
                'body' => array(
                    'application' => 'sejoli-tutor',
                    'referer' => home_url(),
                    'wp-admin' => admin_url(),
                    'serial-number' => get_option( '_sejoli_tutor_serial_number', 'sejoli-tutor' ),
                    'public-key' => get_option( '_sejoli_tutor_public_key', 'sejoli-tutor' ),
                    'secret-key' => get_option( '_sejoli_tutor_secret_key', 'sejoli-tutor' ),
                ))
            );

        if ( !is_wp_error( $remote ) && isset( $remote['body'] ) ) {
            set_transient( 'sejoli_tutor_get_update', $remote, 30 );
            $res = json_decode( $remote['body'] );
            return $res;
        }

        return false;
    }

    public function _update_info( $res, $action, $args ) {
        if ($action !== 'plugin_information') {
            return false;
        }

        if ('sejoli-tutor' !== $args->slug) {
            return false;
        }

        if ( false == $transito = get_transient( 'sejoli_tutor_get_update' ) ) {
            return $this->_update_info_transient();
        } 
        return false;

    }

    public function _plugin_push_update( $transient ) {

        if (empty( $transient->checked )) {
            return $transient;
        }
    
        if ( false == $transito = get_transient( 'sejoli_tutor_get_update' ) ) {
            $this->_update_info_transient();
        } 

        if ( is_array($transito) && isset( $transito['body']) ) {
            $body = json_decode( $transito['body'] );
            $res = new \stdClass();
            $res->slug = 'sejoli-tutor';
            $res->plugin = 'sejoli-tutor/sejoli-tutor.php'; 

            if (isset($body->version)) {
                $res->new_version = $body->version;
                $res->tested = $body->tested;
                $res->package = $body->download_link;
                $transient->response['sejoli-tutor/sejoli-tutor.php'] = $res;
            }
        }
        
        return $transient;
    
    }



}
