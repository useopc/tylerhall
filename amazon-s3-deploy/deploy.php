<?PHP
	// This is a cleaned up version of the deploy script I use.
	// In other words, this exact file hasn't been tested in production,
	// it's based off of another working copy. So, you can try using
	// it directly, but it might be best to just concentrate on the ideas
	// here and design one that fits your own custom needs. --Tyler
	//
	// This script assumes that *all* of your images, css, and js files are
	// in /images, /css, /js, respectively. YMMV.
	
	require 'class.s3.php';

	define('DOC_ROOT', '/path/to/your/website/'); // Include trailing slash
	define('IMG_PATH', 'images/');
	define('JS_PATH', 'js/');
	define('CSS_PATH', 'css/');

	define('S3_KEY', 'S3 Key');
	define('S3_PKEY', 'S3 Private Key');
	define('S3_BUCKET', 'S3 Bucket Name');

	// Create a connection to Amazon S3
	$s3 = new S3(S3_KEY, S3_PKEY);

	// Set our expiry header time to a week from now.
	$expires = date('D, j M Y 23:59:59', time() + (86400 * 7)) . ' GMT';

	/* UPLOAD IMAGES */
	$files = scandir(DOC_ROOT . IMG_PATH);
	foreach($files as $fn)
	{
		if(!in_array(substr($fn, -3), array('jpg', 'png', 'gif'))) continue;

		$object   = IMG_PATH . $fn;
		$the_file = DOC_ROOT . IMG_PATH . $fn;

		// Only upload if the file is different
		if(!$s3->objectIsSame($bucket, $object, md5_file($the_file)))
		{
			echo "Putting $the_file . . . ";
			if($s3->putObject($bucket, $object, $the_file, true, null, array('Expires' => $expires)))
				echo "OK\n";
			else
				echo "ERROR!\n";
		}
		else
		{
			echo "Skipping $the_file\n";
		}
	}

	/* COMBINE & UPLOAD STYLESHEETS */
	// List your stylesheets here for concatenation...
	$css  = file_get_contents(DOC_ROOT . CSS_PATH . 'reset-fonts-grids.css') . "\n\n";
	$css .= file_get_contents(DOC_ROOT . CSS_PATH . 'screen.css') . "\n\n";
	$css .= file_get_contents(DOC_ROOT . CSS_PATH . 'jquery.lightbox.css') . "\n\n";
	$css .= file_get_contents(DOC_ROOT . CSS_PATH . 'syntax.css') . "\n\n";

	file_put_contents(DOC_ROOT . CSS_PATH . 'combined.css', $css);
	shell_exec('gzip -c ' . DOC_ROOT . CSS_PATH . 'combined.css > ' . DOC_ROOT . CSS_PATH . 'combined.gz.css');

	if(!$s3->objectIsSame($bucket, CSS_PATH . 'combined.css', md5_file(DOC_ROOT . CSS_PATH . 'combined.css')))
	{
		echo "Putting combined.css...";
		if($s3->putObject($bucket, CSS_PATH . 'combined.css', DOC_ROOT . CSS_PATH . 'combined.css', true, null, array('Expires' => $expires)))
			echo "OK\n";
		else
			echo "ERROR!\n";

		echo "Putting combined.gz.css...";
		if($s3->putObject($bucket, CSS_PATH . 'combined.gz.css', DOC_ROOT . CSS_PATH . '/combined.gz.css', true, null, array('Expires' => $expires, 'Content-Encoding' => 'gzip')))
			echo "OK\n";
		else
			echo "ERROR!\n";
	}
	else
		echo "Skipping combined.css\n";

	/* COMBINE & UPLOAD JAVASCRIPT */
	$js  = file_get_contents(DOC_ROOT . JS_PATH . 'jquery.js') . "\n\n";
	$js .= file_get_contents(DOC_ROOT . JS_PATH . 'jquery.lightbox.js') . "\n\n";
	$js .= file_get_contents(DOC_ROOT . JS_PATH . 'shCore.js') . "\n\n";
	$js .= file_get_contents(DOC_ROOT . JS_PATH . 'shBrushPhp.js') . "\n\n";

	file_put_contents(DOC_ROOT . JS_PATH . 'combined.js', $js);
	shell_exec('gzip -c ' . DOC_ROOT . JS_PATH . 'combined.js > ' . DOC_ROOT . JS_PATH . 'combined.gz.js');

	if(!$s3->objectIsSame($bucket, JS_PATH . 'combined.js', md5_file(DOC_ROOT . JS_PATH . 'combined.js')))
	{
		echo "Putting combined.js...";
		if($s3->putObject($bucket, JS_PATH . 'combined.js', DOC_ROOT . JS_PATH . 'combined.js', true, null, array('Expires' => $expires)))
			echo "OK\n";
		else
			echo "ERROR!\n";

		echo "Putting combined.gz.js...";
		if($s3->putObject($bucket, JS_PATH . 'combined.gz.js', DOC_ROOT . JS_PATH . 'combined.gz.js', true, null, array('Expires' => $expires, 'Content-Encoding' => 'gzip')))
			echo "OK\n";
		else
			echo "ERROR!\n";
	}
	else
		echo "Skipping combined.js\n";