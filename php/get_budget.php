<?
require_once(__DIR__ . '/include.php');

$post_body = file_get_contents('php://input');
$post_data = json_decode($post_body, true);
$request_headers = getallheaders();
$auth_header_key = 'Authorization';

if (!array_key_exists($auth_header_key, $request_headers)) {
  print("Missing Authorization header");
  http_response_code(401);
  exit();
}

[ $auth_header_key => $auth_header ] = $request_headers;


$auth_header_prefix = 'Bearer ';

if (str_starts_with($auth_header, $auth_header_prefix)) {
  $api_key = substr($auth_header, strlen($auth_header_prefix));
} else {
  $api_key = null;
}

$budget = get_budget($api_key);

print($budget);
