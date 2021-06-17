<?php
namespace SejoliTutor;
defined('ABSPATH') || die('Hello, World!');

class Sejoli_Tutor extends \TUTOR\Tutor_Base {

    public $course_post_type;
	public $lesson_post_type;
	public $lesson_base_permalink;
    

    public function __construct() {
		$this->course_post_type = tutor()->course_post_type;
		$this->lesson_post_type = tutor()->lesson_post_type;

        $this->lesson_base_permalink = tutor_utils()->get_option( 'lesson_permalink_base' );
		if ( ! $this->lesson_base_permalink){
			$this->lesson_base_permalink = $this->lesson_post_type;
		}

        // Tambahkan Sejoli ke list monetize tutor
		add_filter('tutor_monetization_options', array($this, 'tutor_monetization_options'));

        $monetize_by = tutils()->get_option('monetize_by');
		if ($monetize_by !== 'sejoli') {
			return;
		}

		/* actions */
		add_action( 'add_meta_boxes', 							array( $this, 'register_meta_box' ) );
		add_action( 'carbon_fields_post_meta_container_saved', 	array( $this, '_action_sejoli_update_product') );
		add_action( 'sejoli/order/set-status/completed', 		array( $this, '_action_sejoli_order_completed') );
		add_action( 'sejoli/order/set-status/cancelled', 		array( $this, '_action_sejoli_order_cancelled') );

		/* filter */
		add_filter( 'is_course_purchasable', 		 array( $this, '_filter_course_purchasable' ) );
		add_filter( 'get_tutor_course_price', 		 array( $this, '_filter_get_course_price' ), 2 );
		add_filter( 'tutor-loop-default-price', 	 array( $this, '_filter_get_course_price' ) );

		add_filter( 'sejoli_tutor/harga_format_idr', array( $this, '_filter_format_harga_kursus' ) );
		add_filter( 'sejoli_tutor/price_strike', 	 array( $this, '_filter_format_harga_kursus_strike' ), 10, 2);
		// UI course
		add_filter( 'tutor_course_loop_price', 		 array( $this, '_filter_ui_loop_course_price' ) );
		add_filter( 'tutor_course/single/add-to-cart', 		 array( $this, '_filter_ui_single_add_to_cart' ) );


		add_filter( 'sejoli/member-area/menu', function($data) {
			$output = array();
			$course_menu = [
	            'link'  => site_url('member-area/course/'),
	            'label' => __('Course','sejoli'),
	            'icon'  => 'graduation cap icon',
	            'class' => 'item',
	            'submenu' => [

	            ]
	        ];

	        foreach($data as $key => $value) {
	        	$output[$key] = $value;

	        	if ($key == 'user-order') {
	        		$output['course'] = $course_menu;
	        	}
	        }
	        return $output;
		});

    	add_filter('sejoli_tutor/member_area/paging', function($array) {
    		if (!isset($array['page_number'])) {
    			$array['page_number'] = 1;
    		}
    		if (!isset($array['sort'])) {
    			$array['sort'] = 'asc';
    		}
    		if (!isset($array['sort_by'])) {
    			$array['sort_by'] = 'title';
    		}

    		return $array;

    	});

		add_filter('sejoli/template-file', function($file, $template_file){
			if (preg_match('#template/course.php#is', $file)) {
				return SEJOLI_TUTOR_DIR . '/template/course.php';
			}

			return $file;
		}, 10, 2);
    }

    public function tutor_monetization_options($arr) {
		$arr['sejoli'] = __('Sejoli', 'tutor');
		return $arr;
	}

	public function handle_by_sejoli() {

		return false;
	}

    public function register_meta_box() {
		add_meta_box('tutor-sejoli', __('Sejoli', 'tutor'), array($this, 'course_sejoli_metabox'), $this->course_post_type, 'advanced', 'high');
	}

	public function course_sejoli_metabox() {

		$courseID = get_the_ID();

		$productID = (int) get_post_meta( $courseID, '_sejoli_product_id', true );
		$content = '<div class="sejoli-inner">';

		if ($productID == 0) {
			// tidak ada relasi produk
			$content .= '<div class="metabox-no-sejoli-product"><span>Belum terhubung ke sejoli</span><span class="link"><a href="edit.php?post_type=sejoli-product">Tautkan produk Sejoli</a></span></div>';
		} else {
			$sejoliProduk = $this->get_sejoli_product( $productID );
			$content .= '<div class="tutor-option-field-row">';
			$content .= '	<div class="tutor-option-field-label"><label for="">Produk sejoli</label></div>';
			$content .= '	<div class="tutor-option-field">';
			$content .= '		<div class="sejoli-title"><a href="' . $sejoliProduk['permalink'] . '">' . $sejoliProduk['post_title'] . '</a></div>';
			$content .= '		<div class="sejoli-price">' . $sejoliProduk['price'] . '</div>';
			$content .= '		<div class="sejoli-edit-link"><a href="post.php?post='.$sejoliProduk['ID'].'&action=edit">Edit produk </a></div>';
			$content .= '	</div>';
			$content .= '</div>';
			
		}
		$content .= '</div>';
		
		ob_start();
		?>
		<div class="sejoli-metabox">
			<?php echo $content; ?>
		</div>
		<?php
		echo ob_get_clean();
	}

