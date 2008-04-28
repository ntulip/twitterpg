#!/usr/bin/php
<?php
/**
 * Jabber testing script
 *
 * Execute this script from a terminal window
 * Send a Jabber IM to tpg_tester@jabber.org
 * Profit!
 * 
 * Send "quit" to end the script
 */

// Test Comment for git commit

include("xmpphp/xmpp.php");

// test account information
$host = 'jabber.org';
$port = 5222;
$username = 'tpg_tester';
$password = 'tpgrox';
$resource = 'xmpphp';
$server = null;

$xmpp = new XMPP($host, $port, $username, $password, $resource, $server, true, LOGGING_INFO);
$xmpp->connect();

while (!$xmpp->disconnected) {
	$payloads = $xmpp->processUntil(array('message', 'presence', 'end_stream', 'session_start'));
	foreach ($payloads as $event) {
		$pl = $event[1];
		switch($event[0]) {
			case 'message':
				echo "---------------------------------------------------------------------------------\n";
				echo "Message from: {$pl['from']}\n";
				if ($pl['subject']) {
					echo "Subject: {$pl['subject']}\n";
				} 
				echo $pl['body'] . "\n";
				echo "---------------------------------------------------------------------------------\n";
				$xmpp->message($pl['from'], "Thanks for sending me \"{$pl['body']}\".", $pl['type']);
				if ($pl['body'] == 'quit') {
					$xmpp->disconnect();
				}
				if ($pl['body'] == 'break') {
					$xmpp->send("</end>");
				}
				break;
			case 'presence':
				echo "Presence: {$pl['from']} [{$pl['show']}] {$pl['status']}\n";
				break;
			case 'session_start':
				$xmpp->presence('Ready to roxorz!');
				break;
		}
	}
}

unset($xmpp);

?>
