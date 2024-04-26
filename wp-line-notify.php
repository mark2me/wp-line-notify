<?php
/**
 * Plugin Name: WordPress LINE Notify
 * Description: This plugin can send a alert message by LINE Notify
 * Version:     1.4.5
 * Author:      Simon Chuang
 * Author URI:  https://github.com/mark2me/wp-line-notify
 * License:     GPLv2
 * Text Domain: wp-line-notify
 * Domain Path: /languages
 */

define( 'SIG_LINE_NOTIFY_OPTIONS', '_sig_line_notify_setting' );
define( 'SIG_LINE_NOTIFY_DIR', dirname(__FILE__) );

require_once( SIG_LINE_NOTIFY_DIR . '/includes/upgrade.php' );
require_once( SIG_LINE_NOTIFY_DIR . '/includes/class-line.php' );
require_once( SIG_LINE_NOTIFY_DIR . '/includes/class-woo.php' );

class WpLineNotify{

    private $version = '';

    private $langs = '';

    private $plugin_name = '';

    public $option;

    public function __construct()
    {
        $data = get_file_data( __FILE__, array('ver' => 'Version', 'langs' => 'Domain Path') );
        $this->version = $data['ver'];
        $this->langs = $data['langs'];

        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $my_option_name = $this->current_user_option_name();

        $upgrade = new sig_line_notify_upgrade( $my_option_name, $this->version );
        $upgrade->run();

        $this->option = get_option( $my_option_name );
        if( empty($this->option) ) $this->option = [];



        // load textdomain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // add setting line
        add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'plugin_settings_link' ) );

        // add menu
        add_action( 'admin_menu', array( $this,'add_option_menu') );

        // add register setting
        add_action( 'admin_init', array( $this, 'add_register_setting' ) );

        // update usermeta
        add_action( "pre_update_option_".$this->current_user_option_name(), array( $this, 'update_option_to_usermeta' ), 10, 3 );

        // add user profile
        add_action( 'show_user_profile', array( $this, 'add_user_profile' ), 99 );

        // save user profile
        add_action( 'personal_options_update', array( $this, 'save_user_profile' ) );

        // alert: post status
        add_action( 'transition_post_status', array( $this, 'post_status_alert' ), 10, 3 );

        // alert: post comments
        add_action( 'comment_post', array( $this, 'new_comments_alert' ), 10, 2 );

        // alert: user register
        add_action( 'user_register' , array( $this, 'new_user_register_alert' ) , 10 , 1 );

        // alert: woocommerce new order, order status change
        if( is_plugin_active( 'woocommerce/woocommerce.php' ) ){
            add_action( 'woocommerce_new_order', array( $this, 'new_woocommerce_order_alert' ) ,10, 2);
            add_action( 'woocommerce_order_status_changed', array( $this, 'update_woocommerce_order_status' ) ,10, 4);
        }

        // alert: contact-form-7
        if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
            add_action("wpcf7_before_send_mail", array($this, "new_wpcf7_message"));
        }

        // alert: elementor-pro form
        if( is_plugin_active( 'elementor-pro/elementor-pro.php' ) ){
            add_action( 'elementor_pro/forms/actions/register', array( $this, 'register_new_form_actions') );
        }

        // test token
        add_action( 'wp_ajax_sig_line_notify_test', array( $this, 'sig_line_notify_test' ) );

    }

    ///////////////

    public function load_textdomain(){
        load_plugin_textdomain( 'wp-line-notify' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function plugin_settings_link( $actions) {
        $settings_link = array(
            '<a href="'. admin_url('options-general.php?page=sig-wp-line-notify'). '">'. esc_html__( 'Settings' , 'wp-line-notify' ).'</a>'
        );
        $actions = array_merge( $actions, $settings_link );
        return $actions;
    }

    public function add_option_menu(){

        $capability  = apply_filters( 'wp_line_notify/plugin_capabilities', 'manage_options' );

        add_options_page(
            __( 'Line Notify Setting' , 'wp-line-notify'),
            __( 'WP Line Notify' , 'wp-line-notify'),
            $capability,
            'sig-wp-line-notify',
            array($this, 'html_settings_page')
        );
    }


    public function html_settings_page() {

        $option_name = $this->current_user_option_name();
        $user_id = $this->sig_get_option_uid();

        global $wp_roles;
        $roles = $wp_roles->get_names();

        $web_users = WpLineNotify::sig_get_all_users();

        require_once SIG_LINE_NOTIFY_DIR . '/includes/page-setup.php';
    }

    public function add_register_setting(){
        register_setting( 'line-notify-option', $this->current_user_option_name(), array('type' => 'array') );
    }

    public function update_option_to_usermeta(  $value ,$old_value, $option){
        if( !empty($value['uid']) ) update_user_meta( $value['uid'], SIG_LINE_NOTIFY_OPTIONS , $value['token'] );
        return $value;
    }

    public function add_user_profile($user){

        $token = get_user_meta( $user->ID, SIG_LINE_NOTIFY_OPTIONS, true );
    ?>
        <h3><?php _e( 'Line Notify Setting' , 'wp-line-notify' )?></h3>
        <table class="form-table">
            <tr>
                <th><label for="line_token"><?php _e( 'Line Notify Token:' , 'wp-line-notify' )?></label></th>
                <td>
                    <input type="text" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>" id="line_token" value="<?php echo esc_attr( $token ) ?>" class="regular-text" />
                    <p class="description"><?php echo __( '* Generate access token on LINE website' , 'wp-line-notify' )?><a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a></p>
                </td>
            </tr>
        </table>
    <?php
    }

    public function save_user_profile( $user_id ) {

        if( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'update-user_' . $user_id ) ) {
            return;
        }

        if( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        $token = sanitize_text_field( $_POST[SIG_LINE_NOTIFY_OPTIONS] );

        update_user_meta( $user_id, SIG_LINE_NOTIFY_OPTIONS , $token );

        // update option
        $option = get_option(SIG_LINE_NOTIFY_OPTIONS.'_'.$user_id);
        $option['uid'] = $user_id;
        $option['token'] = $token;
        update_option( SIG_LINE_NOTIFY_OPTIONS.'_'.$user_id, $option );
    }

    ///////////////

    public function post_status_alert($new_status,  $old_status, $post){

        if( $post->post_type !== 'post' ) return;

        if( $new_status === $old_status ) return;

        $post_status = $new_status;

        $status = [
            'publish' => __( 'publish a post' , 'wp-line-notify' ),
            'pending' => __( 'pending a post' , 'wp-line-notify' )
        ];

        if( !isset( $status[$post_status] ) ){
            return;
        }else{
            $alert_text = $status[$post_status];
        }

        ///
        $author = get_userdata( $post->post_author );
        if( !is_object($author) ){
            return;
        }else{
            $roles = (array)$author->roles;
        }

        $token = [];

        foreach( self::sig_get_all_options() as $option ){

            if( !empty($option['token']) && isset($option['post_status']) && isset($option['post_status'][$post_status]) ) {

                $roles_alert = $option['post_status'][$post_status];

                foreach( $roles as $role_name ){
                    if( in_array( $role_name, $roles_alert ) ){
                        $token[] = $option['token'];
                        break;
                    }
                }

            }
        }

        if( !empty( $token ) ){
            $message = "{$author->display_name} {$alert_text} \n {$post->post_title} {$post->guid}";
            $this->send_msg( $token, $message );
        }

    }

    public function new_comments_alert( $comment_ID, $comment_approved ) {

        $token = [];

    	foreach( self::sig_get_all_options() as $option ){
        	if( !empty($option['token']) && isset($option['comments']) && $option['comments'] === 'yes' ){
                $token[] = $option['token'];
            }
		}

        if( !empty( $token ) ){
            $comment = get_comment( $comment_ID );
            $message = __( 'You have a new comment.' , 'wp-line-notify' ) . "\n" . $comment->comment_content;
            $this->send_msg( $token, $message );
        }

    }

    public function new_user_register_alert( $user_id ) {

        $token = [];

        foreach( self::sig_get_all_options() as $option ){
            if( !empty($option['token']) && isset($option['user_register']) && $option['user_register'] === 'yes' ){
                $token[] = $option['token'];
            }
        }

        if( !empty( $token ) ){
            $user_info = get_userdata($user_id);
            $message = __( 'You have a new user register.' , 'wp-line-notify' );
            $message .= __( 'Username:' , 'wp-line-notify' ) . $user_info->user_login;
            $this->send_msg( $token, $message );
        }
    }

    public function new_woocommerce_order_alert( $order_id, $order ) {

        $token = [];

        foreach( self::sig_get_all_options() as $option ){
            if( !empty($option['token']) && isset($option['woo_order']) && $option['woo_order'] === 'yes' ){
                $token[] = $option['token'];
            }
        }

        if( !empty( $token ) ){

            if( isset($this->option['woo_tpl']) && !empty($this->option['woo_tpl']) ){
        	    $message = $this->option['woo_tpl'];
            }else{
                $message = sig_line_notify_woo::form();
            }

            $order_data = $order->get_data();
            $order_product = '';

            if( isset($order_data['line_items'] ) && count( $order_data['line_items'] ) > 0 ){
                foreach( $order_data['line_items'] as $item ){
                    if( isset($item['name']) && isset($item['quantity']) ){
                        $product = $order->get_product_from_item( $item );
                        $sku = $product->get_sku();
                        if( !empty($sku) ){
                            $order_product .= "\n {$item['name']} [" . $product->get_sku() . "] x {$item['quantity']}";
                        }else{
                            $order_product .= "\n {$item['name']} x {$item['quantity']}";
                        }

                    }
                }
            }

            $order_name = (isset($order_data['billing']['first_name']) && isset($order_data['billing']['last_name'])) ? ($order_data['billing']['last_name'].$order_data['billing']['first_name']) : '-';

            $shipping_name = (isset($order_data['shipping']['first_name']) && isset($order_data['shipping']['last_name'])) ? ($order_data['shipping']['last_name'].$order_data['shipping']['first_name']) : '';

            $text = array(
                '[order-id]'        => $order_id,
                '[order-product]'   => $order_product,
                '[order-name]'      => $order_name,
                '[shipping-name]'   => $shipping_name,
                '[payment-method]'  => (isset($order_data['payment_method_title'])) ? $order_data['payment_method_title'] : '',
                '[total]'           => (isset($order_data['total'])) ? $order_data['total'] : '',
                '[order-time]'      => (isset($order_data['date_created'])) ? $order_data['date_created']->date('Y-m-d H:i:s') : '',
                '[customer_note]'   => (isset($order_data['customer_note'])) ? $order_data['customer_note'] : '',
            );

            // Checkout Field Editor for WooCommerce
            if( class_exists('THWCFD_Utils') ) {

                $metas = get_post_meta( $order_data['id'] );

                // billing
                foreach( sig_line_notify_woo::get_fields('billing') as $tag => $v ){
                    $text["[{$tag}]"] = ( isset($metas['_'.$tag]) ) ? $metas['_'.$tag][0] : '';
                }

                // shipping
                foreach( sig_line_notify_woo::get_fields('shipping') as $tag => $v ){
                    $text["[{$tag}]"] = ( isset($metas['_'.$tag]) ) ? $metas['_'.$tag][0] : '';
                }

                // Additional Fields
                foreach( sig_line_notify_woo::get_fields('additional') as $tag => $label ){
                    if( $tag === 'order_comments' ){
                        $text["[{$tag}]"] = ( isset($order_data['customer_note']) ) ? $order_data['customer_note']:'';
                    }else{
                        $text["[{$tag}]"] = ( isset($metas[$tag]) ) ? $metas[$tag][0] : '';
                    }
                }

            }else{
                // billing
                foreach( sig_line_notify_woo::init('billing') as $tag => $label ){
                    $field = str_replace('billing_' ,'' , $tag);
                    if( isset($order_data['billing'][$field]) ){
                        $text["[{$tag}]"] = $order_data['billing'][$field];
                    }
                }

                // shipping
                foreach( sig_line_notify_woo::init('shipping') as $tag => $label ){
                    $field = str_replace('shipping_','',$tag);
                    if( isset($order_data['shipping'][$field]) ){
                        $text["[{$tag}]"] = $order_data['shipping'][$field];
                    }
                }
            }

            $message = str_ireplace(  array_keys($text),  $text,  $message );
            $this->send_msg( $token, $message );
        }

    }

    public function update_woocommerce_order_status($order_id, $old_status, $new_status, $order) {

        $token = [];

        foreach( self::sig_get_all_options() as $option ){
            if( !empty($option['token']) && isset($option['woo_status']) ){
                if( ( $old_status !== $new_status ) && in_array( 'wc-'.$new_status, $option['woo_status'] ) ){
                    $token[] = $option['token'];
                }
            }
        }

        if( !empty( $token ) ){
            /* translators: %1$s is order id, %2$s is order state. */
            $message = sprintf( __('There is an order id %1$d, and the state is changed to %2$s.', 'wp-line-notify'), $order_id, wc_get_order_status_name($new_status) );
            $message .= admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
            $this->send_msg( $token, $message );
        }

    }

    public function new_wpcf7_message( $contact_form ) {

        $token = [];
        $wpcf7_id = $contact_form->id();

        foreach( self::sig_get_all_options() as $option ){
            if( !empty($option['token']) && isset($option['wpcf7_form']) && !empty($option['wpcf7_form']) ){
                if( in_array( $wpcf7_id, $option['wpcf7_form']) ) {
                    $token[] = $option['token'];
                }
            }
        }

        if( !empty( $token ) ){

            $mail_body = $contact_form->prop('mail')['body'];
            $message = __( "You have a new contact message.\n" , 'wp-line-notify' );
            $message .= wpcf7_mail_replace_tags( $mail_body );

            $this->send_msg( $token, $message );
        }

    }

    public function register_new_form_actions( $form_actions_registrar ){

        $token = [];

        foreach( self::sig_get_all_options() as $option ){
            if( !empty($option['token']) && isset($option['elementor_form']) && $option['elementor_form'] === 'yes' ){
                $token[] = $option['token'];
            }
        }

        require_once( SIG_LINE_NOTIFY_DIR . '/includes/class-elementor.php' );
        $form_actions_registrar->register( new \Line_Notify_After_Submit_Action( $token ) );

    }

    /**
     *
     */
    public function send_msg( $tokens=[], $message=''){

        if( empty( $tokens ) || empty( $message ) ) return;

        foreach( $tokens as $token ){
            $line = new sig_line_notify( $token );
            $line->send( $message );
        }

    }

    /**
     *  return string
     */
    public function current_user_option_name(){
        $user_id = $this->sig_get_option_uid();
        $option_name = SIG_LINE_NOTIFY_OPTIONS;
        if ( !empty($user_id) ) {
            $option_name .= '_'.$user_id;
        }
        return $option_name;
    }

    /**
     *  return array
     */
    public static function sig_get_all_options(){

        $options = [];
        $options[0] = get_option( SIG_LINE_NOTIFY_OPTIONS);

        $users = self::sig_get_all_users();
        foreach( $users as $user ){
            if( !empty(get_option( SIG_LINE_NOTIFY_OPTIONS."_{$user->ID}" )) ){
                $options[$user->ID] = get_option( SIG_LINE_NOTIFY_OPTIONS."_{$user->ID}" );
            }
        }

        return $options;
    }

    /**
     *  return array
     */
    public static function sig_get_all_users(){
        return get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ) ) );
    }

    /**
     *  Test token
     */
    public function sig_line_notify_test(){

        $rs_msg = "";

        check_admin_referer('sig_line_notify_test_nonce');

        $message = sanitize_textarea_field( $_POST['line_notify_content_test'] );
        if( empty($message) ) $message = __( 'This is a Line notify plugin test.' , 'wp-line-notify' );

        $option_name = SIG_LINE_NOTIFY_OPTIONS;
        $user_id = ! empty( $_POST['line_notify_uid_test'] ) ? absint( wp_unslash( $_POST['line_notify_uid_test'] ) ) : null;
        if( !empty($user_id) ) $option_name .= '_'.$user_id;

        $option = get_option( $option_name );

        if( empty($option['token']) ){
            $rs_msg = __( 'Please fill in the top Line Notify Token field.' , 'wp-line-notify' );
        }else{

            $line = new sig_line_notify( $option['token'] );
            $rs = $line->send( $message );

            if ( $rs === 'ok' ) {
                $rs_msg = __( 'Send test ok!' , 'wp-line-notify' );
            } else {
                $rs_msg = sprintf('%1s, message: %2s', __( 'Error on send LINE Notify.' , 'wp-line-notify' ), sanitize_text_field($rs) );
            }

        }

        wp_die( $rs_msg );
    }

    /**
     *  return int or null
     */
    public function sig_get_option_uid(){
        $user_id = ! empty( $_GET['uid'] ) ? absint( wp_unslash( $_GET['uid'] ) ) : null;
        if ( ! empty($user_id) ) {
            $userdata = get_user_by( 'id',$user_id );
            if ( ! $userdata ) {
                wp_redirect( admin_url( 'options-general.php?page=sig-wp-line-notify' ) );
                die();
            }
        }
        return $user_id;
    }
}

add_action( 'plugins_loaded', 'wp_line_notify', 10, 0 );

function wp_line_notify(){
    new WpLineNotify();
}
