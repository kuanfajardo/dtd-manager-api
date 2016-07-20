<?php
if(count($argv) > 1) {
	$url = 'http://localhost/dtd/public/extras.php';
	$data = array('pw' => 'tgjEUiuNintJuWuBn19N9QEMIPgOYQl', 'req' => $argv[1]);

	// use key 'http' even if you send the request to https://...
	$options = array(
	    'http' => array(
	        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	        'method'  => 'POST',
	        'content' => http_build_query($data),
	    ),
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	echo $result;
}
?>