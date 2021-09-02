<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !isset($this->options['token']) || $this->options['token'] === ''){
?>
    <div class="notice notice-error  is-dismissible">
        <p><?php _e( 'LINE Notify token is required!' , 'wp-line-notify' ); ?></p>
    </div>
<?php
}

?>
<div class="wrap">
    <h2><?php _e( 'Line Notify Setting' , 'wp-line-notify' )?></h2>
    <form method="post" action="options.php">
        <?php settings_fields('line-notify-option'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Line Notify Token:' , 'wp-line-notify' )?></th>
                <td>
                    <input type="text" class="regular-text" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[token]" value="<?php if(isset($this->options['token'])) echo esc_attr( $this->options['token'] ) ?>">
                    <?php
                        if($this->token_status['code']==200){
                            echo __('Access token valid.', 'wp-line-notify' );
                    ?>
                        <p class="description"><button type="button" id="btn-revoke"><?php echo __( 'Revoke token' , 'wp-line-notify' )?></button> </p>
                    <?php
                        } else {
                            echo __( 'Invalid access token.' , 'wp-line-notify' );
                    ?>
                        <p class="description"><?php echo __( '* Generate access token on LINE website' , 'wp-line-notify' )?> <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a> </p>
                    <?php
                        }
                    ?>

                </td>
            </tr>
        </table>

        <hr>

        <?php
            global $wp_roles;
            $roles = $wp_roles->get_names();
        ?>
        <h2><?php _e( 'When to send message ?' , 'wp-line-notify' )?></h2>
        <table class="form-table">

            <tr valign="top">
                <th scope="row" rowspan="2"><?php _e( 'Post' )?></th>
                <td>
                    <strong><?php _e( 'Publish' )?></strong>&nbsp;&nbsp;(
                <?php
                    _e( 'Select roles:' , 'wp-line-notify' );

                    foreach($roles as $name => $role){
                ?>
                    <input type="checkbox" id="publish_post_<?php echo $name?>"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS."[publish_post][$name]"?>"
                        value="1" <?php if(isset($this->options['publish_post'][$name])) echo checked( 1, $this->options['publish_post'][$name], false )?>><label for="publish_post_<?php echo $name?>"><?php echo translate_user_role($role)?></label>&nbsp;&nbsp;

                <?php
                        }
                ?>)</td>
            </tr>
            <tr>
                <td>
                    <strong><?php _e( 'Pending' )?></strong>&nbsp;&nbsp;(
                <?php
                    _e( 'Select roles:' , 'wp-line-notify' );

                    foreach($roles as $name => $role){
                ?>
                    <input type="checkbox" id="pending_post_<?php echo $name?>"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS."[pending_post][$name]"?>"
                        value="1" <?php if(isset($this->options['pending_post'][$name])) echo checked( 1, $this->options['pending_post'][$name], false )?>><label for="pending_post_<?php echo $name?>"><?php echo translate_user_role($role)?></label>&nbsp;&nbsp;

                <?php
                        }
                ?>)</td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Comments' )?></th>
                <td>
                    <input type="checkbox" id="chcek_comment"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[comments]"
                        value="1" <?php if(isset($this->options['comments'])) echo checked( 1, $this->options['comments'], false )?>>

                    <label for="chcek_comment"><?php _e( 'Add a new comment' , 'wp-line-notify' )?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Users' )?></th>
                <td>
                    <input type="checkbox" id="chcek_user"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[user_register]"
                        value="1" <?php if(isset($this->options['user_register'])) echo checked( 1, $this->options['user_register'], false )?>>

                    <label for="chcek_user"><?php _e( 'User register' , 'wp-line-notify' )?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"></th>
                <td>
                    <hr>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'WooCommerce' , 'quadric' ); ?></th>
                <td>
                <?php
                    if( is_plugin_active( 'woocommerce/woocommerce.php' ) ){
                ?>
                    <input type="checkbox" id="chcek_order"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]"
                        value="1" <?php if(isset($this->options['woocommerce'])) echo checked( 1, $this->options['woocommerce'], false )?>>

                    <label for="chcek_order"><?php _e( 'Add a new order' , 'wp-line-notify' )?></label><?php
                        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    ?>
                    <hr>
                    <legend><?php _e( 'You can use these tags in the message template:' , 'wp-line-notify' )?></legend>
                    <legend>[total] [order-product] [order-name] [shipping-name] [payment-method] [order-date] [order-time]</legend>
                    <textarea class="regular-text code"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce_tpl]"
                        cols="50" rows="10"
                        placeholder="<?php echo $woo_form->form();?>"><?php
                        if(isset($this->options['woocommerce_tpl']) && $this->options['woocommerce_tpl']!==''){
                            echo esc_html( $this->options['woocommerce_tpl'] );
                        }
                    ?></textarea>
                    <p class="description"><?php _e( '* If you are not input it, system used default template.' , 'wp-line-notify' )?></p>

                <?php
                    } else {
                        echo '<p class="description">(' . __( 'This plugin is not install or active.' , 'wp-line-notify' ) . ')</p>';
                    }
                ?>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><?php
                    _e( 'Contact Form 7' , 'wp-line-notify' );
                ?></th>
                <td>
                <?php if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) {
                    if ( post_type_exists('wpcf7_contact_form') ) {
                        $args = array('post_type' => 'wpcf7_contact_form', 'post_per_page' => -1);
                        $the_query = new WP_Query($args);
                        if ($the_query->have_posts()) {
                            while ($the_query->have_posts()) {
                                $the_query->the_post();
                                //$filed_name = SIG_LINE_NOTIFY_OPTIONS.'[wpcf7]['.get_the_ID().']';
                    ?>
                            <p>
                                <input type="checkbox" id="chcek_cf7"
                            name="<?php echo SIG_LINE_NOTIFY_OPTIONS.'[wpcf7]['.get_the_ID().']';?>"
                            value="1" <?php if(isset($this->options['wpcf7'][get_the_ID()])) echo checked( 1, $this->options['wpcf7'][get_the_ID()], false )?>><?php echo get_the_title();?>
                            </p>

                    <?php
                            }
                            wp_reset_postdata();
                        }
                    }

                    echo '<p class="description">' .__( 'When a new contact message is received.' , 'wp-line-notify' ) .'</p>';
                } else {
                    echo '<p class="description">(' . __( 'This plugin is not install or active.' , 'wp-line-notify' ) . ')</p>';
                }

                ?>


                </td>
            </tr>



        </table>
        <?php submit_button(); ?>
    </form>

    <br><hr><br>

    <h2><?php _e( 'Test Line Notify' , 'wp-line-notify' )?></h2>
    <?php
    if(isset($test_send)) echo $test_send;
    ?>
    <form action="" method="post">
    <?php wp_nonce_field('test_button_clicked'); ?>
        <textarea name="text_line_notify" style="width: 500px; height: 100px; "></textarea>
        <?php submit_button(__( 'Test Send' , 'wp-line-notify' )); ?>
    </form>
</div>

<script>
(function($) {
    $(function() {
        $('#btn-revoke').click(function(){
            if(confirm("<?php _e( 'Are you sure to revoke notification configurations?' , 'wp-line-notify' ) ?>")){
            	$.getJSON("<?php echo $this->revoke_url;?>", function(result){
                	console.log(result);
                    alert(result.message);
                });
            }else{
            	return false;
            }
        });
    });
})(jQuery);
</script>
