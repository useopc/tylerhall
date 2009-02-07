<?PHP
	// Still a work in progress
	class QTMovie
	{
		public $refs;

		function __construct()
		{
			$this->refs = array();
		}

		// URL of movie resource
		// Data rate in kbps
		// CPU speed is a value 0 to 1000, with 1000 being a fast CPU
		// Quality tie-breaker if everything else is equal. 0 to 1000.
		function addRef($url, $rate = 38400, $cpu = 500, $quality = 500)
		{
			$this->refs[] = array("url" => $url, "rate" => $rate, "cpu" => $cpu, "quality" => $quality);
		}

		function __toString()
		{
			$atoms = "";
			
			foreach($this->refs as $ref)
			{
				// URL
				$url  = pack("N", strlen($ref['url']) + 20); // Size
				$url .= "rdrf";
				$url .= pack("N", 0);
				$url .= "url ";
				$url .= pack("N", strlen($ref['url']));
				$url .= $ref['url'];

				// Rate
		        $rate  = pack("N", 16); // Size
		        $rate .= "rmdr";
		        $rate .= pack("N", 0);
		        $rate .= pack("N", $ref['rate']);

				// CPU
		        $cpu  = pack("N", 16); // Size
		        $cpu .= "rmcs";
		        $cpu .= pack("N", 0);
		        $cpu .= pack("N", $ref['cpu']);

				// Quality
				$q  = pack("N", 16);
				$q .= "rmqu";
				$q .= pack("N", 0);
				$q .= pack("N", $ref['quality']);

				// Combine atoms into descriptor atom
				$ref  = pack("N", strlen($ref['url']) + 20 + 16 + 16 + 16);
				$ref .= "rmda";
				$ref .= $url . $rate . $cpu . $q;
				
				$atoms .= $ref;
			}

			$molecule  = pack("N", strlen($atoms) + 8);
			$molecule .= "rmra";
			$molecule .= $atoms;

			$str  = pack("N", strlen($molecule) + 8);
			$str .= "moov";
			$str .= $molecule;

			return $str;
		}
	}

	
	$qt = new QTMovie();
	$qt->addRef("http://audio-mp3.ibiblio.org:8000/wcpe.mp3", 6400, 500);

	header("Content-type: video/quicktime");
	echo $qt;