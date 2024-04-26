<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 *    For Elementor Pro
 *   https://developers.elementor.com/forms-api/custom-form-action/
 */

class Line_Notify_After_Submit_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    private $token;

    public function __construct( $token = [] ) {

        $this->token = $token;
	}

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'Line notify';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return __( 'Line notify', 'wp-line-notify' );
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {

        if( !empty($this->token) ){
    		$settings = $record->get( 'form_settings' );

    		$message = ( !empty( $settings['line_notify_prompt_text'] ) ) ? $settings['line_notify_prompt_text'] : __( 'You have a message from the Elementor Form.', 'wp-line-notify' );

    		$form_data = $record->get( 'fields' );

    		// Normalize the Form Data
    		foreach ( $form_data as $id => $field ) {
                $title = (!empty($field['title'])) ? $field['title'] : $id;
                $valeu = (!empty($field['value'])) ? $field['value'] : '';
    			$message .= "\n[{$title}] {$valeu}";
    		}

            $sender = new WpLineNotify();
            $sender->send_msg( $this->token, $message );
        }
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_line_notify',
			[
				'label' => __( 'Line notify alert', 'wp-line-notify' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'line_notify_prompt_text',
			[
				'label' => __( 'Prompt text', 'wp-line-notify' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'placeholder' => '',
				'description' => __( 'The reminder text is displayed at the beginning of the sent message.', 'wp-line-notify' ),
			]
		);

		$widget->end_controls_section();
	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['line_notify_prompt_text']
		);
	}
}