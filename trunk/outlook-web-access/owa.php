<?PHP
	$owa = new OWA('<username>', '<password>');
	if(isset($_GET['user']))
		$owa->freeBusyICS($_GET['user'], true);

	if(isset($_GET['accept']))
		$owa->accept($_GET['accept']);

	if(isset($_GET['decline']))
		$owa->decline($_GET['decline']);

	if(isset($_GET['tentative']))
		$owa->tentative($_GET['tentative']);

	class OWA
	{
		public $username;
		public $password;
		public $url = "<outlook web access URL>";
		public $domain = "<the part after the @ in your email address>";

		private $tmpfile;

		public function __construct($un, $pw)
		{
			$this->tmpfile = tempnam("/tmp", "owa");
			$this->username = $un;
			$this->password = $pw;
			$this->login();
		}

		// Create a new curl session and login to OWA.
		public function login()
		{
			@unlink($this->tmpfile);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->url . "/exchange/");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->tmpfile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->tmpfile);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1) Gecko/20061024 BonEcho/2.0");
			curl_setopt($ch, CURLOPT_REFERER, $this->url . "/exchweb/bin/auth/owalogon.asp?url={$this->url}/exchange&reason=0");

			$headers = array();
			$headers[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$headers[] = "Accept-Language: en-us,en;q=0.5";
			$headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$html = curl_exec($ch);
			curl_close($ch);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->url . "/exchweb/bin/auth/owaauth.dll");
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->tmpfile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->tmpfile);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1) Gecko/20061024 BonEcho/2.0");
			curl_setopt($ch, CURLOPT_REFERER, $this->url . "/exchweb/bin/auth/owalogon.asp?url={$this->url}/exchange&reason=0");

			$headers = array();
			$headers[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$headers[] = "Accept-Language: en-us,en;q=0.5";
			$headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			curl_setopt($ch, CURLOPT_POST, true);
		 	curl_setopt($ch, CURLOPT_POSTFIELDS, "destination=" . urlencode($this->url) . "%2Fexchange&flags=0&username=" . urlencode($this->username) . "&password=" . urlencode($this->password) . "&SubmitCreds=Log+On&trusted=0");

			$html = curl_exec($ch);

			curl_close($ch);
		}

		// After logging in, grab the contents of an OWA URL.
		function get($url)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->url . $url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->tmpfile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->tmpfile);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1) Gecko/20061024 BonEcho/2.0");
			curl_setopt($ch, CURLOPT_REFERER, $this->url . "/exchweb/bin/auth/owalogon.asp?url={$this->url}/exchange&reason=0");

			$headers = array();
			$headers[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$headers[] = "Accept-Language: en-us,en;q=0.5";
			$headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$html = curl_exec($ch);
			curl_close($ch);
			return $html;
		}

		// Returns Free/Busy schedule for a user on a given day.
		// $ts is a timestamp on the day you want info for. Defaults to current time.
		function availability($user, $ts = null)
		{
			if(is_null($ts)) $ts = time();
			$html = $this->get("/public/?Cmd=freebusy&start=" . date("Y-m-d", $ts) . "T00:00:00-08:00&u=$user@{$this->domain};0");
			preg_match_all('!<tr.*?</tr!msi', $html, $matches);
			$data = $matches[0][4];
			preg_match_all('!#[a-f0-9]{6}!msi', $data, $matches);
			array_shift($matches[0]);
			array_shift($matches[0]);

			list($m, $d, $y) = explode(",", date("m,d,Y", $ts));

			$status = array();
			$choices = array("#99CCFF" => "Tentative", "#0000FF" => "Busy", "#660066" => "Out of Office", "#FFFFFF" => "No Information", "#FFE3A5" => "");
			for($t = mktime(0, 0, 0, $m, $d, $y); $t < mktime(23, 59, 59, $m, $d, $y); $t += 1800)
			{
				$cur = $choices[array_shift($matches[0])];
				if(($cur != "No Information") && ($cur != ""))
					$status[] = array("status" => $cur, "date" => date("Y-m-d H:i:00", $t));
				else
					$status[] = array("status" => "", "date" => date("Y-m-d H:i:00", $t));
			}
			return $status;
		}

		// Builds an iCalendar file of a user's Free/Busy schedule.
		// Can optionally output to a browser as an .ics attachment.
		function freeBusyICS($user, $output = false)
		{
			$info = $this->availability($user);

			$out  = "BEGIN:VCALENDAR\n";
			$out .= "VERSION:2.0\n";
			$out .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";

			for($ts = time() - 86400 * 2; $ts < (time() + 86400 * 8); $ts += 86400)
			{
				$info = $this->availability($user, $ts);
				for($i = 0; $i < count($info); $i++)
				{
					if($info[$i]['status'] != "")
					{
						$out .= "BEGIN:VEVENT\n";
						$out .= "SEQUENCE:4\n";
						$out .= "TRANSP:OPAQUE\n";
						$out .= "UID:" . md5($user . $info[$i]['date']) . "\n";
						$out .= "DTSTART;TZID=US/Pacific:" . date('Ymd\THi00', strtotime($info[$i]['date'])) . "\n";
						$out .= "SUMMARY:" . ucwords($info[$i]['status']) . "\n";

						$end = $info[$i]['date'];
						$j = $i + 1;
						while(isset($info[$j]) && ($info[$j]['status'] == $info[$i]['status']))
						{
							$info[$j]['status'] = "";
							$end = $info[$j]['date'];
							$j++;
						}

						$out .= "DTEND;TZID=US/Pacific:" . date('Ymd\THi00', strtotime($end) + 1800) . "\n";
						$out .= "END:VEVENT\n";
					}
				}
			}

			$out .= "END:VCALENDAR\n";

			if($output)
			{
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Disposition: attachment; filename=$user.ics");
				header("Content-Length: " . strlen($out));
				header("Content-type: text/calendar");
				echo $out;
			}
			else
				return $out;
		}

		// Accepts an event. You can pass in the event's OWA URL or an email
		// message on disk containing the link (for Applescript tie-ins).
		function accept($url)
		{
			if(substr($url, 0, 4) != "http") $url = $this->extractURLFromMessage($url);
			$url = preg_replace('!^.*?(/Exchange.*)$!', "$1", $url);
			$url = str_replace("?cmd=open", "?Cmd=accept", $url);
			$this->get($url);
		}

		// Declines an event. You can pass in the event's OWA URL or an email
		// message on disk containing the link (for Applescript tie-ins).
		function decline($url)
		{
			if(substr($url, 0, 4) != "http") $url = $this->extractURLFromMessage($url);
			$url = preg_replace('!^.*?(/Exchange.*)$!', "$1", $url);
			$url = str_replace("?cmd=open", "?Cmd=decline", $url);
			$this->get($url);
		}

		// Tentatively accepts an event. You can pass in the event's OWA URL or an email
		// message on disk containing the link (for Applescript tie-ins).
		function tentative($url)
		{
			if(substr($url, 0, 4) != "http") $url = $this->extractURLFromMessage($url);
			$url = preg_replace('!^.*?(/Exchange.*)$!', "$1", $url);
			$url = str_replace("?cmd=open", "?Cmd=tentative", $url);
			$this->get($url);
		}
		
		// Extracts the event's OWA link from a message on disk.
		function extractURLFromMessage($fn)
		{
			$file = file_get_contents($fn);
			preg_match('!Microsoft Outlook Web Access.*?(http://.*?open)!ms', $file, $matches);
			$url = str_replace("=\n", "", $matches[1]);
			return $url;
		}
	}