<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../include/db.php');

$input_output_token_cost_in_e8s = array(
  'gpt-3.5-turbo-0125' => array(50, 150),
  'gpt-4o-mini-2024-07-18' => array(15, 60),
  'gpt-4o-2024-08-06' => array(250, 1000),
  'gpt-4-turbo-2024-04-09' => array(1000, 3000),
  'gpt-4' => array(3000, 6000),
  'gpt-4-32k' => array(6000, 12000),
);

function str_starts_with($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function get_budget($api_key) {
  if ($api_key === null) {
    return 0;
  }
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

function deduct_budget($api_key, $model, $input_tokens, $output_tokens) {
  $cost = get_cost($model, $input_tokens, $output_tokens);

  $db = dbConnect(GPT_DB);
  $update = dbPrepare(
    $db, 'update Budgets set amount = amount - ? where api_key = ?');
  dbBindParams($db, $update, 'is', $cost, $api_key);
  dbExec($db, $update);
  $update->close();
}


?>
