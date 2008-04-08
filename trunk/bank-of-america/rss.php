<?PHP
	// Add your Bank of America signon information here...
	$username   = '<online id>';
	$password   = '<passcode>';
	$account_id = 0; // Typically, 0 is checking and 1 is savings
	$challenges = array('<key1>' => '<answer1>', '<key2>' => '<answer2>', '<key3>' => '<answer3>');

	// Grab the challenge question
	do_curl('https://www.bankofamerica.com/mobile/iphone.do');
	do_curl('https://sitekey.bankofamerica.com/sas/signonScreen.do?isMobileDevice=true');
	$html = do_curl('https://sitekey.bankofamerica.com/sas/signonMobile.do', 'nextAction=screen&customer_Type=MODEL&reason=&portal=&history=&cache=&dltoken=&pmbutton=false&onlineID=' . $username . '&rembme=Y');

	// Answer the challenge question
	$found = false;
	foreach($challenges as $question => $answer)
	{
		if(strpos($html, $question) !== false)
		{
			$found = true;
			break;
		}
	}
	if(!$found) die("We couldn't answer the challenge question!");
	do_curl('https://sitekey.bankofamerica.com/sas/challengeQandAMobile.do', 'nextAction=verify&sitekeyChallengeAnswer=' . $answer . '&sitekeyDeviceBind=false');

	// Enter our password
	$html = do_curl('https://sitekey.bankofamerica.com/sas/verifyImageMobile.do', 'nextAction=signon&passcode=' . $password);

	// Grab the cipher hex
	$cipher = match('/<input.*?CIPHER.*?value="(.*?)"/', $html, 1);
	$iv     = match('/<input.*?IV.*?value="(.*?)"/', $html, 1);
	$sid    = match('/<input.*?sessionid.*?value="(.*?)"/', $html, 1);
	$action = match('/action="(.*?)"/', $html, 1);
	$html   = do_curl($action, "CIPHER_TEXT_IN_HEX=$cipher&IV=$iv&sessionid=$sid");

	// Grab link to main page
	$domain = match('/(.*?)cgi/', $action, 1);
	$href   = $domain . match('/\/(cgi-bin.*?Accounts)/', $html, 1);
	$html   = do_curl($href);

	// Grab link to account details page
	preg_match_all('/cgi-bin.*?accountIndex=[0-9]/', $html, $matches);
	$href = $domain . $matches[0][$account_id];
	$html = do_curl($href);

	// Grab account info
	$rss_title = match('/<title>(.*?)</', $html, 1);
	$rss_desc  = match('/<h3>(.*?)</', $html, 1);
	$balance   = trim(match('/Avail Bal:(.*)/', $html, 1));	

	if(isset($_GET['type']) && ($_GET['type'] == 'pending'))
	{
		$type  = 'Pending';
		$trans = array();
		$href  = match('/\/(cgi-bin.*?Pending)/', $html, 1);
		$phtml = do_curl($domain . $href);
		preg_match_all('/<br>(.*?)Pending.*?(-?\$[0-9]+\.[0-9][0-9])/ms', $phtml, $matches);
			$trans[] = array('description' => trim(strip_tags($matches[1][$i])), 'amount' => $matches[2][$i], 'date' => date('Y-m-d'));
	}
	else
	{
		$type  = 'Cleared';
		$trans = array();
		$href  = match('/\/(cgi-bin.*?Cleared)/', $html, 1);
		$chtml = do_curl($domain . $href);
		preg_match_all('/txnid=[0-9]+">(.*?)<\/a>.*?([0-9][0-9]\/[0-9][0-9]\/[0-9][0-9][0-9][0-9]).*?(-?\$[0-9]+\.[0-9][0-9])/ms', $chtml, $matches);
		for($i = 0; $i < count($matches[1]); $i++)
			$trans[] = array('description' => trim(strip_tags($matches[1][$i])), 'date' => $matches[2][$i], 'amount' => $matches[3][$i]);
	}

	// And build the RSS feed
	$out  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	$out .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
	$out .= "<channel>\n";
	$out .= "<title>$rss_title $type</title>\n";
	$out .= "<link>https://www.bankofamerica.com</link>\n";
	$out .= "<description>$rss_desc</description>\n";
	$out .= "<language>en-us</language>\n";
	$out .= "<pubDate>" . date("D, d M Y H:i:s O", strtotime($posted[1][$i] . " 12:00pm")) . "</pubDate>\n";
	$out .= "</channel>\n";

	foreach($trans as $t)
	{
		$out .= "<item>\n";
		$out .= '<title>' . $t['description'] . '(' . $t['amount'] . ")</title>\n";
		$out .= "<link>https://www.bankofamerica.com</link>\n";
		$out .= "<description><![CDATA[ " . str_replace("&nbsp;", "", $t['description']) . "<br/>" . $t['amount'] . " ]]></description>\n";
		$out .= "<pubDate>" . date("D, d M Y H:i:s O", strtotime($t['date'] . " 12:00pm")) . "</pubDate>\n";
		$out .= "</item>\n";
	}

	$out .= '</rss>';

	// Output the results
	header('Content-type: application/xml');
	echo $out;

	function do_curl($url, $post = null)
	{
		static $tmp     = null;
		static $referer = "";

		if(is_null($tmp))
			$tmp = tempnam(sys_get_temp_dir(), 'boa');
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_2; en-us) AppleWebKit/525.13 (KHTML, like Gecko) Version/3.1 Safari/525.13");
		curl_setopt($ch, CURLOPT_COOKIEFILE, $tmp);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $tmp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
		if(!is_null($post))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		ob_start();
		curl_exec($ch);
		$html = ob_get_contents();
		ob_end_clean();
		
		$referer = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		return $html;
	}

	// Quick wrapper for preg_match
	function match($regex, $str, $i = 0)
	{
		return preg_match($regex, $str, $match) == 1 ? $match[$i] : false;
	}