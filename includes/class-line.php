<?php

class sig_line_notify {

    const API_URL = 'https://notify-api.line.me/api/';

    private $token;

    public function __construct($token='') {
        $this->token = $token;
    }

    public function send($text='') {

        if ( empty($this->token) ) return __( 'LINE Notify token is required!' , 'wp-line-notify' );

        if ( empty($text) ) return __( 'Plase write something !' , 'wp-line-notify' );

        $request_params = array(
            "headers" => "Authorization: Bearer {$this->token}",
            "body"    => array(
                "message" => "\n{$text}"
            )
        );

        $response = wp_remote_post( self::API_URL.'notify', $request_params );
        $code = wp_remote_retrieve_response_code( $response );
        $message = wp_remote_retrieve_response_message( $response );

        if( $code === 200 ){
            return 'ok';
        }else{
            return $message;
        }

    }

    public function check_token(){

        $request_params = array(
            "headers" => "Authorization: Bearer ".$this->token
        );
        $response = wp_remote_get( self::API_URL.'status', $request_params );
        return ( 200 === wp_remote_retrieve_response_code( $response ) ) ? true : false ;
    }

    public function revoke(){

        $request_params = array(
            "headers" => "Authorization: Bearer ".$this->token
        );

        $response = wp_remote_post( self::API_URL.'revoke', $request_params );
        $code = wp_remote_retrieve_response_code( $response );
        $message = wp_remote_retrieve_response_message( $response );

        echo json_encode(array(
            'rs' => ($code==200) ? true : false,
            'message' => $message
        ));
        die();
    }
}