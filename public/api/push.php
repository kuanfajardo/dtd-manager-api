<?php
/**
 * Created by PhpStorm.
 * User: juanfajardo
 * Date: 9/11/16
 * Time: 3:59 PM
 */


// Put your device token here (without spaces):
$deviceToken = '7cbaa4af1e126787fb83d460ec425b670d591792318be6981c806b3cb535524f';

// Put your private key's passphrase here:
$passphrase = 'dtdpush';

$message = $argv[1];
$type = $argv[2];

if (!$message)
    exit('fail');

if (!$type)
    $type = "GENERIC_NOTIFICATION";

////////////////////////////////////////////////////////////////////////////////

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
$fp = stream_socket_client(
    'ssl://gateway.sandbox.push.apple.com:2195', $err,
    $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp)
    exit("Failed to connect: $err $errstr" . PHP_EOL);

echo 'Connected to APNS' . PHP_EOL;

// Create the payload body
$body = array(
    'aps' => array(
        'alert' => $message,
        'sound' => 'default',
        'badge' => 0
    ),
    'type' => $type
);

// Encode the payload as JSON
$payload = json_encode($body);
echo $payload . PHP_EOL     ;

// Build the binary notification
$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
    echo 'Message not delivered' . PHP_EOL;
else
    echo 'Message successfully delivered' . PHP_EOL;

// Close the connection to the server
fclose($fp);
