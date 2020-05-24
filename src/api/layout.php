<?php
function getDBConnection() {
  $db_hostname = 'localhost';
  $db_database = 'min';
  $db_username = 'min';
  $db_password = 'min';
  return new PDO("mysql:host=$db_hostname;dbname=$db_database", $db_username, $db_password);
}

$pdo = getDBConnection();
$result = $pdo->query("SELECT `id`, `longitude`, `latitude`, `type` FROM `parking`.`status`;");
$result = array_map(function ($row) {
  return [
    "id" => (int)$row["id"],
    "latitude" => (double)$row["latitude"],
    "longitude" => (double)$row["longitude"],
    "type" => $row["type"]
  ];
}, $result->fetchAll(PDO::FETCH_ASSOC));

echo json_encode($result);
?>