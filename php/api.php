<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../include/db.php');

$input_output_token_cost_in_e8s = array(
  'gpt-3.5-turbo-0125' => array(50, 150),
  'gpt-4-turbo-2024-04-09' => array(1000, 3000),
  'gpt-4' => array(3000, 6000),
  'gpt-4-32k' => array(6000, 12000),
);

function str_starts_with($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function get_budget($api_key) {
  $db = dbConnect(GPT_DB);
  $select = dbPrepare(
      $db, 'select amount from Budgets where api_key = ?');
  dbBindParams($db, $select, 's', $api_key);
  dbBindResult($db, $select, $amount);
  dbExec($db, $select);
  $success = $select->fetch();
  $select->close();
  if (!$success) {
    return null;
  }

  return $amount;
}

function get_cost($model, $input_tokens, $output_tokens) {
  global $input_output_token_cost_in_e8s;
  list($input_token_cost, $output_token_cost) = $input_output_token_cost_in_e8s[$model];
  return 
      $input_tokens * $input_token_cost +
      $output_tokens * $output_token_cost;
}

function deduct_budget($api_key, $model, $input_tokens, $output_tokens) {
  $cost = get_cost($model, $input_tokens, $output_tokens);

  $db = dbConnect(GPT_DB);
  $update = dbPrepare(
    $db, 'update Budgets set amount = amount - ? where api_key = ?');
  dbBindParams($db, $update, 'is', $cost, $api_key);
  dbExec($db, $update);
  $update->close();
}

$openai_api_url = 'https://api.openai.com/v1/chat/completions';

$request_headers = getallheaders();

$auth_header_key = 'Authorization';
$content_type_header_key = 'Content-Type';

if (!array_key_exists($auth_header_key, $request_headers)) {
  print("Missing Authorization header");
  http_response_code(401);
  exit();
}

[ $auth_header_key => $auth_header, $content_type_header_key => $content_type_header ] = $request_headers;

$request_headers = array();
$request_headers[$auth_header_key] = $auth_header;
$request_headers[$content_type_header_key] = $content_type_header;

$auth_header_prefix = 'Bearer ';

if (str_starts_with($auth_header, $auth_header_prefix)) {
  $api_key = substr($auth_header, strlen($auth_header_prefix));
} else {
  $api_key = null;
}

if ($api_key !== null) {
  $budget = get_budget($api_key);

  if ($budget > 0) {
    $auth_header = 'Bearer ' .OPENAI_API_KEY;
    $request_headers['Authorization'] = $auth_header;
  }
}

$post_body = file_get_contents('php://input');

$post_data = json_decode($post_body, true);
$model = $post_data['model'];

if (!array_key_exists($model, $input_output_token_cost_in_e8s)) {
  print("Unknown model: $model");
  http_response_code(400);
  exit();
}

$hostname = parse_url($openai_api_url, PHP_URL_HOST);

$request_headers['Host'] = $hostname;

$request_headers_array = array_map(function ($key, $value) {
    return "$key: $value";
}, array_keys($request_headers), $request_headers);

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

$response_data = json_decode($response_body, true);
$input_tokens = $response_data['usage']['prompt_tokens'];
$output_tokens = $response_data['usage']['completion_tokens'];

$cost = get_cost($model, $input_tokens, $output_tokens);
$new_budget = $budget - $cost;
$response_data['usage']['cost'] = $cost;
$response_data['usage']['old_budget'] = $budget;
$response_data['usage']['new_budget'] = $new_budget;

deduct_budget($api_key, $model, $input_tokens, $output_tokens);

http_response_code($status_code);

print(json_encode($response_data));
?>