	public function _action_sejoli_order_completed($order){
		$sejoliOrderID = intval($order['ID']);
        $userID = intval($order['user_id']);
		$productID = intval($order['product_id']);
        $courseID = intval( get_post_meta($productID, '_tutor_course_id', true) );
		
		if ($courseID > 0) {
			
			$enroll_data = array(
				'post_type'     => 'sejoli_tutor_order',
				'post_title'    => sprintf('Order tutor/user: %s /course: %s', $userID, $courseID),
				'post_status'   => 'completed',
				'post_author'   => $userID,
				'post_parent'   => $courseID,
				'post_content'  => json_encode($order)
			);
			
			$orderID = wp_insert_post( $enroll_data );
			
			tutor_utils()->do_enroll( $courseID, $orderID, $userID );
		}
    }

	public function _action_sejoli_order_cancelled($order) {
		// $sejoliOrderID = intval($order['ID']);
        $userID = intval($order['user_id']);
		$productID = intval($order['product_id']);
        $courseID = intval( get_post_meta($productID, '_tutor_course_id', true) );

		tutor_utils()->cancel_course_enrol(
			$courseID,
			$userID
		);
	}


	public function _action_sejoli_update_product($sejoliProductID) {
		global $wpdb;

		// hapus data kursus
		$courses         = carbon_get_post_meta( $sejoliProductID, 'tutor_course' );
		
		if ( intval($sejoliProductID) > 0 && is_array( $courses ) ) {
			
			if (count($courses) > 0) {
				// menambahkan relasi
				$courseID        = intval($courses[0]['id']);
				$price           = (int) get_post_meta( $sejoliProductID, '_price', true ) ?? 0;
				$price_formatted = apply_filters( 'sejoli_tutor/harga_format_idr', $price );

				update_post_meta( $courseID, '_sejoli_active', 1 );
				update_post_meta( $courseID, '_sejoli_product_id', $sejoliProductID );
				update_post_meta( $courseID, '_sejoli_product_price', $price );
				update_post_meta( $courseID, '_sejoli_product_price_formatted', $price_formatted );
				
				update_post_meta( $sejoliProductID, '_tutor_course_id', $courseID );
			} else {
				// menghapus relasi: no course id selected
				update_post_meta( $sejoliProductID, '_tutor_course_id', 0 );
				// get couse id
				$query = $wpdb->get_results( $wpdb->prepare(
					"SELECT * 
					FROM 	{$wpdb->postmeta}
					WHERE	meta_key = '_sejoli_product_id' 
							AND meta_value = '%s'
					", 
					$sejoliProductID
				) );

				if (count($query) > 0) {
					foreach($query as $entry) {
						$courseID = intval($entry->post_id);
						update_post_meta( $courseID, '_sejoli_active', '0' );
						update_post_meta( $courseID, '_sejoli_product_id', '0' );
						update_post_meta( $courseID, '_sejoli_product_price', '0' );
						update_post_meta( $courseID, '_sejoli_product_price_formatted', '' );
					}
				}

			}
		}
	}

	public function is_course_purchasable($courseID = null){
		if (is_null($courseID)) {
			$courseID = tutor_utils()->get_post_id();
		} else {
			$courseID = tutor_utils()->get_post_id($courseID);
		}

		if (intval($courseID) > 0) {
			$sejoli = boolval( get_post_meta( $courseID, '_sejoli_active', true ) ); 
			if ($sejoli) {
				return true;
			}
		}
		return $courseID;
	}

	public function _filter_course_purchasable($courseID){
		return $this->is_course_purchasable($courseID);
	}

	public function _filter_get_course_price($content){
		$courseID = tutor_utils()->get_post_id();
		
		if ($courseID > 0) {
			$price = get_post_meta( $courseID, '_sejoli_product_price_formatted', true );
			$sejoli_product = $this->get_sejoli_product_by_course( $courseID );

			$price_strike       = apply_filters( 'sejoli_tutor/price_strike', 0, $sejoli_product['ID'] );
			$price_strike_html 	= '<span class="strike-price">'. $price_strike .'</span>';

			if ( !empty( $price_strike ) ) {
				$price     = sprintf('<span class="active-price">%s</span> <s class="active-strike">%s</s>', $price, $price_strike_html );
			}

			if (!empty($price)) {
				$content = $price;
			}
		}
		return $content;
	}

	public function _filter_sejoli_produk_metabox($fields) {

		$fields[]   = [
			'title'     => __('Tutor LMS', 'sejoli'),
			'fields'    => [
				Field::make( 'separator', 'sep_tutor' , 'Integrasi TutorLMS')
					->set_classes('sejoli-with-help'),
				Field::make('association', 'tutor_course', 'Tutor LMS Course')
					->set_types([
						[
							'type'      => 'post',
							'post_type' => sjt_get_course_post_type(),
						]
					])
					->set_max( 1 )
					->set_help_text(__('Course yang akan digunakan pada pembelian produk ini', 'sejoli')),
				]
			];
		return $fields;
	}
	
