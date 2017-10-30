<?php

if (!isset($_POST) || !isset($_POST['db_name']) || !isset($_POST['db_user'])) {
  if (!file_exists("config.php")) {
    include "includes/install.inc.php";
  } else {
    include "includes/ready.inc.php";
  }
  exit;
}

$secret_key = md5(microtime().rand());
$replace = array(
  '<DB_NAME>' => '"' . $_POST["db_name"] . '"',
  '<DB_HOST>' => '"' . $_POST["db_host"] . '"',
  '<DB_PASS>' => '"' . $_POST["db_pass"] . '"',
  '<DB_USER>' => '"' . $_POST["db_user"] . '"',
  '<SECRET_KEY>' => '"' . $secret_key . '"'
);

$config_string = file_get_contents("config.tpl");
$config_string = str_replace(array_keys($replace), array_values($replace), $config_string);
file_put_contents("config.php", $config_string);

include "config.php";
include "includes/header.inc.php";

echo '<div class="container mt-5 mb-5">';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
  echo "<p>MySQL Connection Failed, See the below for more details:</p>";
  echo "<pre>" . $mysqli->connect_error . "</pre>";
  die();
}

if ($mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME) === TRUE) {
  echo '<div class="alert alert-success" role="alert">Database Created Successfully</div>';
} else {
  echo "<p>Database Creation Failed, See the below for more details:</p>";
  echo "<pre>" . $mysqli->error . "</pre>";
}

if ($mysqli->query("USE " . DB_NAME) === TRUE) {
  echo '<div class="alert alert-success" role="alert">Database Selected Successfully</div>';
} else {
  echo "<p>Database Selection Failed, See the below for more details:</p>";
  echo "<pre>" . $mysqli->error . "</pre>";
}

$result = $mysqli->query("CREATE TABLE IF NOT EXISTS users (
  ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY COMMENT 'User Unique ID',
  username varchar(60) NOT NULL UNIQUE KEY COMMENT 'Username',
  password varchar(255) DEFAULT NULL COMMENT 'Password',
  type varchar(60) NOT NULL DEFAULT 'user' COMMENT 'Account Type',
  registered datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Registration Date'
)");
if ($result === TRUE) {
  echo '<div class="alert alert-success" role="alert">Table "users" Created Successfully</div>';
} else {
  echo "<p>Failed to Create Table 'users', See the below for more details:</p>";
  echo "<pre>" . $mysqli->error . "</pre>";
}

$result = $mysqli->query("CREATE TABLE IF NOT EXISTS saves (
  ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY COMMENT 'Data Unique ID',
  user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Associated User ID',
  data_key varchar(255) DEFAULT NULL COMMENT 'Key (Identifier)',
  data_value longblob DEFAULT NULL COMMENT 'Value',
  FOREIGN KEY (user_id) REFERENCES users (ID) ON DELETE CASCADE ON UPDATE CASCADE
)");
if ($result === TRUE) {
  echo '<div class="alert alert-success" role="alert">Table "saves" Created Successfully</div>';
} else {
  echo "<p>Failed to Create Table 'saves', See the below for more details:</p>";
  echo "<pre>" . $mysqli->error . "</pre>";
}

echo "<p>Here is your secret key, Write it down to a paper or some sources, because you can't access this page again:</p>";
echo '<div class="card"><div class="card-body text-center"><strong>' . $secret_key . "</strong></div></div>";

echo "</div>";

include "includes/footer.inc.php";
