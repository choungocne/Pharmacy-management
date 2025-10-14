<?php
// Cấu hình PDO
define('DB_HOST','127.0.0.1');
define('DB_NAME','nhathuocantam');
define('DB_USER','root');
define('DB_PASS','');

function pdo(){
  static $pdo=null;
  if($pdo===null){
    $pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
      DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false]);
  }
  return $pdo;
}
function money_vn($n){ return number_format((float)$n,0,',','.'); }
