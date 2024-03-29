<?php

class WP_LINE_NOTIFY_WOO {

    public static function init($type='order') {

		$fields = array(
    		'billing' => array(
    			'billing_first_name' => __( 'Billing First Name', 'wp-line-notify' ),
    			'billing_last_name'  => __( 'Billing Last Name', 'wp-line-notify' ),
    			'billing_company'    => __( 'Billing Company', 'wp-line-notify' ),
    			'billing_address_1'  => __( 'Billing Address 1', 'wp-line-notify' ),
    			'billing_address_2'  => __( 'Billing Address 2', 'wp-line-notify' ),
    			'billing_city'       => __( 'Billing City', 'wp-line-notify' ),
    			'billing_state'      => __( 'Billing State', 'wp-line-notify' ),
    			'billing_postcode'   => __( 'Billing Postal/Zip Code', 'wp-line-notify' ),
    			'billing_country'    => __( 'Billing Country / Region', 'wp-line-notify' ),
    			'billing_email'      => __( 'Email Address', 'wp-line-notify' ),
    			'billing_phone'      => __( 'Billing Phone Number', 'wp-line-notify' ),
            ),
            'shipping' => array(
    			'shipping_first_name'=> __( 'Shipping First Name', 'wp-line-notify' ),
    			'shipping_last_name' => __( 'Shipping Last Name', 'wp-line-notify' ),
    			'shipping_company'   => __( 'Shipping Company', 'wp-line-notify' ),
    			'shipping_address_1' => __( 'Shipping Address 1', 'wp-line-notify' ),
    			'shipping_address_2' => __( 'Shipping Address 2', 'wp-line-notify' ),
    			'shipping_city'      => __( 'Shipping City', 'wp-line-notify' ),
    			'shipping_state'     => __( 'Shipping State', 'wp-line-notify' ),
    			'shipping_postcode'  => __( 'Shipping Postal/Zip Code', 'wp-line-notify' ),
    			'shipping_country'   => __( 'Shipping Country / Region', 'wp-line-notify' ),
    			'shipping_phone'     => __( 'Shipping Phone Number', 'wp-line-notify' ),
            ),
            'order' => array(
                'order-id'          => __( 'Order id' , 'wp-line-notify' ),
                'order-product'     => __( 'Order item' , 'wp-line-notify' ),
                'order-name'        => __( 'Order name' , 'wp-line-notify' ),
                'shipping-name'     => __( 'Shipping name' , 'wp-line-notify' ),
                'payment-method'    => __( 'Payment method' , 'wp-line-notify' ),
                'total'             => __( 'Total' , 'wp-line-notify' ),
                'order-time'        => __( 'Order time' , 'wp-line-notify' ),
                'customer_note'     => __( 'Order notes', 'wp-line-notify' ),
            )
		);

		return (isset($fields[$type])) ? $fields[$type] : array();

    }

    public static function form() {
        $template = sprintf(
'%1$s
%2$s: [order-id]
%3$s: [order-product]
%4$s: [order-name]
%5$s: [shipping-name]
%6$s: [payment-method]
%7$s: [total]',
                __( 'You have a new order.' , 'wp-line-notify' ),
                __( 'Order id' , 'wp-line-notify' ),
                __( 'Order item' , 'wp-line-notify' ),
                __( 'Order name' , 'wp-line-notify' ),
                __( 'Shipping name' , 'wp-line-notify' ),
                __( 'Payment method' , 'wp-line-notify' ),
                __( 'Total' , 'wp-line-notify' )
        );

        return trim( $template );
    }

    /*
        woo-checkout-field-editor
    */
    public static function get_fields($type='') {

        $fields = [];
        if( class_exists('THWCFD_Utils') ){
            $fields = THWCFD_Utils::get_fields($type);
        }
        return $fields;
    }

    private static function  fields_tags_list($type='') {

        foreach( self::get_fields($type) as $tag => $v ){
            echo '<a href="javascript:;" class="woo_item_tag" data-id="'.$tag.'" data-label="'.$v['label'].'" title="'. $tag .'">'.$v['label'].'</a>';
        }
    }


    public static function woo_box_html() {

        $options = get_option(SIG_LINE_NOTIFY_OPTIONS);
        $my_status = (!empty($options['woo_status'])) ? $options['woo_status']:[];
?>
        <input type="checkbox" id="chcek_order" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce]" value="1" <?php if(isset($options['woocommerce'])) echo checked( 1, $options['woocommerce'], false )?>>
        <label for="chcek_order"><?php _e( 'Add a new order' , 'wp-line-notify' )?></label><br>
        <hr>

