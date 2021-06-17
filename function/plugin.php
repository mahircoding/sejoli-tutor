<?php
defined('ABSPATH') or die('Hello, World!');

use JasonGrimes\Paginator;


if (!function_exists('has_sejoli')) {
    function has_sejoli() {
        return true;
    }
}


function sjt_get_course_post_type() {
    return 'courses';
}

function sejoli_tutor_course_page() {
    global $wpdb;
    $user       = wp_get_current_user();
    $input      = apply_filters('sejoli_tutor/member_area/paging', $_GET);
    $perpage    = apply_filters('sejoli_tutor/member_area/perpage', 10);

    $v          = new Valitron\Validator($input);
    $v->rule('numeric', 'page_number');
    if($v->validate() && $user->ID > 0) {

        $all_courses_id = [];
        $query_courses_id = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'courses'", ARRAY_A);
        foreach ($query_courses_id as $entry) {
             $all_courses_id[] = $entry['ID']; 
        }

        $all_courses_id_in = implode(',', $all_courses_id);

        $sql = "SELECT ID, post_parent FROM {$wpdb->posts} WHERE post_author = {$user->ID} AND post_type = 'tutor_enrolled' AND post_parent IN ($all_courses_id_in)";
        $query = $wpdb->get_results($sql, ARRAY_A);

        $my_courses_id = [];

        foreach($query as $entry){
            $my_courses_id[] = $entry['post_parent'];
        }

        $total = count($my_courses_id);
        $posts = [];
        if ( count($my_courses_id) > 0) {


            $from = (intval($input['page_number']) - 1) * $perpage;
            $my_courses_id_in = implode(',', $my_courses_id);

            $sql = "SELECT * FROM {$wpdb->posts} WHERE post_type = 'courses' AND ID IN ($my_courses_id_in) ORDER BY post_title LIMIT {$from}, {$perpage} ";
            $query = $wpdb->get_results($sql, ARRAY_A);

            foreach ($query as $entry) {
                $posts[] = array(
                    'ID' => intval($entry['ID']),
                    'title' => $entry['post_title'],
                    'permalink' => home_url('courses/' . $entry['post_name'] . '/'),
                    'thumbnail' => get_the_post_thumbnail_url( intval($entry['ID']), 'medium' ),
                );
            }
        }

        echo '<div class="my-tutor-course-dashboard">';
        if (count($posts) < 1) {
            ?>
            <div class="my-course-entries">
                <div class="my-courses-empty">
                    <img width="132" src="<?php echo plugin_dir_url(SEJOLI_TUTOR_DIR) . 'sejoli-tutor/assets/img/course-empty.svg'; ?>">
                    <div class="message">Anda belum memiliki kursus</div>
                </div>
            </div>
            <?php
        } else {
            foreach($posts as $post) {

                ?>

                <div class="my-course-entries">
                    <article class="my-course">
                        <div class="thumbnail">
                            <a class="my-course-link" href="<?php echo $post['permalink']; ?>">
                                <img src="<?php echo $post['thumbnail']; ?>">
                            </a>
                        </div>
                        <div class="body">
                            <a class="my-course-link" href="<?php echo $post['permalink']; ?>">
                                <div class="title"><?php echo $post['title']; ?></div>
                            </a>
                        </div>
                    </article>
                </div>

                <div class="paging">
                    <?php 
                    if ($total > count($post)) {
                        $current_page   = intval($input['page_number']);
                        $pattern        = home_url('member-area/course/?page_number=') . '(:num)';

                        $paginator      = new Paginator($total, $perpage, $current_page, $pattern);

                        echo $paginator; 
                    }
                    ?>
                </div>
                <?php

            }
        }

        echo '</div>';

    } else {
        ?>
            <div class="my-course-entries">
                <div class="my-courses-empty">
                    <img width="132" src="<?php echo plugin_dir_url(SEJOLI_TUTOR_DIR) . 'sejoli-tutor/assets/img/course-empty.svg'; ?>">
                    <div class="message">Data tidak ditemukan</div>
                </div>
            </div>
        <?php
    }
}

if( ! function_exists( 'wc_get_product' ) && !file_exists(ABSPATH . '/wp-content/plugins/woocommerce/includes/wc-product-functions.php') ) {
    try {
        class Temporary_Product {
            public $product;
            public $price_formatted;

            public function __construct( $product_id )
            {

                $this->product = sejolisa_get_product( $product_id );
                $price = $this->product->price ?? 0;

                if ($price > 0) {
                    $this->price_formatted = apply_filters( 'sejoli_tutor/harga_format_idr', $price );
                } else {
                    $this->price_formatted = '<span class="price">Free</span>';
                }
            }

            public function get_price_html() {
                return $this->price_formatted;
            }
        }

        function wc_get_product( $product_id ) {
            return new Temporary_Product( $product_id );
        }
    } catch(\Exception $error) { }
}
