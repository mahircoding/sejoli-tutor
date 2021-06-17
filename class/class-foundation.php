<?php
namespace SejoliTutor;

defined('ABSPATH') || die('Hello, World!');

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Foundation {
    public static function loader() {
        self::integration();
        require SEJOLI_TUTOR_DIR . '/class/class-ajax.php';
        // require SEJOLI_TUTOR_DIR . '/class/class-transient.php';
        \SejoliTutor\Ajax::activate();

        add_action( 'admin_menu', '\SejoliTutor\Foundation::action_admin_menu' );
        add_action( 'admin_init', '\SejoliTutor\Foundation::action_admin_action_init' );

        if (self::authorized()) {

            add_action( 'init', '\SejoliTutor\Foundation::action_init' );
            add_action( 'template_redirect', '\SejoliTutor\Foundation::action_template_redirect' );

            // \SejoliTutor\Transient::activate();
        }
    }


    public static function integration() {
        // Integrasikan Tutor LMS ke produk sejoli
        add_filter( 'sejoli/product/fields', function ($fields) {
            if ( defined( 'TUTOR_VERSION' ) ) {
                $fields[]   = [
                    'title'     => __( 'Tutor LMS', 'sejoli' ),
                    'fields'    => [
                        Field::make( 'separator', 'sep_tutor' , 'Integrasi Tutor LMS')
                            ->set_classes( 'sejoli-with-help' ),
                        Field::make('association', 'tutor_course', 'Tutor LMS Course')
                            ->set_types([
                                [
                                    'type'      => 'post',
                                    'post_type' => sjt_get_course_post_type(),
                                ]
                            ])
                            ->set_max(1)  // Tutor sampai dengan versi 1.8.10 hanya support relasi One to One. 
                            ->set_help_text(__('Course yang akan digunakan pada pembelian produk ini', 'sejoli')),
                        ]
                    ];
            }
            return $fields;
        }, 11, 1 );

        add_filter( 'sejoli/product/fields', function ($fields) {
            if ( defined( 'TUTOR_VERSION' ) && isset($fields['general'])) {
                $newFields = array();
                foreach($fields['general']['fields'] as $carbonField) {
                    $newFields[] = $carbonField;
                    if( $carbonField->get_base_name() == 'price' ) {
                        $newFields[] = Field::make('text', 'price_strike', __('Harga coret', 'sejoli'))
                            ->set_attribute('type', 'text')
                            ->set_default_value('');
                    }
                }
                $fields['general']['fields'] = $newFields;
            }
            return $fields;
        }, 12, 1);
    }

    

    public static function action_admin_menu() {

        $page_title = 'Sejoli Tutor LMS';
        $menu_title = 'Sejoli Tutor LMS';
        $capability = 'manage_options';
        $menu_slug  = 'sejoli_tutor';
        $function   = '\SejoliTutor\Foundation::action_admin_menu_content';
        $icon_url   = 'dashicons-schedule';
        $position   = 4;

        add_menu_page( $page_title, $menu_title,  $capability,  $menu_slug,  $function,  $icon_url,  $position );
    }

    public static function action_admin_menu_content() {
        ob_start();
        if ( !self::authorized() ) {
        ?>
            <div class="wrap">
                <div class="wrap-sejoli-tutor">
                    <div id="serial-wrap" class="serial-wrap">
                        <p>Silahkan masukkan kode lisensi pada kolom di bawah</p>
                        <input type="hidden" id="sejoli_tutor_nonce" name="nonce" value="<?php echo wp_create_nonce( 'sejoli_tutor' ); ?>">
                        <input name="sejoli_tutor_serial" type="text" id="sejoli_tutor_serial" value="" class="regular-text">
                        <button id="sejoli-tutor-serial-btn" class="button button-primary">Simpan</button>
                        <div id="serial-wrap-info" class="serial-wrap"></div>
                    </div>
                </div>
            </div>

        <?php
        } else {
            $field_redirect_tutor_dashboard = boolval( get_option( '_sejoli_tutor_disable_tutor_dashboard', false ) );
            $show_redirect_tutor = $field_redirect_tutor_dashboard ? ' checked="checked"' : '';
        ?>
            <div class="wrap">
                <div class="wrap-sejoli-tutor">
                    <h1>Pengaturan Sejoli Tutor LMS</h1>

                    <div class="sejoli-tutor-option-list">
                        <div class="sejoli-tutor-option-field-row">
                            <div class="sejoli-tutor-option-field-label">
                                <label for="redirect_tutor_dashboard">Member area</label>
                            </div>
                            <div class="sejoli-tutor-option-field">
                                <label>
                                    <input type="checkbox" id="redirect_tutor_dashboard" name="sejoli_tutor_option[redirect_tutor_dashboard]" value="1" <?php  echo $show_redirect_tutor; ?>>
                                    Alihkan Dashboard Tutor LMS ke Member Area Sejoli	
                                </label>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="sejoli_tutor_nonce" name="nonce" value="<?php echo wp_create_nonce( 'sejoli_tutor' ); ?>">
                    <button id="sejoli-tutor-save-setting-btn" class="button button-primary button-lg">Simpan pengaturan</button>


                </div>
            </div>
        <?php
        }
        return ob_get_contents();
    }

    public static function authorized() {
        return boolval( get_option( '_sejoli_tutor_authorized', 0 ) );
    }

    public static function action_init() {
        $cek                = false;
        if ( defined('SEJOLISA_VERSION') && defined('TUTOR_VERSION') ) {
            $cek            = true;
        }

        if ( ! $cek ) {
            $sejoli_file    = ABSPATH . '/wp-content/plugins/sejoli/sejoli.php';
            $tutor_file     = ABSPATH . '/wp-content/plugins/tutor/tutor.php';

            if ( file_exists($sejoli_file) && file_exists($sejoli_file) ) {
                $cek        = true;
            }
        }

        if( $cek ) {
            require SEJOLI_TUTOR_DIR . '/class/class-sejoli.php';
            $integrasi = new Sejoli_Tutor();
            add_action( 'wp_enqueue_scripts', function() {
                wp_enqueue_style('sejoli-tutor-app', plugin_dir_url( SEJOLI_TUTOR_DIR . '/assets' ) . 'assets/css/main.css', array() );
                wp_enqueue_script('sejoli-tutor-app', plugin_dir_url( SEJOLI_TUTOR_DIR . '/assets' ) . 'assets/js/app.js', array(), '1.0.0', true );
            });
        } else {
            add_action( 'admin_notices', function(){
                echo '<div class="notice notice-error is-dismissible sejoli-tutor-notice"><p>';
                echo "<h2>Sejoli Feat Tutor LMS</h2> Harap install dan aktifkan plugin Sejoli dan Tutor LMS.";
                echo '</p></div>';
            } );
        }
    
        $sejoli_tutor_disable_tutor_dashboard = boolval( get_option( '_sejoli_tutor_disable_tutor_dashboard', false ) );
        if ( $sejoli_tutor_disable_tutor_dashboard ) {
            add_filter('rewrite_rules_array', function($rules){
                foreach($rules as $key => $value) {
                    if (preg_match("#\(dashboard\)#", $key)) {
                        unset( $rules[$key]);
                    }
                }
                return $rules;
            }, 10);
        }
    }

    public static function action_admin_action_init() {
        add_action( 'admin_enqueue_scripts', function() {
            wp_register_style( 'sejoli-tutor-admin', plugin_dir_url( SEJOLI_TUTOR_DIR . '/assets' ) . 'assets/css/admin.css', array(), '1.0.0' );
            wp_enqueue_script( 'sejoli-tutor-admin', plugin_dir_url( SEJOLI_TUTOR_DIR . '/assets' ) . 'assets/js/admin.js', array('jquery-core'), '1.0.0', true );
            wp_enqueue_style( 'sejoli-tutor-admin' );

            wp_localize_script( 
                'sejoli-tutor-admin', 
                'sejoli_tutor_admin', 
                array( 
                    'cek_serial_ajax' => 'https://sejolitutor.brizpress.com/api-license/validate/',
                    'save_serial_ajax' => admin_url( 'admin-ajax.php?action=sejoli_tutor_save_credential' ),
                    'save_setting_ajax' => admin_url( 'admin-ajax.php?action=sejoli_tutor_save_setting' ),
                    'credential' => array(
                        'email' => wp_get_current_user()->data->user_email ?? '',
                        'serial_number' => '',
                        'domain' => home_url(),
                        'application' => 'sejoli-tutor',
                        'path' => ABSPATH,
                    ),
                )
            );

        });

    }

    public static function action_template_redirect() {
        $sejoli_tutor_disable_tutor_dashboard = boolval( get_option( '_sejoli_tutor_disable_tutor_dashboard', false ) );
        if ( $sejoli_tutor_disable_tutor_dashboard ) {
            if( get_query_var('pagename') == 'dashboard' ) {
                wp_safe_redirect( home_url('member-area'), 302 );
                exit();
            }
        }

    }
    
}
