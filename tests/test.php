#!/usr/bin/php
<?php

$input_handle = fopen('php://stdin','r');

echo "Hello, I'm Hermes. Who are you?\n";

$name = "isaac"; //stream_get_line($input_handle, 100, "\n");

echo "You appear to be on: " . trim(`tty`) . ".\n";

$command = "";
while (strtolower($command) !== 'q') {
	echo "Hello, $name.\nEnter a command:\n";

	echo "E: show Environment\n";
	echo "S: show Server\n";
	echo "G: show Globals\n";
	echo "Q: quit\n";
	
	$command = stream_get_line($input_handle, 100, "\n");
	$command = strtolower(substr($command,0,1));
	process($command);
}

function process ($command) {
	switch ($command) {
		case 'e':
			var_dump($_ENV);
		break;
		case 's':
			var_dump($_SERVER);
		break;
		case 'g':
			var_dump($_GLOBALS);
		break;
	}
}

echo "Goodbye!\n";
?>