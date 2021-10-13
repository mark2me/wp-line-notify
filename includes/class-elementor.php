<?php
/*
    For Elementor Pro
    https://developers.elementor.com/forms-api/custom-form-action/
*/

class Ele_After_Submit_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {
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

		$settings = $record->get( 'form_settings' );

		$message = ( !empty( $settings['line_notify_prompt_text'] ) ) ? $settings['line_notify_prompt_text'] : __( 'You have a message from the Elementor form.', 'wp-line-notify' );

		$form_data = $record->get( 'fields' );

		// Normalize the Form Data
		foreach ( $form_data as $id => $field ) {
            $title = (!empty($field['title'])) ? $field['title'] : $id;
            $valeu = (!empty($field['value'])) ? $field['value'] : '';
			$message .= "\n[{$title}] {$valeu}";
		}

        $send = new sig_line_notify();
        $send->send_msg( $message );
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