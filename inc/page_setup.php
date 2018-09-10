<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !isset($this->options['token']) || $this->options['token'] === ''){
?>
    <div class="notice notice-error  is-dismissible">
        <p><?php
                _e( 'LINE Notify token is required!', $this->plugin_name );
        ?></p>
    </div>
<?php
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
                    <p class="description"><?php _e('* Generate access token on LINE website',$this->plugin_name)?> <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a> </p>
                </td>
            </tr>
        </table>

        <h2><?php _e('When to send message ?',$this->plugin_name)?></h2>
        <table class="form-table">
            <tr valign="top">
            <tr valign="top">
                <th scope="row"><?php _e('Comments')?></th>
                <td>
                    <input type="checkbox" id="chcek_comment"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[comments]"
                        value="1" <?php if(isset($this->options['comments'])) echo checked( 1, $this->options['comments'], false )?>>

                    <label for="chcek_comment"><?php _e('Add a new comment', $this->plugin_name)?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Woocommerce','quadric')?></th>
                <td>
                    <input type="checkbox" id="chcek_order"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]"
                        value="1" <?php if(isset($this->options['woocommerce'])) echo checked( 1, $this->options['woocommerce'], false )?>>

                    <label for="chcek_order"><?php _e('Add a new order', $this->plugin_name)?></label><?php
                        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        if(!is_plugin_active( 'woocommerce/woocommerce.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', $this->plugin_name).")</p>";
                    ?>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Users')?></th>
                <td>
                    <input type="checkbox" id="chcek_user"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[user_register]"
                        value="1" <?php if(isset($this->options['user_register'])) echo checked( 1, $this->options['user_register'], false )?>>

                    <label for="chcek_user"><?php _e('User register', $this->plugin_name)?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('Contact Form', 'contact-form-7')?> 7</th>
                <td>
                    <input type="checkbox" id="chcek_cf7"
                        name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[wpcf7]"
                        value="1" <?php if(isset($this->options['wpcf7'])) echo checked( 1, $this->options['wpcf7'], false )?>>

                    <label for="chcek_cf7"><?php _e('Add a new contact message', $this->plugin_name)?></label><?php
                        if(!is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) echo "&nbsp;&nbsp;<p class=\"description\">(".__('This plugin is not install or active.', $this->plugin_name).")</p>";
                    ?>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('New Post')?></th>
                <td>
                <?php
                    _e('Select roles:',$this->plugin_name);

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

    <h2><?php _e('Test Line Notify', $this->plugin_name)?></h2>
    <?php
    if(isset($test_send)) echo $test_send;
    ?>
    <form action="" method="post">
    <?php wp_nonce_field('test_button_clicked'); ?>
        <textarea name="text_line_notify" style="width: 500px; height: 100px; "></textarea>
        <?php submit_button(__('Test Send',$this->plugin_name)); ?>
    </form>
</div>