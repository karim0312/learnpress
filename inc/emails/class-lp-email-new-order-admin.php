<?php
/**
 * LP_Email_New_Order_Admin.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Admin' ) ) {

	/**
	 * Class LP_Email_New_Order_Admin
	 */
	class LP_Email_New_Order_Admin extends LP_Email_Type_Order {
		/**
		 * LP_Email_New_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-admin';
			$this->title       = __( 'Admin', 'learnpress' );
			$this->description = __( 'Send email to admin when new order is placed', 'learnpress' );

			$this->default_subject = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'New user order', 'learnpress' );

			$this->recipients = get_option( 'admin_email' );
			$this->recipient  = LP()->settings->get( 'emails_' . $this->id . '.recipients', $this->recipients );

			parent::__construct();
		}

		/**
		 * Trigger email.
		 *
		 * @param int $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {
			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$this->get_object();
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}

	}
}

return new LP_Email_New_Order_Admin();