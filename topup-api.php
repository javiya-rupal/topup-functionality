<?php 
ini_set('max_execution_time', 0); // prevents 30 seconds from being fatal
$ch = curl_init('http://127.0.0.1/topup-api/?action=getBalance&number=2147483647');

curl_setopt($h, CURLOPT_USERPWD, $username . ":" . $password);  
curl_setopt(
    $ch, 
    CURLOPT_HTTPHEADER,
    array(
        'X-Parse-Application-Id: myApplicationID',
        'X-Parse-REST-API-Key: myRestAPIKey',
        'Accept:application/xml'
    )
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$resp = curl_exec($ch);
if ($error_number = curl_errno($ch)) {
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (in_array($httpCode, array(408, 500, 404) {
        print "curl timed out";
    }
}
curl_close($ch);
echo $resp;