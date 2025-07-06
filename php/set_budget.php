<html>
  <head>
    <style>
td {
  border: 1px solid black;
  padding: 4px;
}
    </style>
  </head>
  <body>


<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../include/db.php');
require_once(__DIR__ . '/../login/include.php');

$session = getLoggedInSession();
if (!$session) {
  http_response_code(401);
  print('Authentication missing');
  exit();
}

if ($session->username !== 'dskloet') {
  http_response_code(403);
  print("Forbidden");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!array_key_exists('api-key', $_POST)) {
    http_response_code(400);
    print('Missing api-key');
    exit();
  }
  if (!array_key_exists('amount', $_POST)) {
    http_response_code(400);
    print('Missing amount');
    exit();
  }
  $api_key = $_POST['api-key'];
  $amount = $_POST['amount'];

  $db = dbConnect(GPT_DB);
  $insert = dbPrepare($db, 'INSERT INTO Budgets (api_key, amount) VALUES (?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)');
  dbBindParams($db, $insert, 'si', $api_key, $amount);
  dbExec($db, $insert);
  $insert->close();
}

function get_budgets() {
  $db = dbConnect(GPT_DB);
  $select = dbPrepare(
      $db, 'select api_key, amount from Budgets');
  dbBindResult($db, $select, $api_key, $amount);
  dbExec($db, $select);
  $result = array();
  while ($select->fetch()) {
    array_push($result, array(
      'api_key' => $api_key,
      'amount' => $amount, // <<< this is line 12
    ));
  }
  $select->close();
  return $result;
}

$budgets = get_budgets();
?>
    <form method="post">
      <table>
        <tr><td>api-key</td><td><input type="text" name="api-key" id="api-key"></td></tr>
        <tr><td>amount</td><td><input type="text" name="amount" value="100000000"></td></tr>
      </table>
      <input type="submit">
    </form>
    <table>
<?php
foreach ($budgets as ['api_key' => $api_key, 'amount' => $amount]) {
?>
      <tr><td><a href="https://dskl.net/gpt/#<?=$api_key?>"><?=substr($api_key, 0, 12)?></a></td><td><?=$amount?></td><td><button onclick="javascript:document.getElementById('api-key').value='<?=$api_key?>'">select</button></td></tr>
<?php
}
?>
    </table>
    <script>

const bytes = new Uint8Array(30);
crypto.getRandomValues(bytes);
const apiKey = btoa(String.fromCharCode.apply(null, bytes))
document.getElementsByName('api-key').forEach(el => el.value = apiKey);

    </script>
  </body>
</html>
