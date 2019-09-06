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
			__( 'You have a new order.', SIG_LINE_NOTIFY_PLUGIN_NAME ),
			__( 'order name:', SIG_LINE_NOTIFY_PLUGIN_NAME),
			__( 'order item:', SIG_LINE_NOTIFY_PLUGIN_NAME),
			__( 'payment method:', SIG_LINE_NOTIFY_PLUGIN_NAME),
			__( 'shipping name:', SIG_LINE_NOTIFY_PLUGIN_NAME),
			__( 'total:', SIG_LINE_NOTIFY_PLUGIN_NAME)
        );

		return trim( $template );
    }
}