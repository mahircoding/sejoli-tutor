<?php
/*
Plugin Name: Sejoli Feat Tutor LMS
Plugin URI: http://koding.info/plugins/sejoli-tutorlms/
Description: Sejoli tutor LMS
Author: M Ali
Version: 1.0.5
Author URI: http://koding.info/
*/

define('SEJOLI_TUTOR_DIR', __DIR__ );
define('SEJOLI_TUTOR_VERSION', '1.0.5' );
define('SEJOLI_TUTOR_BUILD_VERSION', 1 );


/**
 * Instantiate Base Class after plugins loaded
 */
add_action('plugins_loaded', 'sejoli_tutor_lms_init');
function sejoli_tutor_lms_init() {
    if (!function_exists('tutor_lms')) {
        require_once SEJOLI_TUTOR_DIR . '/Installer.php';
        new \SejoliTutorLMS\Installer();
    } else {
        require SEJOLI_TUTOR_DIR . '/class/class-foundation.php';
        require SEJOLI_TUTOR_DIR . '/bootstrap.php';
    }


    if(!defined('SEJOLISA_VERSION')) :

        add_action('admin_notices', 'sejolp_no_sejoli_functions');

        function sejolp_no_sejoli_functions() {
            ?><div class="notice notice-error is-dismissible"><p>
            <h2>Sejoli Tutor LMS</h2> Harap install dan aktifkan plugin SEJOLI.
            </p>
        </div><?php
        }

        return;
    endif;

}


/**
 * Plugin update checker
 */

require_once(SEJOLI_TUTOR_DIR . '/updater/plugin-update-checker.php');

$update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/ihsansahab/sejoli-tutor',
    __FILE__,
    'sejoli-tutor'
);

$update_checker->setBranch('master');