<?php

/**
 * Class LP_Preview_Course
 *
 * Helper class for preview course/lesson/quiz
 *
 * @since 3.0.0
 */
class LP_Preview_Course {

	/**
	 * @var int
	 */
	protected static $_item_id = 0;

	/**
	 * @var int
	 */
	protected static $_preview_course = 0;

	/**
	 * Get a FAKE course for preview.
	 *
	 * @return LP_Course|mixed
	 */
	public static function get_preview_course() {

		if ( empty( self::$_preview_course ) ) {
			global $wpdb;

			$ids = self::get_preview_courses();

			if ( $ids === false ) {
				$title                 = __( 'Preview Course', 'learnpress' );
				self::$_preview_course = wp_insert_post(
					array(
						'post_author' => 0,
						'post_type'   => LP_COURSE_CPT,
						'post_title'  => $title,
						'post_status' => 'draft',
						'post_name'   => sanitize_title( $title ),
					)
				);

				update_post_meta( self::$_preview_course, '_lp_preview_course', 'yes' );

				LP_Object_Cache::set( 'preview-courses', array( self::$_preview_course ), 'learnpress' );
			} else {
				self::$_preview_course = $ids[0];
			}
		}

		return self::$_preview_course;
	}

	public static function exclude( $where ) {
		global $wpdb;

		if ( ! self::is_preview() ) {
			if ( $ids = self::get_preview_courses() ) {
				$format = array_fill( 0, sizeof( $ids ), '%d' );
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID NOT IN(" . join( ',', $format ) . ') ', $ids );
			}
		}

		return $where;
	}

	public static function is_preview() {
		if ( ! $post_id = LP_Request::get_int( 'lp-preview' ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( LP_Request::get_string( '_wpnonce' ), 'lp-preview' ) ) {
			return false;
		}

		if ( ! $post_item = get_post( $post_id ) ) {
			throw new Exception( __( 'Invalid preview item.', 'learnpress' ) );
		}

		return $post_item;
	}

	/**
	 * Setup preview environment.
	 */
	public static function setup_preview() {
		try {

			if ( ! $post_item = self::is_preview() ) {
				return false;
			}

			if ( ! in_array( $post_item->post_type, learn_press_course_get_support_item_types( true ) ) ) {
				throw new Exception( __( 'Invalid preview item.', 'learnpress' ) );
			}

			// Access forbidden
			if ( ! current_user_can( 'manage_options' ) ) {
				if ( $post_item->post_author != get_current_user_id() ) {
					throw new Exception( __( 'Access denied.', 'learnpress' ) );
				}
			}

			$post_id = $post_item->ID;

			if ( empty( $post_item->post_name ) ) {
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_name' => sanitize_title( $post_item->post_title ),
					)
				);

				wp_redirect( learn_press_get_current_url() );
				exit();
			}

			self::$_item_id = $post_id;

			$preview_course = self::get_preview_course();
			$post_course    = get_post( $preview_course );

			$post              = wp_cache_get( self::$_preview_course, 'posts' );
			$post->post_status = 'publish';
			wp_cache_set( self::$_preview_course, $post, 'posts' );

			/**
			 * Set FAKE url of preview course to request uri so WP will parse
			 * the request as a real course.
			 */
			$_SERVER['REQUEST_URI'] = self::build_course_url( $post_course, $post_item );

			// Should not redirect canonical to real course
			add_filter( 'redirect_canonical', '__return_false' );

			// Prevent 404 because the preview item is not inside a course
			add_filter( 'learn-press/query/404', '__return_false' );

			// Add custom class to body
			add_filter( 'body_class', array( __CLASS__, 'body_class' ) );

			// Edit button
			add_action( 'learn-press/before-course-item-content', array( __CLASS__, 'edit_button' ) );

			// learn_press_debug($_SERVER);die();

		} catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
			wp_redirect( get_home_url() );
			exit();
		}
	}

	public static function get_preview_courses() {
		if ( false === ( $ids = LP_Object_Cache::get( 'preview-courses' ) ) ) {
			global $wpdb;
			$query = $wpdb->prepare(
				"
				SELECT post_id
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE meta_key = %s AND meta_value = %s
			",
				'_lp_preview_course',
				'yes'
			);

			$ids = $wpdb->get_col( $query );
			LP_Object_Cache::set( 'preview-courses', $ids );
		}

		return $ids;
	}

	public static function edit_button() {
		learn_press_display_message( sprintf( __( 'You are in preview mode. Continue <a href="%s">editing</a>?', 'learnpress' ), get_edit_post_link( self::$_item_id ) ), 'error' );
	}

	public static function body_class( $classes ) {
		$classes[] = 'lp-preview';

		return $classes;
	}

	/**
	 * Build a FAKE url of a course.
	 *
	 * @param WP_Post $post_course
	 * @param WP_Post $post_item
	 *
	 * @return string
	 */
	public static function build_course_url( $post_course, $post_item ) {
		$post_types         = get_post_types( '', 'objects' );
		$slug               = preg_replace( '!^/!', '', $post_types[ LP_COURSE_CPT ]->rewrite['slug'] );
		$custom_slug_lesson = sanitize_title_with_dashes( LP()->settings->get( 'lesson_slug' ) );
		$custom_slug_quiz   = sanitize_title_with_dashes( LP()->settings->get( 'quiz_slug' ) );

		if ( ! empty( $custom_slug_lesson ) ) {
			$post_types[ LP_LESSON_CPT ]->rewrite['slug'] = urldecode( $custom_slug_lesson );
		}

		if ( ! empty( $custom_slug_quiz ) ) {
			$post_types[ LP_QUIZ_CPT ]->rewrite['slug'] = urldecode( $custom_slug_quiz );
		}

		$request_uri = $_SERVER['REQUEST_URI'];
		$arr         = parse_url( $request_uri );

		return join(
			'/',
			array(
				untrailingslashit( $arr['path'] ),
				$slug,
				$post_course->post_name,
				$post_types[ $post_item->post_type ]->rewrite['slug'],
				$post_item->post_name,
			)
		);
	}

	public static function init() {
		// add_action( 'init', array( __CLASS__, 'setup_preview' ) );
		// add_filter( 'wp_count_posts', array( __CLASS__, 'reduce_counts' ), 10, 3 );
		// add_filter( 'posts_where_paged', array( __CLASS__, 'exclude' ) );

		add_action( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'wp_footer', array( __CLASS__, 'output_script' ), 1000 );
	}

	public static function output_script() {
		if ( learn_press_is_preview_course() ) {
			?>
			<script>
				jQuery(function ($) {

					var $elements = $('form, a');
					var previewNonce = '<?php echo LP_Request::get( 'preview' ); ?>';

					$elements.each(function () {
						var $element = $(this),
							link = $element.attr('href') || $element.attr('action') || '';

						if (link.match(/^http:\/\/localhost\/learnpress\/dev/) || !link.match(/^https?:\/\//)) {
							link = link.addQueryVar('preview', previewNonce);
						}

						if ($element.is('a')) {
							$element.attr('href', link)
						} else {
							$element.attr('src', link)
						}
					})

					$.ajaxSetup({
						beforeSend: function (a, b) {
							b.url = b.url.addQueryVar('preview', previewNonce)
						}
					});
				})

			</script>
			<?php
		}
	}

	public static function template_include( $template ) {

		if ( ! ( $preview_nonce = LP_Request::get( 'preview' ) ) || get_post_type() !== LP_COURSE_CPT ) {
			return $template;
		}

		if ( $preview_nonce && ! current_user_can( 'edit_lp_course' ) ) {
			wp_redirect( remove_query_arg( 'preview' ) );
			exit();
		}

		if ( wp_verify_nonce( $preview_nonce, 'preview-' . get_the_ID() ) ) {
			return $template;
		}

		if ( LP_Request::get( 'preview' ) === 'true' ) {
			$redirect_url = remove_query_arg( 'preview' );
			$redirect_url = add_query_arg( 'preview', wp_create_nonce( 'preview-' . get_the_ID() ), $redirect_url );
			wp_redirect( $redirect_url );
			exit();
		}

		return $template;
	}

	public static function reduce_counts( $counts, $type, $perm ) {
		if ( ( LP_COURSE_CPT === $type ) && ( $ids = self::get_preview_courses() ) ) {
			foreach ( $ids as $id ) {
				switch ( get_post_status( $id ) ) {
					case 'draft':
						$counts->draft -= 1;
						break;
					default:
						$counts->publish -= 1;
						break;
				}
			}
		}

		return $counts;

	}

}

LP_Preview_Course::init();
