<?php
function getDBConnection() {
  $db_hostname = 'localhost';
  $db_database = 'min';
  $db_username = 'min';
  $db_password = 'min';
  return new PDO("mysql:host=$db_hostname;dbname=$db_database", $db_username, $db_password);
}

function makeTables($pdo) {
  $query = $pdo->prepare(<<<_END
CREATE TABLE IF NOT EXISTS `parking`.`status` (
  `id` INT NOT NULL,
  `free` BOOLEAN NOT NULL DEFAULT FALSE,
  `longitude` DOUBLE NOT NULL DEFAULT 0,
  `latitude` DOUBLE NOT NULL DEFAULT 0,
  `type` VARCHAR(64) NOT NULL DEFAULT '',
  PRIMARY KEY(`id`)
);
_END
  );
  $query->execute();
}

function addSpaces($pdo) {
  $spots = json_decode(file_get_contents("./ParkingLayout.geojson"), true);
  $query = $pdo->prepare("INSERT INTO `parking`.`status` (`id`, `longitude`, `latitude`, `type`) VALUES (?, ?, ?, ?);");
  foreach($spots["features"] as $spot) {
    $query->bindValue(1, $spot["id"], PDO::PARAM_INT);
    $query->bindValue(2, (string)$spot["geometry"]["coordinates"][0], PDO::PARAM_STR);
    $query->bindValue(3, (string)$spot["geometry"]["coordinates"][1], PDO::PARAM_STR);
    $query->bindValue(4, $spot["properties"]["Category"], PDO::PARAM_STR);
    $query->execute();
  }
}

$pdo = getDBConnection();
$pdo->beginTransaction();
makeTables($pdo);
addSpaces($pdo);
$pdo->commit();
?>