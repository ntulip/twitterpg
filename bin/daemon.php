#!/usr/bin/php
<?php
// The Daemon that listens, dispatches, and gracefully fails over.
// Considered going all OOP on this bitch; just didn't make sense in this case.
// 99 lines, with this comment.  Procedural programming FTW!

require_once(dirname(__FILE__) . '/../conf/twitterpg.conf.php');
require_once(dirname(__FILE__) . '/../classes/xmpphp/xmpp.php');

define('DAEMON_LISTENING', 'listening');
define('DAEMON_TRANSFERRING', 'transferring');
define('DAEMON_READY', 'ready');
define('PROCESS_TIMEOUT',10);
define('DEBUG', true);

$conn = new XMPP(XMPP_HOST, XMPP_PORT, XMPP_USERNAME, XMPP_PASSWORD, 'trnd', XMPP_SERVER, DEBUG, LOGGING_INFO);

function connect () {
	global $conn;
	$conn->connect();
	$conn->processUntil('session_start',PROCESS_TIMEOUT);
	return true;
}

$first_daemon = true;
$processing = false;
$awaiting_transfer = false;
$transfer_pending = false;
$magic = false;
for (
		connect(), $conn->presence(DAEMON_READY), $me = explode('/', $conn->fulljid);
		!$conn->disconnected;
		$evs = $conn->processUntil(array('message','presence'),PROCESS_TIMEOUT)) {

	if (is_array($evs)) foreach ($evs as $ev) {
		$type = $ev[0];
		$pl = $ev[1];
		switch ($type) {
			case 'presence':
				$other = explode('/', $pl['from']);
				if ( $other[0] === $me[0] && @$other[1] !== $me[1] ) {
					// a sibling daemon!
					// echo "sibling! " . implode(" ", $pl);
					$first_daemon = false;
					if (!$processing && $pl['status'] === DAEMON_LISTENING) {
						$awaiting_transfer = true;
					} elseif (!$processing && $pl['status'] === DAEMON_TRANSFERRING) {
						$processing = true;
						$conn->presence(DAEMON_LISTENING);
					} elseif ($processing && $pl['status'] === DAEMON_READY) {
						$transfer_pending = true;
						$conn->presence(DAEMON_TRANSFERRING);
					} elseif ($processing && $pl['status'] === DAEMON_LISTENING ) {
						if ($magic) {
							error_log('Magic in TRN daemon process. Two alive at once!');
							exit(1);
						} else {
							$magic = true; // if other doesn't go away, then abort.
						}
					}
				}
			break;
			case 'message':
				if (!trim($pl['body'])) {
					break;
				}
				
				// a message before seeing another daemon. Most likely the first one.
				// This is a race condition.  Dupes are better than lost actions.
				if (!$awaiting_transfer) {
					$processing = true;
				}
				if ($pl['from'] === "twitter@twitter.com") {
					$message = explode("\n", $pl['body']);
					$user = trim(str_replace(array('Direct from',':'),'',$message[0]));
					$command = $message[1];
				} else {
					$user = explode('/', $pl['from']);
					$user = $user[0];
					$command = $pl['body'];
				}
				$dispatch_args = json_encode(array('user'=>$user,'command'=>$command));
				$dispatch = dirname(__FILE__) . "/dispatch.php '$dispatch_args'";
				DEBUG ? print(`$dispatch`) : `$dispatch`;
			break;
		}
	}
	if ($transfer_pending) {
		$conn->disconnect();
	} elseif ($conn->disconnected) {
		connect();
	}
	if ($first_daemon) {
		$conn->presence(DAEMON_LISTENING);
		$first_daemon = false;
		$processing = true;
	}
}
?>