<?php
/**
 * LP_Question_True_Or_False
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 * @extends LP_Question
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question_True_Or_False' ) ) {

	/**
	 * Class LP_Question_True_Or_False
	 */
	class LP_Question_True_Or_False extends LP_Question {
		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'true_or_false';

		/**
		 * LP_Question_True_Or_False constructor.
		 *
		 * @param null $the_question
		 * @param null $args
		 *
		 * @throws Exception
		 */
		public function __construct( $the_question = null, $args = null ) {
			parent::__construct( $the_question, $args );
		}

		/**
		 * Get true or false default answers.
		 *
		 * @return array
		 */
		public function get_default_answers() {
			$answers = array(
				array(
					'question_answer_id' => - 1,
					'is_true'            => 'yes',
					'value'              => 'true',
					'title'              => __( 'True', 'learnpress' ),
				),
				array(
					'question_answer_id' => - 2,
					'is_true'            => 'no',
					'value'              => 'false',
					'title'              => __( 'False', 'learnpress' ),
				),
			);

			return $answers;
		}

		/**
		 * Check user answer.
		 *
		 * @param null $user_answer
		 *
		 * @return array
		 */
		public function check( $user_answer = null ) {
			$return  = parent::check();
			$answers = $this->get_answers();

			if ( $answers ) {
				foreach ( $answers as $key => $option ) {
					if ( ( $option['is_true'] == 'yes' ) && ( $option['value'] == $user_answer ) ) {
						$return['correct'] = true;
						$return['mark']    = floatval( $this->get_mark() );
						break;
					}
				}
			}

			return $return;
		}
	}
}
