<?php
function getDBConnection() {
  $db_hostname = 'localhost';
  $db_database = 'min';
  $db_username = 'min';
  $db_password = 'min';
  return new PDO("mysql:host=$db_hostname;dbname=$db_database", $db_username, $db_password);
}

function requestSpotStats($types) {
  $pdo = getDBConnection();
  $typelist = count($types) === 0 ? "" : " AND `type` IN (?".str_repeat(", ?", count($types) - 1).")";
  $query = $pdo->prepare("SELECT `type`, COUNT(*) FROM `parking`.`status` WHERE `free` = TRUE".$typelist." GROUP BY `type`;");
  
  foreach($types as $i => $type) {
    $query->bindValue($i + 1, $type, PDO::PARAM_STR);
  }
  
  $query->execute();
  return array_map(function($x) {
    return (int)$x;
  }, $query->fetchAll(PDO::FETCH_KEY_PAIR));
}

function requestFreeSpots($types, $prefer) {
  $pdo = getDBConnection();
  $typelist = count($types) === 0 ? "" : " AND `type` IN (?".str_repeat(", ?", count($types) - 1).")";
  $query = $pdo->prepare("SELECT `id` FROM `parking`.`status` WHERE `free` = TRUE".$typelist." LIMIT 50;");
  
  foreach($types as $i => $type) {
    $query->bindValue($i + 1, $type, PDO::PARAM_STR);
  }
  
  $query->execute();
  return array_map(function($x) {
    return (int)$x;
  }, $query->fetchAll(PDO::FETCH_COLUMN, 0));
}

function updateFreeSpots($spots) {
  $pdo = getDBConnection();
  $pdo->beginTransaction();
  
  $query = $pdo->prepare("UPDATE `parking`.`status` SET `free` = ? WHERE `id` = ?;");
  foreach($spots as $spot) {
    [
      'id' => $id,
      'free' => $free
    ] = $spot;
    
    $query->bindValue(1, $free, PDO::PARAM_BOOL);
    $query->bindValue(2, $id, PDO::PARAM_INT);
    
    if(!$query->execute()) {
      $pdo->rollBack();
      return FALSE;
    }
  }
  
  $pdo->commit();
  return TRUE;
}

if($_SERVER["REQUEST_METHOD"] === "GET") {
  try {
    if(isset($_GET["digest"])) {
      echo json_encode(requestSpotStats(isset($_GET["type"]) && $_GET["type"] != "" ? explode(",", $_GET["type"], 64) : array()));
    } else {
      echo json_encode(requestFreeSpots(isset($_GET["type"]) && $_GET["type"] != "" ? explode(",", $_GET["type"], 64) : array(), isset($_GET["prefer"]) ? $_GET["prefer"] : NULL));
    }
  } catch (Exception $e) {
    http_response_code(500);
  }
} else if($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    if(!updateFreeSpots(json_decode(file_get_contents("php://input"), true))) {
      http_response_code(400);
    }
  } catch (Exception $e) {
    http_response_code(500);
  }
} else {
  http_response_code(405);
}
?>