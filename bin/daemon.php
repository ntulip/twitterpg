#!/usr/bin/php
<?php
require_once(dirname(__FILE__) . '/../conf/twitterpg.conf.php');
require_once(dirname(__FILE__) . '/../classes/xmpphp/xmpp.php');

define('DAEMON_LISTENING', 'listening');
define('DAEMON_TRANSFERRING', 'transferring');
define('DAEMON_READY', 'ready');


$conn = new XMPP(XMPP_HOST, XMPP_PORT, XMPP_USERNAME, XMPP_PASSWORD, 'xmpphp', XMPP_SERVER, false);

// was going to go all oop on this bitch, but it just doesn't make much sense anymore.
// Procedural programming FTW!

$conn->connect();
$conn->processUntil('session_start');
$conn->presence(DAEMON_READY);

$first_daemon = false;
$processing = false;
$awaiting_transfer = false;
$transfer_pending = false;
$me = explode('/', $conn->fulljid);

while (!$conn->disconnected) {
	foreach ($conn->processUntil(array('message','presence')) as $ev) {
		$type = $ev[0];
		$pl = $ev[1];
		switch ($type) {
			'presence':
				$other = explode('/', $pl['from']);
				if ( $other[0] === $me[0] && $other[1] !== $me[1] ) {
					// a sibling daemon!
					if (!$processing && $pl['status'] === DAEMON_LISTENING) {
						$awaiting_transfer = true;
					} elseif (!$processing && $pl['status'] === DAEMON_TRANSFERRING) {
						$first_daemon = true;
						$processing = true;
					} elseif ($processing && $pl['status'] === DAEMON_READY) {
						$transfer_pending = true;
						$conn->presence(DAEMON_TRANSFERRING);
					} elseif ($processing && $pl['status'] === DAEMON_LISTENING ) {
						// something strange happened.
						// two listeners at once!
						error_log('Magic in daemon process. Two alive at once!');
						exit(1);
					}
				}
			break;
			'message':
				if (!trim($pl['body'])) {
					continue;
				}
				// a message before seeing the presence of another daemon means
				// that we're most likely the first one.  This is a race condition,
				// but an unlikely one to lose.
				// Better to create duplicate actions than have actions not go through.
				if (!$awaiting_transfer) {
					$processing = true;
				}
				
				// clean up the message a bit...
				if ($pl['from'] === "twitter@twitter.com") {
					$message = explode("\n", $pl['body']);
					$user = trim(str_replace(array('Direct from',':'),'',$message[0]));
					$command = $message[1];
				} else {
					$user = explode('/', $pl['from']);
					$user = $user[0];
					$command = $body;
				}
				
				$dispatch_args = json_encode(array('user'=>$user,'command'=>$command));
				
				$dispatch = dirname(__FILE__) . "/dispatch.php '$dispatch_args' &";
				`$dispatch`;
			break;
		}
	}
	if ($transfer_pending) {
		$conn->disconnect();
	}
	if ($first_daemon) {
		$conn->presence(DAEMON_LISTENING);
		$first_daemon = false;
	}
}

?>