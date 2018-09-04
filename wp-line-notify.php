<?php
/**
 * Plugin Name: WordPress LINE Notify
 * Plugin URI:  https://github.com/mark2me/wp-line-notify
 * Description: This plugin can send a alert message by LINE Notify
 * Version:     0.1.1
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

        // add plugin menu
        add_action( 'admin_menu', array($this,'add_option_menu') );

        add_filter("plugin_action_links_".plugin_basename(__FILE__) ,array($this, 'plugin_settings_link') );

        if( isset($this->options['comments']) && $this->options['comments'] == 1 ){
            add_action( 'comment_post' , array($this, 'new_comments_alert') , 10 ,2  );
        }

        if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 ){
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if(is_plugin_active( 'woocommerce/woocommerce.php' )) {
                add_action( 'woocommerce_new_order', array($this,'new_woocommerce_order_alert') , 10, 3 );
	        }
	    }

	    if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            add_action( 'user_register' , array($this,'new_user_register_alert') , 10 , 1 );
        }

        if( isset($this->options['wpcf7']) && $this->options['wpcf7'] == 1 ){
            add_action("wpcf7_before_send_mail", array($this, "new_wpcf7_message"));
        }

     }

    function add_option_menu(){
        add_options_page(
            __('Line Notify Setting', $this->plugin_name),
            __('Line Notify', $this->plugin_name),
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

    ?>
        <div class="wrap">
            <h2><?php _e('Line Notify Setting',$this->plugin_name)?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('line-notify-option'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Line Notify Token:',$this->plugin_name)?></th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[token]" value="<?php echo esc_attr( $this->options['token'] ) ?>">
                            <p class="description">到 <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a> 申請發行存取權杖。 </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('comments:', $this->plugin_name)?></th>
                        <td>
                            <input type="checkbox" id="chcek_comment" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[comments]" value="1" <?php echo checked( 1, $this->options['comments'], false )?>>
                            <label for="chcek_comment"><?php _e('Add new comment.', $this->plugin_name)?></label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Woocommerce:', $this->plugin_name)?></th>
                        <td>
                            <input type="checkbox" id="chcek_order" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]" value="1" <?php echo checked( 1, $this->options['woocommerce'], false )?>>
                            <label for="chcek_order"><?php _e('Add new order.', $this->plugin_name)?></label><?php
                                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                                if(!is_plugin_active( 'woocommerce/woocommerce.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', $this->plugin_name).")</p>";
                            ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('User:', $this->plugin_name)?></th>
                        <td>
                            <input type="checkbox" id="chcek_user" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[user_register]" value="1" <?php echo checked( 1, $this->options['user_register'], false )?>>
                            <label for="chcek_user"><?php _e('An user join.', $this->plugin_name)?></label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Contact Form 7:', $this->plugin_name)?></th>
                        <td>
                            <input type="checkbox" id="chcek_cf7" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[wpcf7]" value="1" <?php echo checked( 1, $this->options['wpcf7'], false )?>>
                            <label for="chcek_cf7"><?php _e('An new contact message.', $this->plugin_name)?></label><?php
                                if(!is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', $this->plugin_name).")</p>";
                            ?>
                        </td>
                    </tr>

                </table>
                <?php submit_button(); ?>
            </form>

            <h2><?php _e('Test Line Notify', $this->plugin_name)?></h2>
            <?php
            if(isset($test_send)) echo $test_send;
            ?>
            <form action="" method="post">
            <?php wp_nonce_field('test_button_clicked'); ?>
                <textarea name="text_line_notify" style="width: 500px; height: 100px; "></textarea>
                <?php submit_button('Test'); ?>
            </form>
        </div>
    <?
    }


    /*
        send
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
        	$message = __("You have an new comment.\n" , $this->plugin_name ) . $comment->comment_content;
    		$this->line_send( $message );
    	}
    }

    function new_woocommerce_order_alert( $order_get_id ) {

    	if( isset($this->options['woocommerce']) && $this->options['woocommerce'] == 1 ){

            $order = wc_get_order( $order_get_id );
            $order_data = $order->get_data();

        	$message = __( "You have an new order." , $this->plugin_name );
        	if(isset($order_data['total'])) $message .= __( " total :" , $this->plugin_name ) . $order_data['total'];
    		$this->line_send( $message );
    	}

    }

    function new_user_register_alert( $user_id ) {

        if( isset($this->options['user_register']) && $this->options['user_register'] == 1 ){
            $message = __( "You have an new user register." , $this->plugin_name );

            $user_info = get_userdata($user_id);
            $message .= __( " Username: " , $this->plugin_name ) . $user_info->user_login;
            $this->line_send( $message );
        }
    }


    function new_wpcf7_message($cf7) {

        $wpcf = WPCF7_ContactForm::get_current();

        $message = __( "You have an new contact message." , $this->plugin_name );
        $this->line_send( $message );
    }

}