<?php

class WP_LINE_NOTIFY_wooTemplate {

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
}