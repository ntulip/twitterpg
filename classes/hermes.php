<?php

/*

Hermes, the messenger
this guy sends messages.

send(MESSAGE to TARGET)
	if TARGET not in QUEUE in persistent data, ERROR
	if TARGET is:
		http: POST to TARGET with MESSAGE as post-body
		file/socket: write MESSAGE to TARGET
	return SUCCESS

*/

class Hermes {
	private function __construct () {}
	private static isUrl ($url) {
		return preg_match('~^[a-z]+://~', $url);
	}
	public static function send ($message, $target, $method = 'GET') {
		if ( self::isUrl($url ) {
			// send via curl
			
	}
}

?>