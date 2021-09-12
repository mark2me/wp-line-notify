<?php

class WP_LINE_NOTIFY_WOO {

    public static function form() {
        $template = sprintf(
'%1$s
%2$s [order-name]
%3$s [order-product]
%4$s [payment-method]
%5$s [shipping-name]
%6$s [total]',
			__( 'You have a new order.' , 'wp-line-notify' ),
			__( 'order name:' , 'wp-line-notify' ),
			__( 'order item:' , 'wp-line-notify' ),
			__( 'payment method:' , 'wp-line-notify' ),
			__( 'shipping name:' , 'wp-line-notify' ),
			__( 'total:' , 'wp-line-notify' )
        );

		return trim( $template );
    }

    public static function get_fields($key) {

        if(class_exists('THWCFD_Utils')){
            $fields = THWCFD_Utils::get_fields($key);
        }else{
            $fields = get_option('wc_fields_'. $key, array());
            $fields = is_array($fields) ? array_filter($fields) : array();
        }

		$array = [];
		foreach( $fields as $key => $f ){
    		$array[$key] = (!empty($f['label'])) ? $f['label'] : $key;
		}
		return $array;

    }


    public static function woo_box_html() {

        $options = get_option(SIG_LINE_NOTIFY_OPTIONS);
?>
        <input type="checkbox" id="chcek_order" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]" value="1" <?php if(isset($options['woocommerce'])) echo checked( 1, $options['woocommerce'], false )?>>

        <label for="chcek_order"><?php _e( 'Add a new order' , 'wp-line-notify' )?></label>

        <hr>
        <legend><?php _e( 'You can use these tags in the message template:' , 'wp-line-notify' )?></legend>
        <legend>[total] [order-product] [order-name] [shipping-name] [payment-method] [order-date] [order-time]</legend>


        <h5>billing</h5>
        <div>
            <?php
            foreach( WP_LINE_NOTIFY_WOO::get_fields('billing') as $k=>$b ){
                echo '<a href="javascript:;" class="woo_item" data-id="'.$k.'" title="'.$k.'">'.$b.'</a>，';
            }
            ?>
        </div>

        <h5>shipping</h5>
        <div>
            <?php
            foreach( WP_LINE_NOTIFY_WOO::get_fields('shipping') as $k=>$b ){
                echo '<a href="javascript:;" class="woo_item" data-id="'.$k.'">'.$b.'['.$k.']</a>，';
            }
            ?>
        </div>

        <textarea  id="woo_msg_textarea" class="regular-text code" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce_tpl]" cols="50" rows="10" placeholder="<?php echo WP_LINE_NOTIFY_WOO::form();?>"><?php

            if(isset($options['woocommerce_tpl']) && $options['woocommerce_tpl']!==''){
                echo esc_html( $options['woocommerce_tpl'] );
            }

        ?></textarea>
        <p class="description"><?php _e( '* If you are not input it, system used default template.' , 'wp-line-notify' )?></p>

        <script>
        jQuery(document).ready(function($) {
            $('.woo_item').on('click', function(e) {
                var tag = $(this).data('id'), $area = $('#woo_msg_textarea');
                var start = $area[0].selectionStart, end = $area[0].selectionEnd, content = $area.val();

                $area.val(content.substring(0, start) + '[' + tag + ']' + content.substring(start));
                $area.focus();
                $area[0].selectionStart = $area[0].selectionEnd = start+tag.length+2;

            });


        });
        </script>
<?php

    }
}