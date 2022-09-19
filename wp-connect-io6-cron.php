<?php

$currentPage = 1;
$totalPages = 1;

$totalRows = -1;

$callUrl = isset($argv) && count($argv) > 1 ? $argv[1] : '';

if($callUrl != '') {
  $contents = '';

  while ($currentPage <= $totalPages) {
    try { 
			$callUrl .= '&page=' . $currentPage;
			$results = get_web_page($callUrl);    
			
			$totalPages = $results['pages'];
			
			echo sprintf('Totale prodotti: %s. Pagine: %s di %s'. PHP_EOL, $results['elementsFounds'], $currentPage , $totalPages);
			$currentPage++;
			
		}
		catch(Exception $e) {
			$totalPages=1;
		}
  } 
	echo 'Update from ImporterONE Cloud terminata' . PHP_EOL;
}
else
  echo 'Url plugin non impostato!';



function get_web_page($url)
{
  $options = array(
    CURLOPT_RETURNTRANSFER => true,   // return web page
    CURLOPT_HEADER         => false,  // don't return headers
    CURLOPT_FOLLOWLOCATION => true,   // follow redirects
    CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
    CURLOPT_ENCODING       => "",     // handle compressed
    CURLOPT_USERAGENT      => "ImporterONE", // name of client
    CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
    CURLOPT_CONNECTTIMEOUT => 1200,    // time-out on connect
    CURLOPT_TIMEOUT        => 1200,    // time-out on response
    CURLOPT_URL, $url 
  );

  $ch = curl_init($url);

  curl_setopt_array($ch, $options);

  // $output contains the output string
  $content = curl_exec($ch);
  if (curl_errno($ch))
				 $output = curl_errno($ch) . " " . curl_error($ch);
			else
				$output =	json_decode($content, true);
			curl_close($ch);

  return $output;
}

?>