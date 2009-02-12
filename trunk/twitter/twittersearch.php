#!/usr/bin/php
<?PHP
	// What we're searching for
	$terms = array('search', 'terms', 'go', 'here');

	// Create a logfile in our home directory...
	define('LOGFILE', $_ENV['HOME'] . '/.twittersearchlog');
	if(!file_exists(LOGFILE)) touch(LOGFILE);

	// Search!...
	foreach($terms as $t)
	{
		search($t);
		sleep(1);
	}

	function search($q)
	{
		// Grab the results from Twitter...
		$url = 'http://search.twitter.com/search.atom?q=' . urlencode($q);
		$xmlstr = file_get_contents($url);
		$xml = simplexml_load_string($xmlstr);

		// Loop over each one...
		foreach($xml->entry as $result)
		{
			// Make sure we haven't already reported it...
			$cmd = sprintf('/usr/bin/grep %s %s | wc -l', escapeshellarg($result->link['href']), LOGFILE);
			if(trim(shell_exec($cmd)) == '0')
			{
				// Log the result
				file_put_contents(LOGFILE, $result->link['href'] . "\n", FILE_APPEND);

				// echo "Re: $q, {$result->author->name} said\n{$result->title}\n\n";

				// And notify the user using Growl...
				$growl = sprintf('/usr/bin/growlnotify -m %s -t %s', escapeshellarg($result->title), escapeshellarg("[$q] {$result->author->name} said"));
				shell_exec($growl);
			}
		}
	}
