<?PHP
    // CNN Political Ticker RSS Scraper
    // Author: Tyler Hall <tylerhall@gmail.com>
    // Last Modified: September 21, 2007
    // License: MIT Open Source License <http://www.opensource.org/licenses/mit-license.php>

    // Grabs a remote URL
    // Code taken from: http://code.google.com/p/simple-php-framework/
    function geturl($url, $username = "", $password = "")
    {
        if(function_exists("curl_init"))
        {
            $ch = curl_init();
            if(!empty($username) && !empty($password))
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' .  base64_encode("$username:$password")));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $html = curl_exec($ch);
            curl_close($ch);
            return $html;
        }
        elseif(ini_get("allow_url_fopen") == true)
        {
            if(!empty($username) && !empty($password))
                $url = str_replace("://", "://$username:$password@", $url);
            $html = file_get_contents($url);
            return $html;
        }
        else
        {
            // Cannot open url. Either install curl-php or set allow_url_fopen = true in php.ini
            return false;
        }
    }

    // Simple preg_match wrapper
    // Code taken from: http://code.google.com/p/simple-php-framework/
    function match($regex, $str, $i = 0)
    {
        if(preg_match($regex, $str, $match) == 1)
            return $match[$i];
        else
            return false;
    }

    // Serve this page as RSS
    header("Content-type: application/rss+xml");
?>
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
<title>CNN Political Ticker</title>
<link>http://politicalticker.blogs.cnn.com/</link>
<description>The CNN Political Ticker provides the latest political news.</description>
<language>en-us</language>
<pubDate><?PHP echo date("D, d M Y H:i:s O"); ?></pubDate>
<?PHP
    // Serve the cached version if it's been less than five minutes
    if(file_exists("cnn.cache") && filemtime("cnn.cache") >= (time() - 300))
    {
        readfile("cnn.cache");
    }
    else
    {
        // Grab the HTML from CNN.com
        $html = geturl("http://politicalticker.blogs.cnn.com/");
        if($html === false || $html == "")
        {
            echo "<item>";
            echo "<title>Could not load data from CNN</title>";
            echo "<link>{$_SERVER['PHP_SELF']}</link>";
            echo "<guid>{$_SERVER['PHP_SELF']}</guid>";
            echo "<description><![CDATA[ <p>Could not load data from CNN.com.</p> ]]></description>";
            echo "<pubDate>" . date("D, d M Y H:i:s O") . "</pubDate>";
            echo "</item>";
            echo "</channel>";
            echo "</rss>";
            exit();
        }

        // Pull out each news story
        $out = "";
        preg_match_all('/date1.*?comment<\/a><\/div>/ms', $html, $matches);
        foreach($matches[0] as $story)
        {
            // And extract the title, url, body, etc...
            $title = strip_tags(match('/header1">(.*?)<\/div>/ms', $story, 1));
            $title = str_replace("&nbsp;", " ", $title);
            $url   = match('/header1"><a href="(.*?)">/ms', $story, 1);
            $body  = match('/(<p.*?)<\/div>/ms', $story, 1);
            $ts    = match('/timestamp.*?timestamp.*?([0-9].*?M)/ms', $story, 1);
            $ts    = date("D, d M Y H:i:s O", strtotime($ts));

            $out .= "<item>";
            $out .= "<title>$title</title>";
            $out .= "<link>$url</link>";
            $out .= "<guid>$url</guid>";
            $out .= "<description><![CDATA[ $body ]]></description>";
            $out .= "<pubDate>$ts</pubDate>";
            $out .= "</item>";
        }

        // Save data to cache file
        file_put_contents("cnn.cache", $out);

        echo $out;
    }
?>
</channel>
</rss>