<?php
/**
 * CFF_New_User.
 *
 * @since 2.18
 */
namespace CustomFacebookFeed\Admin;
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CFF_New_User extends CFF_Notifications {

	/**
	 * Source of notifications content.
	 *
	 * @since 2.18
	 *
	 * @var string
	 */
	const SOURCE_URL = 'http://plugin.smashballoon.com/newuser.json';

	/**
	 * @var string
	 */
	const OPTION_NAME = 'cff_newuser_notifications';

	/**
	 * Register hooks.
	 *
	 * @since 2.18
	 */
	public function hooks() {
		add_action( 'admin_notices', array( $this, 'output' ), 8 );

		add_action( 'admin_init', array( $this, 'dismiss' ) );
	}

	public function option_name() {
		return self::OPTION_NAME;
	}

	public function source_url() {
		return self::SOURCE_URL;
	}

	/**
	 * Verify notification data before it is saved.
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 *
	 * @since 2.18
	 */
	public function verify( $notifications ) {
		$data = array();
		
		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return $data;
		}

		$option = $this->get_option();

		foreach ( $notifications as $key => $notification ) {

			// The message should never be empty, if they are, ignore.
			if ( empty( $notification['content'] ) ) {
				continue;
			}

			// Ignore if notification has already been dismissed.
			if ( ! empty( $option['dismissed'] ) && in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				continue;
			}

			$data[ $key ] = $notification;
		}

		return $data;
	}

	/**
	 * Verify saved notification data for active notifications.
	 *
	 * @since 2.18
	 *
	 * @param array $notifications Array of notifications items to verify.
	 *
	 * @return array
	 */
	public function verify_active( $notifications ) {
		if ( ! is_array( $notifications ) || empty( $notifications ) ) {
			return array();
		}

		$cff_statuses_option = get_option( 'cff_statuses', array() );
		$current_time = cff_get_current_time();

		// rating notice logic
		$cff_rating_notice_option = get_option( 'cff_rating_notice', false );
		$cff_rating_notice_waiting = get_transient( 'custom_facebook_rating_notice_waiting' );
		$should_show_rating_notice = ($cff_rating_notice_waiting !== 'waiting' && $cff_rating_notice_option !== 'dismissed');

		// new user discount logic
		$in_new_user_month_range = true;
		$should_show_new_user_discount = false;
		$has_been_one_month_since_rating_dismissal = isset( $cff_statuses_option['rating_notice_dismissed'] ) ? ((int)$cff_statuses_option['rating_notice_dismissed'] + ((int)$notifications['review']['wait'] * DAY_IN_SECONDS)) < $current_time + 1: true;

		if ( isset( $cff_statuses_option['first_install'] ) && $cff_statuses_option['first_install'] === 'from_update' ) {
			global $current_user;
			$user_id = $current_user->ID;
			$ignore_new_user_sale_notice_meta = get_user_meta( $user_id, 'cff_ignore_new_user_sale_notice' );
			$ignore_new_user_sale_notice_meta = isset( $ignore_new_user_sale_notice_meta[0] ) ? $ignore_new_user_sale_notice_meta[0] : '';
			if ( $ignore_new_user_sale_notice_meta !== 'always' ) {
				$should_show_new_user_discount = true;
			}
		} elseif ( $in_new_user_month_range && $has_been_one_month_since_rating_dismissal && $cff_rating_notice_waiting !== 'waiting' ) {
			global $current_user;
			$user_id = $current_user->ID;
			$ignore_new_user_sale_notice_meta = get_user_meta( $user_id, 'cff_ignore_new_user_sale_notice' );
			$ignore_new_user_sale_notice_meta = isset( $ignore_new_user_sale_notice_meta[0] ) ? $ignore_new_user_sale_notice_meta[0] : '';

			if ( $ignore_new_user_sale_notice_meta !== 'always'
			     && isset( $cff_statuses_option['first_install'] )
			     && $current_time > (int)$cff_statuses_option['first_install'] + ((int)$notifications['discount']['wait'] * DAY_IN_SECONDS) ) {
				$should_show_new_user_discount = true;
			}
		}

		if ( isset( $notifications['review'] ) && $should_show_rating_notice ) {
		    return array( $notifications['review'] );
        } elseif ( isset( $notifications['discount'] ) && $should_show_new_user_discount ) {
			return array( $notifications['discount'] );
		}

		return array();
	}

	/**
	 * Get notification data.
	 *
	 * @since 2.18
	 *
	 * @return array
	 */
	public function get() {
		if ( ! $this->has_access() ) {
			return array();
		}

		$option = $this->get_option();

		// Only update if does not exist.
		if ( empty( $option['update'] ) ) {
			$this->update();
		}

		$events = ! empty( $option['events'] ) ? $this->verify_active( $option['events'] ) : array();
		$feed   = ! empty( $option['feed'] ) ? $this->verify_active( $option['feed'] ) : array();

		return array_merge( $events, $feed );
	}

	/**
	 * Add a manual notification event.
	 *
	 * @since 2.18
	 *
	 * @param array $notification Notification data.
	 */
	public function add( $notification ) {
		if ( empty( $notification['id'] ) ) {
			return;
		}

		$option = $this->get_option();

		if ( in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			return;
		}

		foreach ( $option['events'] as $item ) {
			if ( $item['id'] === $notification['id'] ) {
				return;
			}
		}

		$notification = $this->verify( array( $notification ) );

		update_option(
			$this->option_name(),
			array(
				'update'    => $option['update'],
				'feed'      => $option['feed'],
				'events'    => array_merge( $notification, $option['events'] ),
				'dismissed' => $option['dismissed'],
			)
		);
	}

	/**
	 * Update notification data from feed.
	 *
	 * @since 2.18
	 */
	public function update() {
		$feed   = $this->fetch_feed();
		$option = $this->get_option();

		update_option(
			$this->option_name(),
			array(
				'update'    => time(),
				'feed'      => $feed,
				'events'    => $option['events'],
				'dismissed' => $option['dismissed'],
			)
		);
	}

	/**
	 * Do not enqueue anything extra.
	 *
	 * @since 2.18
	 */
	public function enqueues() {

	}

	/**
	 * Output notifications on Form Overview admin area.
	 *
	 * @since 2.18
	 */
	public function output() {
	    // If the Instagram Feed plugin is active, notices only shown on CFF Settings pages
		if ( function_exists( 'sb_instagram_activate' )
            && ! function_exists( 'sb_instagram_feed_pro_init' ) ) {
			return;
		}

		$notifications = $this->get();

		if ( empty( $notifications ) ) {
			return;
		}

		// new user notices included in regular settings page notifications so this
        // checks to see if user is one of those pages
		if ( ! empty( $_GET['page'] )
		     && strpos( $_GET['page'], 'cff-' ) !== false ) {
			return;
		}

		$content_allowed_tags = array(
			'em'     => array(),
			'strong' => array(),
			'span'   => array(
				'style' => array(),
			),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		);
		$image_overlay = '';

		foreach ( $notifications as $notification ) {
			$type = sanitize_text_field( $notification['id'] );
			$img_src = CFF_PLUGIN_URL . 'admin/assets/img/' . sanitize_text_field( $notification['image'] );
			$content = '';
			if ( ! empty( $notification['content'] ) ) {
				$content = wp_kses( $this->replace_merge_fields( $notification['content'], $notification ), $content_allowed_tags );
			}
			$buttons = array();
			if ( ! empty( $notification['btns'] ) && is_array( $notification['btns'] ) ) {
				foreach ( $notification['btns'] as $btn_type => $btn ) {
					if ( ! is_array( $btn['url'] ) ) {
						$buttons[ $btn_type ]['url'] = $this->replace_merge_fields( $btn['url'], $notification );
					} elseif ( is_array( $btn['url'] ) ) {
						$buttons[ $btn_type ]['url'] = add_query_arg( $btn['url'] );
					}

					$buttons[ $btn_type ]['attr'] = '';
					if ( ! empty( $btn['attr'] ) ) {
						$buttons[ $btn_type ]['attr'] = ' target="_blank" rel="noopener noreferrer"';
					}

					$buttons[ $btn_type ]['class'] = '';
					if ( ! empty( $btn['class'] ) ) {
						$buttons[ $btn_type ]['class'] = ' ' . $btn['class'];
					}

					$buttons[ $btn_type ]['text'] = '';
					if ( ! empty( $btn['text'] ) ) {
						$buttons[ $btn_type ]['text'] = wp_kses( $btn['text'], $content_allowed_tags );
					}
				}
			}
			if ( isset( $notification['image_overlay'] ) ) {
				$image_overlay = '<div class="img-overlay">'. esc_html( $notification['image_overlay'] ).'</div>';
			}
		}
		?>

        <div class="cff_notice cff_<?php echo esc_attr( $type ); ?>_notice">
            <div class="cff_thumb">
                <img src="<?php echo esc_url( $img_src ); ?>" alt="notice">
				<?php echo $image_overlay; ?>
            </div>
            <div class="cff-notice-text">
                <p style="padding-top: 4px;"><?php echo $content; ?></p>
                <p class="links">
                <?php foreach ( $buttons as $button ) : ?>
                    <a class="<?php echo esc_attr( $button['class'] ); ?>" href="<?php echo esc_attr( $button['url'] ); ?>"<?php echo $button['attr']; ?>><?php echo $button['text']; ?></a>
                <?php endforeach; ?>
                </p>
            </div>
            <a class="cff_notice_close" href="<?php echo add_query_arg( array( 'cff_dismiss' => $type ) ); ?>"><i class="fa fa-close"></i></a>
        </div>
		<?php
	}

	/**
	 * Hide messages permanently or some can be dismissed temporarily
     *
     * @since 2.18
	 */
	public function dismiss() {
		global $current_user;
		$user_id = $current_user->ID;
		$cff_statuses_option = get_option( 'cff_statuses', array() );

		if ( isset( $_GET['cff_ignore_rating_notice_nag'] ) ) {
			if ( (int)$_GET['cff_ignore_rating_notice_nag'] === 1 ) {
				update_option( 'cff_rating_notice', 'dismissed', false );
				$cff_statuses_option['rating_notice_dismissed'] = cff_get_current_time();
				update_option( 'cff_statuses', $cff_statuses_option, false );

			} elseif ( $_GET['cff_ignore_rating_notice_nag'] === 'later' ) {
				set_transient( 'custom_facebook_rating_notice_waiting', 'waiting', 2 * WEEK_IN_SECONDS );
				update_option( 'cff_rating_notice', 'pending', false );
			}
		}

		if ( isset( $_GET['cff_ignore_new_user_sale_notice'] ) ) {
			$response = sanitize_text_field( $_GET['cff_ignore_new_user_sale_notice'] );
			if ( $response === 'always' ) {
				update_user_meta( $user_id, 'cff_ignore_new_user_sale_notice', 'always' );

				$current_month_number = (int)date('n', cff_get_current_time() );
				$not_early_in_the_year = ($current_month_number > 5);

				if ( $not_early_in_the_year ) {
					update_user_meta( $user_id, 'cff_ignore_bfcm_sale_notice', date( 'Y', cff_get_current_time() ) );
				}

			}
		}

		if ( isset( $_GET['cff_ignore_bfcm_sale_notice'] ) ) {
			$response = sanitize_text_field( $_GET['cff_ignore_bfcm_sale_notice'] );
			if ( $response === 'always' ) {
				update_user_meta( $user_id, 'cff_ignore_bfcm_sale_notice', 'always' );
			} elseif ( $response === date( 'Y', cff_get_current_time() ) ) {
				update_user_meta( $user_id, 'cff_ignore_bfcm_sale_notice', date( 'Y', cff_get_current_time() ) );
			}
			update_user_meta( $user_id, 'cff_ignore_new_user_sale_notice', 'always' );
		}

		if ( isset( $_GET['cff_dismiss'] ) ) {
			if ( $_GET['cff_dismiss'] === 'review' ) {
				update_option( 'cff_rating_notice', 'dismissed', false );
				$cff_statuses_option['rating_notice_dismissed'] = cff_get_current_time();
				update_option( 'cff_statuses', $cff_statuses_option, false );
			} elseif ( $_GET['cff_dismiss'] === 'discount' ) {
				update_user_meta( $user_id, 'cff_ignore_new_user_sale_notice', 'always' );

				$current_month_number = (int)date('n', cff_get_current_time() );
				$not_early_in_the_year = ($current_month_number > 5);

				if ( $not_early_in_the_year ) {
					update_user_meta( $user_id, 'cff_ignore_bfcm_sale_notice', date( 'Y', cff_get_current_time() ) );
				}
			}
			update_user_meta( $user_id, 'cff_ignore_new_user_sale_notice', 'always' );
		}
    }
}
