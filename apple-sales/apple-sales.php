<?PHP
	// This script logs into iTunes Connect (https://itunesconnect.apple.com/) and
	// downloads your most recent daily sales data in CSV (tab delimited) format.
	// Your data is written to stdout.
	//
	// Obviously, we're scraping Apple's website, so this could break at any time.
	// This script was last successfully tested on 10/29/2008.
	//
	// (It's totally possible that there's an Apple approved way of automatically
	//  downloading your sales data, but if there is, I can't find it.)

	$un = '<your username>';
	$pw = '<your password>';

	do_curl('https://phobos.apple.com/WebObjects/MZLabel.woa/wa/default');
	do_curl('https://phobos.apple.com/WebObjects/MZLabel.woa/wo/0.0.5.3.3.1.0.1', 'theAccountName=' . urlencode($un) . '&theAccountPW=' . urlencode($pw) . '&1.Continue.x=0&1.Continue.y=0&theAuxValue=');

	$html = do_curl('https://itts.apple.com/cgi-bin/WebObjects/Piano.woa');
	$action = 'https://itts.apple.com' . match('/frmVendorPage(\'|").*?(\/cgi-bin.*?)(\'|")/ms', $html, 2);

	$html = do_curl($action, '9.5=Summary&9.7=Daily&hiddenDayOrWeekSelection=Daily&hiddenSubmitTypeName=ShowDropDown');
	$date = match('/value=(\'|")([0-9][0-9]\/[0-9][0-9]\/[0-9]{4})/ms', $html, 2);
	$action = 'https://itts.apple.com' . match('/frmVendorPage(\'|").*?(\/cgi-bin.*?)(\'|")/ms', $html, 2);
	
	$gz = do_curl($action, "9.5=Summary&9.7=Daily&9.9.1=" . urlencode($date) . "&download=Download&hiddenDayOrWeekSelection=" . urlencode($date) . "&hiddenSubmitTypeName=Download");
	$fn = tempnam(sys_get_temp_dir(), 'itunes');
	file_put_contents($fn, $gz);
	readgzfile($fn);

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

    function match($regex, $str, $i = 0)
    {
            return preg_match($regex, $str, $match) == 1 ? $match[$i] : false;
    }
