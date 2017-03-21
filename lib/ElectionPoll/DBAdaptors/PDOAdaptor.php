<?php

namespace ElectionPoll\DBAdaptors;
use PDO;

/**
* The DB adaptor doesnt need any fancy models. I just want a way of getting SQL done.
*/
class PDOAdaptor {

  protected $pdo;

  public function __construct(){
    $charset = 'utf8';
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=$charset";
    $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $this->pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $opt);
  }
  public function query($query){
    $stmt = $this->pdo->query($query);
  }
  public function getRows($query){
    $stmt = $this->pdo->query($query);

    $result = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = $row;
    }
    return $result;
  }
  public function quote($str){
    return $this->pdo->quote($str);
  }

}
