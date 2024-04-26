<?php

/**
 *
 */

class sig_line_notify_upgrade {

    public $option_name;

    public $options;

    public $option_ver;

    public $plugin_ver;

    public function __construct( $option_name='', $ver='1.0' ){

        if( empty($option_name) ) return;

        $this->option_name = $option_name;

        $this->option = get_option( $option_name );
        if( empty($this->option) ) $this->option = [];

        $this->option_ver = ( isset($this->option['version']) ) ? $this->option['version'] : '1.0';

        $this->plugin_ver = $ver;
    }

    /**
     *
     */
    public  function run() {

        /**
         *
         *  @since 1.3.3
         */
        if( version_compare( $this->option_ver, '1.3.3', '<=' ) ) {

            if( !isset($this->option['post_status']) ) $this->option['post_status'] = [];

            if( isset($this->option['publish_post']) ){
                $this->option['post_status']['publish'] = [];
                foreach( $this->option['publish_post'] as $role => $v ){
                    $this->option['post_status']['publish'][] = $role;
                }
                unset( $this->option['publish_post'] );
            }

            if( isset($this->option['pending_post']) ){
                $this->option['post_status']['pending'] = [];
                foreach( $this->option['pending_post'] as $role => $v ){
                    $this->option['post_status']['pending'][] = $role;
                }
                unset( $this->option['pending_post'] );
            }

            if( !empty($this->option['comments']) ){
                if( $this->option['comments'] == '1' ) $this->option['comments'] = 'yes';
            }

            if( !empty($this->option['user_register']) ){
                if( $this->option['user_register'] == '1' ) $this->option['user_register'] = 'yes';
            }

            if( !empty($this->option['woocommerce']) ){
                if( $this->option['woocommerce'] == '1' ) {
                    $this->option['woo_order'] = 'yes';
                }
                unset( $this->option['woocommerce'] );
            }

            if( isset($this->option['woocommerce_tpl']) ){
                $this->option['woo_tpl'] = $this->option['woocommerce_tpl'];
                unset( $this->option['woocommerce_tpl'] );
            }

            if( !isset($this->option['wpcf7_form']) ) $this->option['wpcf7_form'] = [];
            if( !empty($this->option['wpcf7']) ){
                foreach( $this->option['wpcf7'] as $id=>$val ){
                    $this->option['wpcf7_form'][] = $id;
                }
                unset( $this->option['wpcf7'] );
            }

            if( !empty($this->option['elementor_form']) ){
                if( $this->option['elementor_form'] == '1' ) $this->option['elementor_form'] = 'yes';
            }

            update_option( $this->option_name, $this->option );
        }

        /**
         *  update version number
         */
        if( version_compare( $this->option_ver, $this->plugin_ver, '<' ) ) {
            $this->option['version'] = $this->plugin_ver;
            update_option( $this->option_name, $this->option );
        }

    }

}