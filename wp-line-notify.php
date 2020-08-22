<?php
/**
 * Plugin Name: WordPress LINE Notify
 * Plugin URI:  https://github.com/mark2me/wp-line-notify
 * Description: This plugin can send a alert message by LINE Notify
 * Version:     1.1.2
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me
 * License:     GPLv2
 * Text Domain: wp-line-notify
 * Domain Path: /languages
 */

define( 'SIG_LINE_NOTIFY_PLUGIN_NAME', 'wp-line-notify' );
define( 'SIG_LINE_NOTIFY_API_URL', 'https://notify-api.line.me/api/' );
define( 'SIG_LINE_NOTIFY_OPTIONS', '_sig_line_notify_setting' );
define( 'SIG_LINE_NOTIFY_DIR', dirname(__FILE__) );

load_plugin_textdomain( SIG_LINE_NOTIFY_PLUGIN_NAME , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

require_once SIG_LINE_NOTIFY_DIR . '/includes/woo-form-template.php';

$lineNotify = new sig_line_notify();

class sig_line_notify{

    private $version = '';
    private $langs = '';
    private $plugin_name = '';
    private $token_status = array();
    private $revoke_url = 'wp-admin/admin-ajax.php?action=sig_line_notify_revoke';

    public function __construct()
    {
        $data = get_file_data(
            __FILE__,
            array('ver' => 'Version', 'langs' => 'Domain Path')
        );
        $this->version = $data['ver'];
        $this->langs = $data['langs'];
        $this->options = get_option(SIG_LINE_NOTIFY_OPTIONS);


        // add menu
        add_action( 'admin_menu', array($this,'add_option_menu') );

        add_filter("plugin_action_links_".plugin_basename(__FILE__) ,array($this, 'plugin_settings_link') );

        // add_action
        if( isset($this->options['comments']) && $this->options['comments'] == 1 ){
            add_action( 'comment_post' , array($this, 'new_comments_alert') , 10 ,2  );
        }

        if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 ){
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if(is_plugin_active( 'woocommerce/woocommerce.php' )) {
                add_action( 'woocommerce_checkout_update_order_meta', array($this,'new_woocommerce_order_alert') , 10, 3 );
	        }
	    }

	    if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            add_action( 'user_register' , array($this,'new_user_register_alert') , 10 , 1 );
        }

        if( isset($this->options['wpcf7']) && is_array($this->options['wpcf7']) && count($this->options['wpcf7']) > 0 ){
            add_action("wpcf7_before_send_mail", array($this, "new_wpcf7_message"));
        }

        if( isset($this->options['new_post']) && count($this->options['new_post']) > 0 ){
            add_action('wp_insert_post',array($this,'new_post_alert'),10,3);
        }

        if( isset($this->options['token']) && $this->options['token'] !== ''){
            $response = $this->line_notify_status();
            $this->token_status = array(
                'code' => wp_remote_retrieve_response_code( $response ),
                'message' => wp_remote_retrieve_response_message( $response )
            );
        }else{
            $this->token_status = array(
                'code' => 0,
                'message' => ''
            );
        }

        // wp_ajax
        $this->revoke_url = home_url($this->revoke_url);
        add_action( 'wp_ajax_sig_line_notify_revoke', array($this, 'line_notify_revoke'));

    }

    public function add_option_menu(){
        add_options_page(
            __('Line Notify Setting', SIG_LINE_NOTIFY_PLUGIN_NAME),
            __('WP Line Notify', SIG_LINE_NOTIFY_PLUGIN_NAME),
            'administrator',
            'sig-'.SIG_LINE_NOTIFY_PLUGIN_NAME,
            array($this, 'html_settings_page')
        );

        add_action( 'admin_init', array($this,'register_option_var') );
    }

    public function plugin_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=sig-'.SIG_LINE_NOTIFY_PLUGIN_NAME.'">'.__( 'Settings', SIG_LINE_NOTIFY_PLUGIN_NAME ).'</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function register_option_var() {
        register_setting( 'line-notify-option', SIG_LINE_NOTIFY_OPTIONS );
    }

    public function html_settings_page() {

        if (isset($_POST['text_line_notify']) && $_POST['text_line_notify'] !=='' && check_admin_referer('test_button_clicked')) {
            $send = $this->line_send( esc_attr($_POST['text_line_notify']) );
            $test_send = ($send) ? '<div class="notice notice-success is-dismissible"><p>'. __( 'Send test ok!', SIG_LINE_NOTIFY_PLUGIN_NAME ) .'</p></div>' : '<div class="notice notice-error is-dismissible"><p>'. __( 'Error on send LINE Notify.',SIG_LINE_NOTIFY_PLUGIN_NAME ) .'</p></div>';
        }

        $woo_form = new WP_LINE_NOTIFY_wooTemplate();
        require_once SIG_LINE_NOTIFY_DIR . '/includes/page-setup.php';

    }

    public function new_comments_alert( $comment_ID, $comment_approved ) {

    	if( isset($this->options['comments']) && $this->options['comments'] == 1 ){
        	$comment = get_comment( $comment_ID );
        	$message = __("You have a new comment.\n" , SIG_LINE_NOTIFY_PLUGIN_NAME ) . $comment->comment_content;
    		$this->line_send( $message );
    	}
    }

    public function new_woocommerce_order_alert( $order_get_id ) {

    	if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 ){

            $order = wc_get_order( $order_get_id );
            $order_data = $order->get_data();

            if( isset($this->options['woocommerce_tpl']) && !empty($this->options['woocommerce_tpl']) ){
            	$message = $this->options['woocommerce_tpl'];
            }else{
                $woo_form = new WP_LINE_NOTIFY_wooTemplate();
                $message = $woo_form->form();
            }

            $total = (isset($order_data['total'])) ? $order_data['total']: '';

            $order_product = '';
            if(isset($order_data['line_items']) && count($order_data['line_items'])>0){
                foreach($order_data['line_items'] as $item){
                    if( isset($item['name']) && isset($item['quantity']) ){
                        $order_product .= "\n {$item['name']} x {$item['quantity']}";
                    }
                }
            }

            $order_name = (isset($order_data['billing']['first_name']) && isset($order_data['billing']['last_name'])) ? ($order_data['billing']['last_name'].$order_data['billing']['first_name']) : '-';

            $shipping_name = (isset($order_data['shipping']['first_name']) && isset($order_data['shipping']['last_name'])) ? ($order_data['shipping']['last_name'].$order_data['shipping']['first_name']) : '-';

            $payment_method = (isset($order_data['payment_method_title'])) ? $order_data['payment_method_title'] :'-';

            $order_date = (isset($order_data['date_created'])) ? $order_data['date_created']->date('Y-m-d') :'';
            $order_time = (isset($order_data['date_created'])) ? $order_data['date_created']->date('H:i:s') :'';

            $text = array(
                '[total]' => $total,
                '[order-product]' => $order_product,
                '[order-name]' => $order_name,
                '[shipping-name]' => $shipping_name,
                '[payment-method]' => $payment_method,
                '[order-date]' => $order_date,
                '[order-time]' => $order_time
            );
            $message = str_ireplace(  array_keys($text),  $text,  $message );
    		$this->line_send( $message );
    	}

    }

    public function new_user_register_alert( $user_id ) {

        if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            $message = __( "You have a new user register." , SIG_LINE_NOTIFY_PLUGIN_NAME );

            $user_info = get_userdata($user_id);
            $message .= __( " Username: " , SIG_LINE_NOTIFY_PLUGIN_NAME ) . $user_info->user_login;
            $this->line_send( $message );
        }
    }

    public function new_wpcf7_message($cf7) {

        $contact_form = WPCF7_ContactForm::get_current();
        $wpcf7_id = $contact_form -> id;

        if( !empty($wpcf7_id) && array_key_exists( $wpcf7_id , $this->options['wpcf7']) ) {

            $submission = WPCF7_Submission::get_instance();
            $posted_data = $submission->get_posted_data();

            $message = __( "You have a new contact message." , SIG_LINE_NOTIFY_PLUGIN_NAME );

            if(isset($posted_data['your-name'])) {
                $message .= __( "\n from:" , SIG_LINE_NOTIFY_PLUGIN_NAME ) . $posted_data['your-name'];
            }

            if(isset($posted_data['your-email'])) {
                $message .= __( "\n email:" , SIG_LINE_NOTIFY_PLUGIN_NAME ) . $posted_data['your-email'];
            }

            if(isset($posted_data['your-message'])) {
                $message .= __( "\n message:" , SIG_LINE_NOTIFY_PLUGIN_NAME ) . $posted_data['your-message'];
            }

            $this->line_send( $message );
        }

    }

    public function new_post_alert($post_id, $post, $update){


        if ( wp_is_post_revision( $post_id ) ) return;

        if( isset($this->options['new_post']) && count($this->options['new_post']) > 0 ){
            foreach($this->options['new_post'] as $name => $sel){
                $allow_role[] = $name;
            }
        }else{
            return;
        }

        if( is_object($post) && ($post->post_status == 'publish' || $post->post_status == 'pending') ){

            if($post->post_type !== 'post') return;

            $post_title = $post->post_title;
            $post_link  = $post->guid;
            $post_type  = $post->post_type;
            $uid        = $post->post_author;

            $user = get_userdata($uid);

            if(is_object($user)){

                $user_name = $user->display_name;
                $role = (array)$user->roles;
                $role_name = $role[0];

                if(in_array($role_name,$allow_role)){

                    $message = $user_name;

                    if( $post->post_status == 'pending' ){
                        $message .= __( " add a pending post." , SIG_LINE_NOTIFY_PLUGIN_NAME );
                    }else{
                        $message .= __( " add a new post." , SIG_LINE_NOTIFY_PLUGIN_NAME );
                    }

                    $message .= " $post_title $post_link" ;
                    $this->line_send( $message );
                }
            }
        }

    }

    private function line_send($text) {

        if (empty($text)) return false;

        $request_params = array(
            "headers" => "Authorization: Bearer {$this->options['token']}",
            "body"    => array(
                "message" => "\n {$text}"
            )
        );

        $response = wp_remote_post(SIG_LINE_NOTIFY_API_URL.'notify', $request_params );
        $code = wp_remote_retrieve_response_code( $response );
        $message = wp_remote_retrieve_response_message( $response );

        if($code=='200'){
            return true;
        }else{
            return false;
        }

    }

    private function line_notify_status(){
        $request_params = array(
            "headers" => "Authorization: Bearer {$this->options['token']}"
        );
        $response = wp_remote_get(SIG_LINE_NOTIFY_API_URL.'status', $request_params );
        return $response;
    }

    public function line_notify_revoke(){
        $request_params = array(
            "headers" => "Authorization: Bearer {$this->options['token']}"
        );
        $response = wp_remote_post(SIG_LINE_NOTIFY_API_URL.'revoke', $request_params );
        $code = wp_remote_retrieve_response_code( $response );
        $message = wp_remote_retrieve_response_message( $response );

        echo json_encode(array(
            'rs' => ($code==200) ? true : false,
            'message' => $message
        ));
        die();
    }
}
