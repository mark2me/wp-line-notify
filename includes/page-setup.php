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

    <h2><?php _e( 'Line Notify Setting' , 'wp-line-notify' )?> <span style="font-size:14px;">Ver.<?php echo $this->version?></span></h2>

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
                <th scope="row" rowspan="2"><?php _e( 'Post' , 'wp-line-notify' )?></th>
                <td>
                    <strong><?php _e( 'Publish' , 'wp-line-notify' )?></strong>&nbsp;&nbsp;(
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
                    <strong><?php _e( 'Pending' , 'wp-line-notify' )?></strong>&nbsp;&nbsp;(
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
                <th scope="row"><?php _e( 'Comments' , 'wp-line-notify' )?></th>
                <td>
                    <input type="checkbox" id="chcek_comment"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[comments]"
                        value="1" <?php if(isset($this->options['comments'])) echo checked( 1, $this->options['comments'], false )?>>

                    <label for="chcek_comment"><?php _e( 'Add a new comment' , 'wp-line-notify' )?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Users' , 'wp-line-notify' )?></th>
                <td>
                    <input type="checkbox" id="chcek_user"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[user_register]"
                        value="1" <?php if(isset($this->options['user_register'])) echo checked( 1, $this->options['user_register'], false )?>>

                    <label for="chcek_user"><?php _e( 'User register' , 'wp-line-notify' )?></label>
                </td>
            </tr>
        </table>

        <hr>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'WooCommerce' , 'wp-line-notify' ); ?></th>
                <td>
                <?php
                    if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                        WP_LINE_NOTIFY_WOO::woo_box_html();
                    } else {
                        echo '<p class="description">('. __( 'This plugin is not install or active.' , 'wp-line-notify' ) .')</p>';
                    } ?>
                </td>
            </tr>


            <tr valign="top">
                <th scope="row"><?php _e( 'Contact Form 7' , 'wp-line-notify' ); ?></th>
                <td>
                <?php
                    if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) {
                        if ( post_type_exists('wpcf7_contact_form') ) {

                            echo '<p class="description">' .__( 'When a new contact message is received.' , 'wp-line-notify' ) .'</p>';

                            $posts = get_posts(
                        		array(
                        			'numberposts' => -1,
                        			'post_type' => 'wpcf7_contact_form',
                        			'post_status' => 'publish',
                        		)
                        	);

                        	foreach ( $posts as $post ) {
                            	$pid = $post->ID;
                        ?>
                                <p>
                                    <input type="checkbox" id="chcek_cf7"
                                name="<?php echo SIG_LINE_NOTIFY_OPTIONS.'[wpcf7]['. $pid .']';?>"
                                value="1" <?php if(isset($this->options['wpcf7'][$pid])) echo checked( 1, $this->options['wpcf7'][$pid], false )?>><?php echo $post->post_title;?>
                                </p>
                        <?php

                            }
                        }

                    } else {
                        echo '<p class="description">(' . __( 'This plugin is not install or active.' , 'wp-line-notify' ) . ')</p>';
                    }
                ?>

                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Elementor Pro' , 'wp-line-notify' ); ?></th>
                <td>
                <?php if( is_plugin_active( 'elementor-pro/elementor-pro.php' )): ?>
                    <input type="checkbox" id="chcek_elementor_form"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[elementor_form]"
                        value="1" <?php if(isset($this->options['elementor_form'])) echo checked( 1, $this->options['elementor_form'], false )?>>

                    <label for="chcek_elementor_form"><?php
                        _e( 'When a new message is received from the Elementor Pro form widget.' , 'wp-line-notify' );
                    ?></label>
                    <p class="description">(<?php _e( 'You have to find the "Actions After Submit" in the form editing function and add a new action called "Line notify".' , 'wp-line-notify' )?>)</p>
                <?php else: ?>
                    <p class="description">(<?php _e( 'This plugin is not install or active.' , 'wp-line-notify' )?>)</p>
                <?php endif; ?>
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
