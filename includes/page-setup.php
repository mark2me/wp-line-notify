<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( !isset($this->option['token']) || empty($this->option['token']) ): ?>
<div class="notice notice-error is-dismissible">
    <p><?php _e( 'LINE Notify token is required!' , 'wp-line-notify' ); ?></p>
</div>
<?php endif; ?>

<div class="wrap">

    <h2><?php _e( 'Line Notify Setting' , 'wp-line-notify' )?> <span style="font-size:14px;">Ver.<?php echo $this->version?></span></h2>

    <form method="post" action="<?php echo admin_url('options.php?uid='.(int)$user_id); ?>">

        <?php settings_fields('line-notify-option'); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Line Notify Token' , 'wp-line-notify' )?></th>
                <td>
                    <select id="user-id" size="1" name="<?php echo $option_name?>[uid]">
                        <option value=""><?php _e('General', 'wp-line-notify' )?></option>
                    <?php
                    foreach( $web_users as $user ) {
                        $selected = ( $user_id === $user->ID) ? 'selected ="selected"' : '';
                        echo '<option value="'.$user->ID.'" '.$selected.'>'.$user->display_name.' ('. translate_user_role($wp_roles->roles[$user->roles[0]]['name']) .')</option>';
                    }
                    ?>
                    </select>
                    <input type="text" id="user-token" class="regular-text" name="<?php echo $option_name?>[token]" value="<?php if( !empty($this->option['token']) ) echo esc_attr( $this->option['token'] ) ?>">
                    <?php
                        if( !empty($this->option['token']) ){
                            $line = new sig_line_notify( $this->option['token'] );
                            echo ( $line->check_token() ) ? __('Access token valid.', 'wp-line-notify' ) : __( 'Invalid access token.' , 'wp-line-notify' );
                        }
                    ?>
                        <p class="description"><?php echo __( '* Generate access token on LINE website' , 'wp-line-notify' )?><a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a></p>

                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e( 'When to send message ?' , 'wp-line-notify' )?></h2>
        <table class="form-table">

            <tr valign="top">
                <th scope="row" rowspan="2"><?php _e( 'Post' , 'wp-line-notify' )?></th>
                <td>
                    <strong><?php _e( 'Publish' , 'wp-line-notify' )?></strong>&nbsp;&nbsp;(
                <?php
                    _e( 'Select roles:' , 'wp-line-notify' );

                    foreach( $roles as $role => $name ){
                ?>
                        <input type="checkbox" id="publish_post_<?php echo $role?>"
                        name="<?php echo $option_name; ?>[post_status][publish][]"
                        value="<?php echo esc_attr( $role ); ?>" <?php if( isset( $this->option['post_status']['publish'] ) && in_array( $role, $this->option['post_status']['publish']) ) echo ' checked="checked"'; ?>><label for="publish_post_<?php echo $role?>"><?php echo translate_user_role($name)?></label>&nbsp;&nbsp;
                <?php
                    }
                ?>)</td>
            </tr>
            <tr>
                <td>
                    <strong><?php _e( 'Pending' , 'wp-line-notify' )?></strong>&nbsp;&nbsp;(
                <?php
                    _e( 'Select roles:' , 'wp-line-notify' );

                    foreach( $roles as $role => $name ){
                ?>
                        <input type="checkbox" id="pending_post_<?php echo $role?>"
                        name="<?php echo $option_name; ?>[post_status][pending][]"
                        value="<?php echo esc_attr( $role ); ?>" <?php if( isset( $this->option['post_status']['pending'] ) && in_array( $role, $this->option['post_status']['pending']) ) echo ' checked="checked"'; ?>><label for="pending_post_<?php echo $role?>"><?php echo translate_user_role($name)?></label>&nbsp;&nbsp;
                <?php
                    }
                ?>)</td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Comments' , 'wp-line-notify' )?></th>
                <td>
                    <input type="checkbox" id="chcek_comment"
                        name="<?php echo $option_name; ?>[comments]"
                        value="yes" <?php if(isset($this->option['comments'])) echo checked( 'yes', $this->option['comments'], false )?>>

                    <label for="chcek_comment"><?php _e( 'Add a new comment' , 'wp-line-notify' )?></label>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e( 'Users' , 'wp-line-notify' )?></th>
                <td>
                    <input type="checkbox" id="chcek_user"
                        name="<?php echo $option_name; ?>[user_register]"
                        value="yes" <?php if(isset($this->option['user_register'])) echo checked( 'yes', $this->option['user_register'], false )?>>

                    <label for="chcek_user"><?php _e( 'User register' , 'wp-line-notify' )?></label>
                </td>
            </tr>
        </table>

        <hr>

        <?php
            sig_line_notify_woo::woo_box_html($option_name);
        ?>
        <hr>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Contact Form 7' , 'wp-line-notify' ); ?></th>
                <td>
                <?php
                    if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )) {
                        if ( post_type_exists('wpcf7_contact_form') ) {

                            echo '<p class="description">' .__( 'When a new contact message is received.' , 'wp-line-notify' ) .'</p>';

                            $items = WPCF7_ContactForm::find();

                        	foreach ( $items as $item ) {
                            	$pid = $item->id();
                            	$title = $item-> title();
                        ?>
                                <p>
                                    <input type="checkbox" id="chcek_cf7"
                                name="<?php echo $option_name?>[wpcf7_form][]"
                                value="<?php echo esc_attr( $pid ); ?>" <?php if( isset($this->option['wpcf7_form']) && in_array( $pid, $this->option['wpcf7_form'] ) ) echo 'checked="checked"' ?>><?php echo $title;?>
                                    &nbsp;&nbsp;<a href="<?php echo admin_url( 'admin.php?page=wpcf7&post='. sanitize_text_field($pid) .'&active-tab=1' ); ?>" target="_blank"><?php _e( '(Edit message template)' , 'wp-line-notify' )?></a>
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
        </table>

        <hr>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Elementor Pro' , 'wp-line-notify' ); ?></th>
                <td>
                <?php if( is_plugin_active( 'elementor-pro/elementor-pro.php' )): ?>
                    <input type="checkbox" id="chcek_elementor_form"
                        name="<?php echo $option_name; ?>[elementor_form]"
                        value="yes" <?php if(isset($this->option['elementor_form'])) echo checked( 'yes', $this->option['elementor_form'], false )?>>

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
    <form id="sig_line_notify_test" action="<?php echo esc_url( admin_url('admin-ajax.php?action=sig_line_notify_test')); ?>" method="post">
        <?php wp_nonce_field( 'sig_line_notify_test_nonce' ); ?>
        <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <?php _e( 'Send to' , 'wp-line-notify' )?>
            </th>
            <td>
                <select size="1" name="line_notify_uid_test">
                    <option value="0"><?php _e('General', 'wp-line-notify' )?></option>
                <?php
                foreach( $web_users as $user ) {
                    echo '<option value="'. esc_attr( $user->ID ) .'">'.$user->display_name.' ('. translate_user_role($wp_roles->roles[$user->roles[0]]['name']) .')</option>';
                }
                ?>
                </select>
            </td>
        </tr>
        </table>
        <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <?php _e( 'Test message' , 'wp-line-notify' )?>
            </th>
            <td>
                <textarea name="line_notify_content_test" style="width: 400px; height: 100px; "></textarea>
                <div id="line_notify_result_test"></div>
                <?php submit_button( __( 'Send test' , 'wp-line-notify' ), 'primary', '', true, 'id=submit_test'); ?>
            </td>
        </tr>
        </table>

    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    $('#user-id').on('change',function(){
        var uid = $(this).val();
        location.href = '<?php echo admin_url('options-general.php?page=sig-wp-line-notify'); ?>';
        if(uid) location.href += '&uid='+uid;
    });

    $('[name=line_notify_content_test]').focus(function(){
        $('#sig_line_notify_test div').text('');
    });

    $('#sig_line_notify_test').submit(function(){
        $('#submit_test').prop('disabled', true);
        $.post( '<?php echo esc_url( admin_url('admin-ajax.php?action=sig_line_notify_test') ); ?>', $('#sig_line_notify_test').serialize() )
        .fail(function(jqXHR, textStatus) {
            errText = jqXHR.responseText;
            $('#line_notify_result_test').text('Error: ' + errText.replace(/<[^>]+>/g, ''));
            $('#submit_test').prop('disabled', false);
        })
        .done(function( data ) {
            $('#line_notify_result_test').text(data);
            $('#submit_test').prop('disabled', false);
        });
        return false;
    });

});
</script>