        <?php _e( 'Order status changed:' , 'wp-line-notify' )?>
        <?php foreach(wc_get_order_statuses() as $type=>$name): ?>
            &nbsp;&nbsp;&nbsp;<input type="checkbox" id="chcek_order_<?php echo $type?>" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woo_status][]" value="<?php echo $type?>" <?php if( in_array($type,$my_status) ) echo 'checked="checked"';?>>
            <label for="chcek_order_<?php echo $type?>"><?php echo $name?></label>
        <?php endforeach;?>
        <hr>

        <h4><?php
            _e( 'You can use these tags in the message template:' , 'wp-line-notify' );
            _e( '(Click tag to insert into the template)' , 'wp-line-notify' );
        ?></h4>

        <table id="table_woo">
            <tr>
                <td><?php _e('Default Item','wp-line-notify')?></td>
                <td colspan="2"><?php
                    foreach( self::init('order') as $tag => $label ){
                        echo '<a href="javascript:;" class="woo_item_tag" data-id="'.$tag.'" data-label="'.$label.'" title="'. $tag .'">'.$label.'</a>';
                    }
                ?></td>
            </tr>

            <?php if( class_exists('THWCFD_Utils') ): ?>

            <tr>
                <td><?php _e('Billing Fields','wp-line-notify')?></td>
                <td colspan="2"><?php
                    self::fields_tags_list('billing');
                ?></td>
            </tr>
            <tr>
                <td><?php _e('Shipping Fields','wp-line-notify')?></td>
                <td colspan="2"><?php
                    self::fields_tags_list('shipping');
                ?></td>
            </tr>
            <tr>
                <td><?php _e('Additional Fields','wp-line-notify')?></td>
                <td colspan="2"><?php
                    self::fields_tags_list('additional');
                ?></td>
            </tr>

            <?php else: ?>

            <tr>
                <td><?php _e('Buyer Information','wp-line-notify')?></td>
                <td colspan="2"><?php
                    foreach( self::init('billing') as $tag => $label ){
                        echo '<a href="javascript:;" class="woo_item_tag" data-id="'.$tag.'" data-label="'.$label.'" title="'. $tag .'">'.$label.'</a>';
                    }?></td>
            </tr>
            <tr>
                <td><?php _e('Recipient Information','wp-line-notify')?></td>
                <td colspan="2"><?php
                    foreach( self::init('shipping') as $tag => $label ){
                        echo '<a href="javascript:;" class="woo_item_tag" data-id="'.$tag.'" data-label="'.$label.'" title="'. $tag .'">'.$label.'</a>';
                    }?></td>
            </tr>

            <?php endif; ?>

            <tr><td colspan="3"><hr></td></tr>

            <tr>
                <td><?php _e('Template','wp-line-notify')?></td>
                <td width="400">
                    <textarea id="woo_msg_textarea" class="regular-text code" name="<?php echo SIG_LINE_NOTIFY_OPTIONS?>[woocommerce_tpl]" style="width: 100%;height: 300px;"><?php

                if(isset($options['woocommerce_tpl']) && $options['woocommerce_tpl']!==''){
                    echo esc_html( $options['woocommerce_tpl'] );
                }

            ?></textarea>

                </td>
                <td style="vertical-align:top"><p class="description"><?php _e( '* If you do not enter any text, the system will use the default template.' , 'wp-line-notify' )?></p><code><?php echo nl2br(WP_LINE_NOTIFY_WOO::form());?></code></td>
            </tr>
        </table>



        <style type="text/css">
        #table_woo td{ padding: 5px 10px}
        .woo_item_tag{
            display: inline-block;
            color: #333;
            background-color: rgba(0,0,0,0.07);
            margin:10px 10px 0 0;
            padding: 3px 10px;
            border-radius: 5px;
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.woo_item_tag').on('click', function(e) {
                var tag = $(this).data('id'), label =  $(this).data('label'), $area = $('#woo_msg_textarea');
                var start = $area[0].selectionStart,
                    end = $area[0].selectionEnd, content = $area.val(),
                    sel_name = label+': [' + tag + ']\n';

                $area.val(content.substring(0, start) + sel_name + content.substring(start));
                $area.focus();
                $area[0].selectionStart = $area[0].selectionEnd = start + sel_name.length;

            });
        });
        </script>
<?php

    }
}