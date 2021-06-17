<?php

namespace SejoliTutorLMS;

defined('ABSPATH') || die();

class Installer {
    /**
     * Installer constructor
     * @since 1.0.0
     */
    public function __construct() {

        /* Enqueue styles and scripts */
        add_action('admin_init', [$this, 'check_plugin_dependency'], 99);
    }


    public function check_plugin_dependency() {
        if (!defined('TUTOR_VERSION')) {
            //Required Tutor Message
            add_action('admin_notices', array($this, 'notice_required_tutor'));
        }

    }

    /**
     * Notice for tutor lms plugin required
     * @since 1.0.0
     */
    public function notice_required_tutor() {
        ?>
        <div class="notice notice-error is-dismissible"><p>
            <h2>Sejoli Tutor LMS</h2> Harap install dan aktifkan plugin TUTOR LMS.
            </p>
        </div>
        <?php 
    }

}


