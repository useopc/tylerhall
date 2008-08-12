#!/usr/bin/php
<?PHP
	// Places a call using your GrandCentral account.
	// Usage: gcdialer.php <your_phone_number> <their_phone_number>

	require 'class.grandcentral.php';

	$you  = $argv[1];
	$them = $argv[2];

	$gc = new GrandCentral('gc_username', 'gc_password');
	$gc->call($you, $them);