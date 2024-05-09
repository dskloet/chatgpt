<?php
require_once('config.php');

$openai_api_url = 'https://api.openai.com/v1/chat/completions';

$request_headers = getallheaders();

$auth_header_key = 'Authorization';

if (!array_key_exists($auth_header_key, $request_headers)) {
  print("Missing Authorization header");
  http_response_code(403);
  exit();
}

[ $auth_header_key => $auth_header ] = $request_headers;

if ($auth_header == 'Bearer ' . DSKL_API_KEY) {
  $auth_header = 'Bearer ' .OPENAI_API_KEY;
}

$hostname = parse_url($openai_api_url, PHP_URL_HOST);

$request_headers['Authorization'] = $auth_header;
$request_headers['Host'] = $hostname;

$request_headers_array = array_map(function ($key, $value) {
    return "$key: $value";
}, array_keys($request_headers), $request_headers);

$post_body = file_get_contents('php://input');

$curl = curl_init($openai_api_url);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers_array);

$response = curl_exec($curl);

$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

$response_headers_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
$response_headers = explode("\n", substr($response, 0, $response_headers_size));
$response_body = substr($response, $response_headers_size);

curl_close($curl);

http_response_code($status_code);

foreach ($response_headers as $header) {
    if (strstr($header, ':')) {
      header("$header");
    }
}

print($response_body);
?>
