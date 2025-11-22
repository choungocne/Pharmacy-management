<?php
// Cấu hình PDO
// define('DB_HOST','localhost');
// define('DB_NAME','sql_nhom31_itimi');
// define('DB_USER','sql_nhom31_itimi');
// define('DB_PASS','717a636a41b2e');

// file zilla username = ftp_nhom31_itimit_id_vn
// pass : 18dc2dbc9b5508
// host : 103.139.203.43
// post : 21

// -----------------------------------------------------
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
