<?php
/**
 * @version 0.1.1 attempting to make anything useful
 */

class helperSqlite {

  public function getHello() {
    return("\r\nHello, I'm helperSqlite::getHello\r\n");
  }

  public static function createTable($dbo) {
    $qCreateTable="CREATE TABLE 'LTforum' (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      dateTime TEXT,
      author TEXT,
      message TEXT,
      comment TEXT
    )";
    $dbo->exec($qCreateTable);
  }
  public static function addFirstMsg($dbo,$dbFileName) {
    $qAddFirstMsg="INSERT INTO 'LTforum' (
      dateTime, author, message, comment ) VALUES ('".
      date("j.m.Y G-i")."', 'Creator', 'New hangout ".$dbFileName." is prepared for you!',''
    )";
    $dbo->exec($qAddFirstMsg);
  }
  public static function addMsg($dbo,array $msg) {
    $qAddMsg="INSERT INTO 'LTforum' (
      dateTime, author, message, comment ) VALUES (
      :dateTime, :author, :message, :comment
    )";
    
    $stmt=$dbo->prepare($qAddMsg);
    $stmt->bindValue(':dateTime',date("j.m.Y G-i"), SQLITE3_TEXT);
    $stmt->bindValue(':author',$msg["author"], SQLITE3_TEXT);
    $stmt->bindValue(':message',$msg["message"], SQLITE3_TEXT);
    $stmt->bindValue(':comment',$msg["comment"], SQLITE3_TEXT);    
    $stmt->execute();
  
  }
  public static function getOneMsg($dbo,$id) {
    $qGetOneMsg="SELECT id, dateTime, author, message, comment
      FROM LTforum 
      WHERE id=:id";
    $stmt=$dbo->prepare($qGetOneMsg);
    $stmt->bindValue(':id',$id,SQLITE3_INTEGER);
    $result = $stmt->execute();
    $msg=$result->fetchArray(SQLITE3_ASSOC);
    return($msg);
  }
  public static function getPackMsg($dbo,$startId,$length) {
    $qGetPackMsg="SELECT id, dateTime, author, message, comment
      FROM LTforum 
      WHERE id<=:startId AND id>:finId ORDER BY id ASC";
    $stmt=$dbo->prepare($qGetPackMsg);
    $stmt->bindValue(':startId',$startId,SQLITE3_INTEGER);
    $finId=$startId-$length;
    if ($finId < 0) $finId=0;
    $stmt->bindValue(':finId',$finId,SQLITE3_INTEGER);
    $result = $stmt->execute();
    $msgs=array();
    $count=0;
    while ( ( $msg=$result->fetchArray(SQLITE3_ASSOC) ) ) {
      $msgs[]=$msg;
      $count++;
    }
    
    return($msgs);
  }
}

class singletDbo {

  private static $dboInstance=null;
  private function __construct() {}

  public static function getInstance($dbFileName) {
    if ( empty(self::$dbInstance) ) { // not instantiated
      $dbFile=$dbFileName.".db";
      if ( !file_exists($dbFile) ) { // and no database 
        //throw new Exception ("File ".$dbFile." not found");
        // create new database
        touch($dbFile);
        $dbo = new SQLite3($dbFile,SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
        helperSqlite::createTable($dbo);
        helperSqlite::addFirstMsg($dbo,$dbFileName);
        self::$dboInstance=$dbo;
      }
      else {
        self::$dboInstance=new SQLite3($dbFile,SQLITE3_OPEN_READWRITE);
      }
    }
    return (self::$dboInstance);
  }
  

} 


?>
