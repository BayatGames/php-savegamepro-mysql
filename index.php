<?php

include "includes/pollyfills.inc.php";

if (!file_exists('config.php')) {
  http_response_code(503);
  include "includes/notinstalled.inc.php";
  exit();
}

include "config.php";

if (!isset($_POST) || !isset($_POST['action'])) {
  http_response_code(400);
  echo 'Bad Request, the Request should use HTTP POST method with an "action" parameter.';
  exit;
}

$action = $_POST['action'];
$secret_key = $_POST['secret-key'];
$username = $_POST['username'];
$password = $_POST['password'];
$data_key = isset($_POST['data-key']) ? $_POST['data-key'] : false;
$data_value = isset($_POST['data-value']) ? $_POST['data-value'] : false;
$type = isset($_POST['type']) ? $_POST['type'] : 'user';
$create_account = isset($_POST['create-account']) ? true : false;
$filename = isset($_POST['file-name']) ? $_POST['file-name'] : $data_key;

if ($secret_key !== SECRET_KEY) {
  http_response_code(400);
  exit("The Secret Key is invalid.");
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo "Error: Failed to make a MySQL connection, here is why: \n";
  echo "Errno: " . $mysqli->connect_errno . "\n";
  echo "Error: " . $mysqli->connect_error . "\n";
  exit("The MySQL connection failed.");
}

$get_user_sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
$result = $mysqli->query($get_user_sql);
if (!$result) {
  http_response_code(500);
  echo "Error: Failed to Execute Query, here is why: \n";
  echo "Query: " . $get_user_sql . "\n";
  echo "Errno: " . $mysqli->errno . "\n";
  echo "Error: " . $mysqli->error . "\n";
  exit;
}

if ($result->num_rows === 0) {
  if ($create_account) {
    $date = date("Y-m-d H:i:s");
    $add_user_sql = "INSERT INTO users (`username`, `password`, `type`, `registered`) VALUES ('$username', '$password', '$type', '$date')";
    $result = $mysqli->query($add_user_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $add_user_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    $result = $mysqli->query($get_user_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $get_user_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
  } else {
    http_response_code(500);
    echo "We could not find a match for username $username, sorry about that. Please try again.";
    exit;
  }
}

$user = $result->fetch_assoc();

switch ($action) {
  case 'getfileurl':
    $filepath = merge_paths(UPLOAD_FOLDER, $username, $filename);
    if (file_exists($filepath)) {
      $filepath = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $filepath);
      $filepath = str_replace('\\', '/', $filepath);
      $url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $url .= $_SERVER['HTTP_HOST'] . $filepath;
      http_response_code(200);
      echo $url;
      exit;
    } else {
      http_response_code(500);
      echo "Error: The Requested File does not Exists.";
      exit;
    }
    break;
  case 'uploadfile':
    if (isset($_FILES) && isset($_FILES['file'])) {
      if ($_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $filepath = merge_paths(UPLOAD_FOLDER, $username, $_FILES['file']['name']);
        if (!file_exists(dirname($filepath))) {
          mkdir(dirname($filepath), 0777, true);
        }
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
          http_response_code(200);
          exit("File Successfully Uploaded");
        } else {
          http_response_code(500);
          echo "Error: Upload Failed, maybe the file is invalid or there is some problem with the file.";
          exit;
        }
      } else {
        http_response_code(500);
        echo "Error: Upload Failed, here is why: \n";
        switch ($_FILES['file']['error']) {
          case UPLOAD_ERR_INI_SIZE:
            $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            break;
          case UPLOAD_ERR_FORM_SIZE:
            $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            break;
          case UPLOAD_ERR_PARTIAL:
            $message = "The uploaded file was only partially uploaded";
            break;
          case UPLOAD_ERR_NO_FILE:
            $message = "No file was uploaded";
            break;
          case UPLOAD_ERR_NO_TMP_DIR:
            $message = "Missing a temporary folder";
            break;
          case UPLOAD_ERR_CANT_WRITE:
            $message = "Failed to write file to disk";
            break;
          case UPLOAD_ERR_EXTENSION:
            $message = "File upload stopped by extension";
            break;
          default:
            $message = "Unknown upload error";
            break;
        }
        echo $message;
        exit;
      }
    } else {
      http_response_code(500);
      echo "Error: No File Received, Upload Failed.";
      exit;
    }
    break;
  case 'downloadfile':
    $filepath = merge_paths(UPLOAD_FOLDER, $username, $filename);
    if (file_exists($filepath)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header("Content-Transfer-Encoding: Binary");
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header("Content-Length: " . filesize($filepath));
      readfile($filepath);
    } else {
      http_response_code(500);
      echo "Error: Requested File does not Exists, Download Failed.";
      exit;
    }
    break;
  case 'save':
    $load_sql = "SELECT * FROM saves WHERE user_id='" . $user['ID'] . "' AND data_key='$data_key'";
    $result = $mysqli->query($load_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $load_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    if ($result->num_rows === 0) {
      $save_sql = "INSERT INTO saves (`user_id`, `data_key`, `data_value`) VALUES ('" . $user['ID'] . "', '$data_key', '$data_value')";
    } else {
      $save_sql = "UPDATE saves SET data_value='$data_value' WHERE user_id='" . $user['ID'] . "' AND data_key='$data_key'";
    }
    $result = $mysqli->query($save_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $save_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    http_response_code(200);
    $mysqli->close();
    exit("Data Successfully Saved");
    break;
  case 'load':
    $load_sql = "SELECT * FROM saves  WHERE user_id='" . $user['ID'] . "' AND data_key='$data_key'";
    $result = $mysqli->query($load_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $load_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    if ($result->num_rows === 0) {
      http_response_code(500);
      exit("Error: The Data with given identifier does not found.");
    }
    $data = $result->fetch_assoc();
    http_response_code(200);
    $mysqli->close();
    exit($data['data_value']);
    break;
  case 'delete':
    $delete_sql = "DELETE FROM saves WHERE user_id='" . $user['ID'] . "' AND data_key='$data_key'";
    $result = $mysqli->query($delete_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $delete_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    http_response_code(200);
    $mysqli->close();
    exit("User Data Successfully Deleted");
    break;
  case 'clear':
    $clear_sql = "DELETE FROM saves WHERE user_id='" . $user['ID'] . "'";
    $result = $mysqli->query($clear_sql);
    if (!$result) {
      http_response_code(500);
      echo "Error: Failed to Execute Query, here is why: \n";
      echo "Query: " . $clear_sql . "\n";
      echo "Errno: " . $mysqli->errno . "\n";
      echo "Error: " . $mysqli->error . "\n";
      exit;
    }
    http_response_code(200);
    $mysqli->close();
    exit("User Data Successfully Cleared");
    break;
  default:
    http_response_code(400);
    $mysqli->close();
    exit("The given action does not exists: $action");
    break;
}
