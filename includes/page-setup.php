<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !isset($this->options['token']) || $this->options['token'] === ''){
?>
    <div class="notice notice-error  is-dismissible">
        <p><?php _e( 'LINE Notify token is required!', SIG_LINE_NOTIFY_PLUGIN_NAME ); ?></p>
    </div>
<?php
}

?>
<div class="wrap">
    <h2><?php _e('Line Notify Setting',SIG_LINE_NOTIFY_PLUGIN_NAME)?></h2>
    <form method="post" action="options.php">
        <?php settings_fields('line-notify-option'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Line Notify Token:',SIG_LINE_NOTIFY_PLUGIN_NAME)?></th>
                <td>
                    <input type="text" class="regular-text" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[token]" value="<?php if(isset($this->options['token'])) echo esc_attr( $this->options['token'] ) ?>">
                    <?php
                        if($this->token_status['code']==200):
                            _e('Access token valid',SIG_LINE_NOTIFY_PLUGIN_NAME);
                    ?>
                        <p class="description"><button type="button" id="btn-revoke"><?php _e('Revoke token',SIG_LINE_NOTIFY_PLUGIN_NAME)?></button> </p>
                    <?php
                        else:
                            _e('Invalid access token',SIG_LINE_NOTIFY_PLUGIN_NAME);
                    ?>
                        <p class="description"><?php _e('* Generate access token on LINE website',SIG_LINE_NOTIFY_PLUGIN_NAME)?> <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a> </p>
                    <?php
                        endif;
                    ?>

                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('When to send message ?',SIG_LINE_NOTIFY_PLUGIN_NAME)?></h2>
        <table class="form-table">
            <tr valign="top">
            <tr valign="top">
                <th scope="row"><?php _e('Comments')?></th>
                <td>
                    <input type="checkbox" id="chcek_comment"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[comments]"
                        value="1" <?php if(isset($this->options['comments'])) echo checked( 1, $this->options['comments'], false )?>>

                    <label for="chcek_comment"><?php _e('Add a new comment', SIG_LINE_NOTIFY_PLUGIN_NAME)?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('WooCommerce','quadric')?></th>
                <td>
                    <input type="checkbox" id="chcek_order"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]"
                        value="1" <?php if(isset($this->options['woocommerce'])) echo checked( 1, $this->options['woocommerce'], false )?>>

                    <label for="chcek_order"><?php _e('Add a new order', SIG_LINE_NOTIFY_PLUGIN_NAME)?></label><?php
                        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        if(!is_plugin_active( 'woocommerce/woocommerce.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', SIG_LINE_NOTIFY_PLUGIN_NAME).")</p>";
                    ?>
                    <hr>
                    <legend><?php _e('You can use these tags in the message template:',SIG_LINE_NOTIFY_PLUGIN_NAME)?></legend>
                    <legend>[total] [order-product] [order-name] [shipping-name] [payment-method] [order-date] [order-time]</legend>
                    <textarea class="regular-text code"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce_tpl]"
                        cols="50" rows="10"
                        placeholder="<?php echo $woo_form->form();?>"><?php
                        if(isset($this->options['woocommerce_tpl']) && $this->options['woocommerce_tpl']!==''){
                            echo esc_html( $this->options['woocommerce_tpl'] );
                        }
                    ?></textarea>
                    <p class="description"><?php
                        _e('* If you are not input it, system used default template.', SIG_LINE_NOTIFY_PLUGIN_NAME)
                    ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Users')?></th>
                <td>
                    <input type="checkbox" id="chcek_user"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[user_register]"
                        value="1" <?php if(isset($this->options['user_register'])) echo checked( 1, $this->options['user_register'], false )?>>

                    <label for="chcek_user"><?php _e('User register', SIG_LINE_NOTIFY_PLUGIN_NAME)?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Contact Form', 'contact-form-7')?> 7</th>
                <td>
                <?php
                //$cf7_id_array = array();
                if (post_type_exists('wpcf7_contact_form')) {
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

                ?>

                    <label for="chcek_cf7"><?php _e('Add a new contact message', SIG_LINE_NOTIFY_PLUGIN_NAME)?></label><?php
                        if(!is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', SIG_LINE_NOTIFY_PLUGIN_NAME).")</p>";
                    ?>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('New Post')?></th>
                <td>
                <?php
                    _e('Select roles:',SIG_LINE_NOTIFY_PLUGIN_NAME);

                    global $wp_roles;
                    $roles = $wp_roles->get_names();
                    foreach($roles as $name => $role){

                ?>
                    <input type="checkbox" id="chcek_newpost_<?php echo $name?>"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS."[new_post][$name]"?>"
                        value="1" <?php if(isset($this->options['new_post'][$name])) echo checked( 1, $this->options['new_post'][$name], false )?>>
                    <label for="chcek_newpost_<?php echo $name?>"><?php echo translate_user_role($role)?></label>&nbsp;&nbsp;&nbsp;

                    <?php
                        }
                    ?>

                </td>
            </tr>

        </table>
        <?php submit_button(); ?>
    </form>

    <br><hr><br>

    <h2><?php _e('Test Line Notify', SIG_LINE_NOTIFY_PLUGIN_NAME)?></h2>
    <?php
    if(isset($test_send)) echo $test_send;
    ?>
    <form action="" method="post">
    <?php wp_nonce_field('test_button_clicked'); ?>
        <textarea name="text_line_notify" style="width: 500px; height: 100px; "></textarea>
        <?php submit_button(__('Test Send',SIG_LINE_NOTIFY_PLUGIN_NAME)); ?>
    </form>
</div>

<script>
(function($) {
    $(function() {
        $('#btn-revoke').click(function(){
            if(confirm("<?php _e('Are you sure to revoke notification configurations?',SIG_LINE_NOTIFY_PLUGIN_NAME) ?>")){
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
