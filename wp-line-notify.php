<?php
/**
 * Plugin Name: WordPress LINE Notify
 * Description: This plugin can send a alert message by LINE Notify
 * Version:     1.3.1
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me/wp-line-notify
 * License:     GPLv2
 * Text Domain: wp-line-notify
 * Domain Path: /languages
 */

define( 'SIG_LINE_NOTIFY_API_URL', 'https://notify-api.line.me/api/' );
define( 'SIG_LINE_NOTIFY_OPTIONS', '_sig_line_notify_setting' );
define( 'SIG_LINE_NOTIFY_DIR', dirname(__FILE__) );

new sig_line_notify();

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

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        require_once( SIG_LINE_NOTIFY_DIR . '/includes/class-woo.php' );


        // actions
        add_action( 'plugins_loaded' , array($this, 'load_textdomain' ) );

        // add menu
        add_action( 'admin_menu' , array($this,'add_option_menu') );

        // add setting
        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__) , array($this, 'plugin_settings_link') );


        if( isset($this->options['publish_post']) && !empty($this->options['publish_post']) ){
            add_action( 'wp_insert_post' , array($this,'post_status_alert'), 10 , 3 );
        }

        if( isset($this->options['pending_post']) && !empty($this->options['pending_post']) ){
            add_action( 'wp_insert_post' , array($this,'post_status_alert'), 10 , 3 );
        }

        if( isset($this->options['comments']) && $this->options['comments'] == 1 ){
            add_action( 'comment_post' , array($this, 'new_comments_alert') , 10 , 2 );
        }

	    if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            add_action( 'user_register' , array($this,'new_user_register_alert') , 10 , 1 );
        }

        if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 && is_plugin_active( 'woocommerce/woocommerce.php' ) ){
                add_action( 'woocommerce_checkout_update_order_meta', array($this,'new_woocommerce_order_alert') , 15, 3 );
	    }

        if( isset($this->options['wpcf7']) && is_array($this->options['wpcf7']) && count($this->options['wpcf7']) > 0 && is_plugin_active('contact-form-7/wp-contact-form-7.php') ){
            add_action("wpcf7_before_send_mail", array($this, "new_wpcf7_message"));
        }

        if( isset($this->options['elementor_form']) && $this->options['elementor_form'] == 1 && is_plugin_active( 'elementor-pro/elementor-pro.php' ) ){
            add_action( 'elementor_pro/init', function() {
                require_once( SIG_LINE_NOTIFY_DIR . '/includes/class-elementor.php' );
            	$after_submit_action = new Ele_After_Submit_Action();
            	\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $after_submit_action->get_name(), $after_submit_action );
            });
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

    public function load_textdomain(){
        load_plugin_textdomain( 'wp-line-notify' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function add_option_menu(){
        add_options_page(
            __( 'Line Notify Setting' , 'wp-line-notify'),
            __( 'WP Line Notify' , 'wp-line-notify'),
            'administrator',
            'sig-wp-line-notify',
            array($this, 'html_settings_page')
        );

        add_action( 'admin_init', array($this,'register_option_var') );
    }

    public function plugin_settings_link( $actions) {
        $settings_link = array(
            '<a href="'. admin_url('options-general.php?page=sig-wp-line-notify'). '">'. esc_html__( 'Settings' , 'wp-line-notify' ).'</a>'
        );
        $actions = array_merge( $actions, $settings_link );
        return $actions;
    }

    public function register_option_var() {
        register_setting( 'line-notify-option', SIG_LINE_NOTIFY_OPTIONS );
    }

    public function html_settings_page() {

        if (isset($_POST['text_line_notify']) && $_POST['text_line_notify'] !=='' && check_admin_referer('test_button_clicked')) {
            $rs_send = $this->send_msg( esc_attr($_POST['text_line_notify']) );
            if ( $rs_send === true ) {
                $test_send = '<div class="notice notice-success is-dismissible"><p>'. __( 'Send test ok!' , 'wp-line-notify' ) .'</p></div>';
            } else {
                $test_send = sprintf('<div class="notice notice-error is-dismissible"><p>%1s Error: %2s</p></div>'
                    , __( 'Error on send LINE Notify.' , 'wp-line-notify' ),
                    $rs_send
                );
            }
        }


        require_once SIG_LINE_NOTIFY_DIR . '/includes/page-setup.php';

    }


    public function post_status_alert($post_id, $post, $update){

        $status = [
            'publish' => __( 'publish a post' , 'wp-line-notify' ),
            'pending' => __( 'pending a post' , 'wp-line-notify' )
        ];

        if( !isset( $status[$post->post_status] ) ) return;

        if( $post->post_type !== 'post' ) return;

        $user = get_userdata( $post->post_author );
        if( is_object($user) ) {

            $role = (array)$user->roles;
            $role_name = $role[0];

            if( isset($this->options[$post->post_status.'_post'][$role_name]) ){
                $message = "{$user->display_name} {$status[$post->post_status]} {$post->post_title} {$post->guid}";
                $this->send_msg( $message );
            }
        }
        return;

    }

    public function new_comments_alert( $comment_ID, $comment_approved ) {

    	$comment = get_comment( $comment_ID );
    	$message = __( 'You have a new comment.' , 'wp-line-notify' ) . "\n" . $comment->comment_content;
		$this->send_msg( $message );

    }

    public function new_user_register_alert( $user_id ) {

        $message = __( 'You have a new user register.' , 'wp-line-notify' );

        $user_info = get_userdata($user_id);
        $message .= __( 'Username:' , 'wp-line-notify' ) . $user_info->user_login;
        $this->send_msg( $message );
    }

    public function new_woocommerce_order_alert( $order_get_id ) {

        $order = wc_get_order( $order_get_id );
        $order_data = $order->get_data();


        if( isset($this->options['woocommerce_tpl']) && !empty($this->options['woocommerce_tpl']) ){
        	$message = $this->options['woocommerce_tpl'];
        }else{

            $message = WP_LINE_NOTIFY_WOO::form();
        }

        //order
        $order_product = '';
        if(isset($order_data['line_items']) && count($order_data['line_items'])>0){
            foreach($order_data['line_items'] as $item){
                if( isset($item['name']) && isset($item['quantity']) ){
                    $product = $order->get_product_from_item( $item );
                    $sku = $product->get_sku();
                    if( !empty($sku) ){
                        $order_product .= "\n {$item['name']} [" .  $product->get_sku() . "] x {$item['quantity']}";
                    }else{
                        $order_product .= "\n {$item['name']} x {$item['quantity']}";
                    }

                }
            }
        }

        $order_name = (isset($order_data['billing']['first_name']) && isset($order_data['billing']['last_name'])) ? ($order_data['billing']['last_name'].$order_data['billing']['first_name']) : '-';

        $shipping_name = (isset($order_data['shipping']['first_name']) && isset($order_data['shipping']['last_name'])) ? ($order_data['shipping']['last_name'].$order_data['shipping']['first_name']) : '';


        $text = array(
            '[order-name]'      => $order_name,
            '[shipping-name]'   => $shipping_name,
            '[order-product]'   => $order_product,
            '[total]'           => (isset($order_data['total'])) ? $order_data['total'] : '',
            '[payment-method]'  => (isset($order_data['payment_method_title'])) ? $order_data['payment_method_title'] : '',
            '[order-time]'      => (isset($order_data['date_created'])) ? $order_data['date_created']->date('Y-m-d H:i:s') : '',
            '[customer_note]'   => (isset($order_data['customer_note'])) ? $order_data['customer_note'] : '',
        );

        // Checkout Field Editor for WooCommerce
        if( class_exists('THWCFD_Utils') ) {

            $metas = get_post_meta( $order_data['id'] );

            // billing
            foreach( WP_LINE_NOTIFY_WOO::get_fields('billing') as $tag => $v ){
                $text["[{$tag}]"] = ( isset($metas['_'.$tag]) ) ? $metas['_'.$tag][0] : '';
            }

            // shipping
            foreach( WP_LINE_NOTIFY_WOO::get_fields('shipping') as $tag => $v ){
                $text["[{$tag}]"] = ( isset($metas['_'.$tag]) ) ? $metas['_'.$tag][0] : '';
            }

            // Additional Fields
            foreach( WP_LINE_NOTIFY_WOO::get_fields('additional') as $tag => $label ){
                if( $tag === 'order_comments' ){
                    $text["[{$tag}]"] = ( isset($order_data['customer_note']) ) ? $order_data['customer_note']:'';
                }else{
                    $text["[{$tag}]"] = ( isset($metas[$tag]) ) ? $metas[$tag][0] : '';
                }
            }

        }else{
            // billing
            foreach( WP_LINE_NOTIFY_WOO::init('billing') as $tag => $label ){
                $field = str_replace('billing_' ,'' , $tag);
                if( isset($order_data['billing'][$field]) ){
                    $text["[{$tag}]"] = $order_data['billing'][$field];
                }
            }

            // shipping
            foreach( WP_LINE_NOTIFY_WOO::init('shipping') as $tag => $label ){
                $field = str_replace('shipping_','',$tag);
                if( isset($order_data['shipping'][$field]) ){
                    $text["[{$tag}]"] = $order_data['shipping'][$field];
                }
            }
        }

        $message = str_ireplace(  array_keys($text),  $text,  $message );
		$this->send_msg( $message );

    }


    public function new_wpcf7_message($cf7) {

        $contact_form = WPCF7_ContactForm::get_current();
        $wpcf7_id = $contact_form -> id;

        if( !empty($wpcf7_id) && array_key_exists( $wpcf7_id , $this->options['wpcf7']) ) {

            $submission = WPCF7_Submission::get_instance();
            $posted_data = $submission->get_posted_data();

            $message = __( 'You have a new contact message.' , 'wp-line-notify' );

            if(isset($posted_data['your-name'])) {
                $message .= "\n". __( 'from:' , 'wp-line-notify' ) . $posted_data['your-name'];
            }

            if(isset($posted_data['your-email'])) {
                $message .= "\n ". __( 'email:' , 'wp-line-notify' ) . $posted_data['your-email'];
            }

            if(isset($posted_data['your-message'])) {
                $message .= "\n ". __( 'message:' , 'wp-line-notify' ) . $posted_data['your-message'];
            }

            $this->send_msg( $message );
        }

    }

    public function send_msg($text) {

        if ( empty($this->options['token']) ) return __( 'LINE Notify token is required!' , 'wp-line-notify' );

        if ( empty($text) ) return __( 'Plase write something !' , 'wp-line-notify' );

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
            return $message;
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