	public function _filter_format_harga_kursus($price){
		if (function_exists('sejolisa_price_format')) {
			$price = sejolisa_price_format($price);
		} else {
			$price = 'Rp. ' . number_format(intval($price), 0, ',', '.');
		}
		$price = sprintf('<span class="price">%s</span>', trim($price) );
		return $price;
	} 

	public function _filter_format_harga_kursus_strike( $price, $sejoliProductID ){
		preg_match( "#\d+#is", get_post_meta( $sejoliProductID, '_price_strike', true ), $match  );
		if ( isset( $match[0] ) ) {
			$price = intval( $match[0] );
			// $price = intval( get_post_meta( $sejoliProductID, '_price_strike', true ) );
			if ($price > 0) {
				if (function_exists('sejolisa_price_format')) {
					$price = sejolisa_price_format($price);
				} else {
					$price = 'Rp. ' . number_format(intval($price), 0, ',', '.');
				}
				$price = sprintf('<span class="price">%s</span>', trim($price) );
				return $price;
			}
		}

		return ;
	} 


	public function get_sejoli_product( $post_id ) {
		if ($post_id > 0) {
			$post = get_post( $post_id, 'ARRAY_A' );

			$post['price'] = sejolisa_price_format( get_post_meta( $post_id, '_price', true ) );
			$post['permalink'] = home_url( '/product/' . $post['post_name'] . '/' );
			return $post;
		}
		return false;
	}

	public function get_sejoli_product_by_course($courseID) {
		$post_id = intval( get_post_meta( $courseID, '_sejoli_product_id', true ) );
		if ($post_id > 0) {
			return $this->get_sejoli_product($post_id);
		}
		return false;
	}

	public function _filter_ui_loop_course_price($template) {
		if( $this->is_course_purchasable() ) {
			if ( !tutils()->is_course_added_to_cart(get_the_ID()) && !tutils()->is_enrolled(get_the_ID()) ) {
				$sejoli_product = $this->get_sejoli_product_by_course( get_the_ID() );

				if ( isset($sejoli_product['ID']) && $sejoli_product['ID'] > 0) {
					$enroll_btn 	= '<div  class="tutor-loop-cart-btn-wrap"><a href="'. $sejoli_product['permalink']. '">'.__('Buy', 'tutor'). '</a></div>';
					$price_html 	= get_post_meta( get_the_ID(), '_sejoli_product_price_formatted', true);

					$price_strike       = apply_filters( 'sejoli_tutor/price_strike', 0, $sejoli_product['ID'] );
					$price_strike_html 	= '<span class="strike-price">'. $price_strike .'</span>';

					if ( !empty( $price_strike ) ) {
						$price_html     = sprintf('<span class="active-price">%s</span> <s class="active-strike">%s</s>', $price_html, $price_strike_html );
					}

					ob_start();
					echo '<div class="tutor-course-loop-price">';
					echo '<div class="price"> ' . $price_html . $enroll_btn . ' </div>';
					echo '</div>';
					$template = ob_get_clean();
				}
			} 	
		}
		
		return $template;
	}

	public function _filter_ui_single_add_to_cart($content) {
		global $wp_query;

		if( $this->is_course_purchasable() ) {
			if ( !tutils()->is_course_added_to_cart(get_the_ID()) && !tutils()->is_enrolled(get_the_ID()) ) {
				$sejoli_product = $this->get_sejoli_product_by_course( get_the_ID() );
				if ($sejoli_product !== false && $sejoli_product['ID'] > 0) {

					$is_administrator = current_user_can('administrator');
					$is_instructor = tutor_utils()->is_instructor_of_this_course();

					$content = '<div class="tutor-course-purchase-box">
					';
					$content 	.= '<div class="tutor-cart-btn-wrap"><a href="'. $sejoli_product['permalink']. '" class="tutor-btn sejoli-tutor-course-purchase-btn"><span>'.__('Buy', 'tutor'). '</span></a></div>';
					
					if ( $wp_query->query['post_type'] !== 'lesson') {
						$lesson_url = tutor_utils()->get_course_first_lesson( get_the_ID() );
            			$completed_lessons = tutor_utils()->get_completed_lesson_count_by_course();

						if(($is_administrator || $is_instructor) && $lesson_url) {
							if(($is_administrator || $is_instructor) && $lesson_url) {
								$content 	.= '<div class="tutor-enroll-btn-wrap"><a href="'. $lesson_url. '" class="tutor-btn sejoli-tutor-enroll-btn">'.__('Enroll', 'tutor'). '</a></div>';
							}
						}
					}
					
					$content .= '</div>';
				}
			}
		}
		return $content;
	}
}


