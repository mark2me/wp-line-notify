<?php
/**
 * Plugin Name: WordPress LINE Notify
 * Plugin URI:  https://github.com/mark2me/wp-line-notify
 * Description: This plugin can send a alert message by LINE Notify
 * Version:     0.1.3
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me
 * License:     GPLv2
 * Text Domain: wp-line-notify
 * Domain Path: /languages
 */

define( 'SIG_LINE_NOTIFY_PLUGIN_NAME', 'wp-line-notify' );
define( 'SIG_LINE_NOTIFY_API_URL', 'https://notify-api.line.me/api/notify' );
define( 'SIG_LINE_NOTIFY_OPTIONS', '_sig_line_notify_setting' );

load_plugin_textdomain( SIG_LINE_NOTIFY_PLUGIN_NAME , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

$lineNotify = new sig_line_notify();

class sig_line_notify{

    private $version = '';
    private $langs = '';
    private $plugin_name = '';

    function __construct()
    {
        $data = get_file_data(
            __FILE__,
            array('ver' => 'Version', 'langs' => 'Domain Path')
        );
        $this->version = $data['ver'];
        $this->langs = $data['langs'];
        $this->plugin_name = SIG_LINE_NOTIFY_PLUGIN_NAME;
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

        if( isset($this->options['wpcf7']) && $this->options['wpcf7'] == 1 ){
            add_action("wpcf7_before_send_mail", array($this, "new_wpcf7_message"));
        }

        if( isset($this->options['new_post']) && count($this->options['new_post']) > 0 ){
            add_action('wp_insert_post',array($this,'new_post_alert'),10,3);
        }
    }

    function add_option_menu(){
        add_options_page(
            __('Line Notify Setting', $this->plugin_name),
            __('WP Line Notify', $this->plugin_name),
            'administrator',
            'sig-'.$this->plugin_name,
            array($this, 'html_settings_page')
        );

        add_action( 'admin_init', array($this,'register_option_var') );
    }

    function plugin_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=sig-'.$this->plugin_name.'">'.__( 'Settings', $this->plugin_name ).'</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    function register_option_var() {
        register_setting( 'line-notify-option', SIG_LINE_NOTIFY_OPTIONS );
    }

    function html_settings_page() {

        if (isset($_POST['text_line_notify']) && $_POST['text_line_notify'] !=='' && check_admin_referer('test_button_clicked')) {
            $send = $this->line_send( esc_attr($_POST['text_line_notify']) );
            $test_send = ($send) ? '<div class="notice notice-success is-dismissible"><p>'. __( 'Send test', $this->plugin_name ) .'</p></div>' : '<div class="notice notice-error is-dismissible"><p>'. __( 'Error on send LINE Notify.',$this->plugin_name ) .'</p></div>';
        }

        require_once plugin_dir_path( __FILE__ ) . 'inc/page_setup.php';
    }


    /*
        Send message
    */
    function line_send($text) {

        if (empty($text)) return false;

        $request_params = array(
            "headers" => "Authorization: Bearer {$this->options['token']}",
            "body"    => array(
                "message" => "\n {$text}"
            )
        );

        $response = wp_remote_post(SIG_LINE_NOTIFY_API_URL, $request_params );
        $code = wp_remote_retrieve_response_code( $response );

        if($code=='200'){
            return true;
        }else{
            return false;
        }

    }

    function new_comments_alert( $comment_ID, $comment_approved ) {

    	if( isset($this->options['comments']) && $this->options['comments'] == 1 ){
        	$comment = get_comment( $comment_ID );
        	$message = __("You have a new comment.\n" , $this->plugin_name ) . $comment->comment_content;
    		$this->line_send( $message );
    	}
    }

    function new_woocommerce_order_alert( $order_get_id ) {

    	if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 ){

            $order = wc_get_order( $order_get_id );
            $order_data = $order->get_data();


        	$message = __( "You have a new order." , $this->plugin_name );

            if(isset($order_data['line_items']) && count($order_data['line_items'])>0){
                foreach($order_data['line_items'] as $item){
                    if( isset($item['name']) && isset($item['quantity']) ){
                        $message .= "\n {$item['name']} x {$item['quantity']}";
                    }
                }
            }

        	if(isset($order_data['total'])) $message .= "\n".__( " total :" , $this->plugin_name ) . $order_data['total'];

    		$this->line_send( $message );
    	}

    }

    function new_user_register_alert( $user_id ) {

        if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            $message = __( "You have a new user register." , $this->plugin_name );

            $user_info = get_userdata($user_id);
            $message .= __( " Username: " , $this->plugin_name ) . $user_info->user_login;
            $this->line_send( $message );
        }
    }

    function new_wpcf7_message($cf7) {

        $wpcf = WPCF7_ContactForm::get_current();

        $message = __( "You have a new contact message." , $this->plugin_name );
        $this->line_send( $message . serialize($wpcf));
    }

    function new_post_alert($post_id, $post, $update){


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
                        $message .= __( " add a pending post." , $this->plugin_name );
                    }else{
                        $message .= __( " add a new post." , $this->plugin_name );
                    }

                    $message .= " $post_title $post_link" ;
                    $this->line_send( $message );
                }
            }
        }

    }

}