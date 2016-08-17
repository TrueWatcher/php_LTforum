<?php
/**
 * @pakage LTforum
 * @version 0.1.2 first workable
 */

/**
 * My exception for file access errors.
 */
class AccessException extends Exception {}
/**
 * My exception for unsupported/forbidden client operations.
 */
class UsageException extends Exception {} 
 
/**
 * A container for database handler.
 */
class ForumDb {

  protected static $forumDbo=null;// DB handler for one forum thread
  
  protected function __construct($forumDbFile,$allowCreate) {
    if (! is_null(self::$forumDbo) ) {
      throw new UsageException ("You are supposed to have only one instance of ForumDb class");
    }
    self::$forumDbo = new SQLite3($forumDbFile,SQLITE3_OPEN_CREATE|SQLITE3_OPEN_READWRITE);
  }
}// end forumDb

/**
 * Transaction script for using database as a set of messages.
 */
class CardfileSqlt extends ForumDb {
  protected static $table="LTforum";

  function __construct($forumName,$allowCreate=false,$tableName=null) {
    $forumDbFile=$forumName.".db";
    if( !empty($tableName) ) self::$table=$tableName;
 
    if (! file_exists($forumDbFile) ) {
      if (! $allowCreate) { // alas
        throw new AccessException ("Missing or inaccessible database file : ".$forumDbFile);
      }
      else { // try to create new database
        touch($forumDbFile);
        if (! file_exists($forumDbFile) ) { // failed to create file
          throw new AccessException ("Cannot create new database file : ".$forumDbFile.", check the folder permissions");
        }
      }
      // new db file was created -- let's write greetings
      parent::__construct($forumDbFile,true);
      $this->createTable(self::$table);
      $this->addFirstMsg($forumDbFile);      
    }
    else {
    // db file was found ready 
      parent::__construct($forumDbFile,false);
    }
  } 
  
  public function createTable($tableName) {
    $qCreateTable="CREATE TABLE '".$tableName."' (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date TEXT,
      time TEXT,
      author TEXT,
      message TEXT,
      comment TEXT
    )";
    
    parent::$forumDbo->exec($qCreateTable);
  }
  
  public function addFirstMsg($dbFileName) {
    $dateTime=explode( "~",date("j.m.Y~G-i") );
    
    $qAddFirstMsg="INSERT INTO '".self::$table."' (
      date, time, author, message, comment ) VALUES ('".
      $dateTime[0]."','".$dateTime[1]."','Creator', 'New hangout ".$dbFileName." is prepared for you!',''
    )";
    parent::$forumDbo->exec($qAddFirstMsg);
  }
  
  public static function addMsg(array $msg,$overwrite=false) {
    $qAddMsg="INSERT INTO '".self::$table."' (
      date, time, author, message, comment ) VALUES (
      :date, :time, :author, :message, :comment
    )";
    $qUpdateMsg="UPDATE '".self::$table."' SET date=:date, time=:time, author=:author, message=:message, comment=:comment WHERE id=:id"; 
    
    if (!$overwrite) $stmt=parent::$forumDbo->prepare($qAddMsg);
    else {
      $stmt=parent::$forumDbo->prepare($qUpdateMsg);
      $stmt->bindValue(':id',$msg["id"],SQLITE3_INTEGER);
    }
    
    if ( !empty( $msg["date"] ) && !empty ( $msg["time"] ) ) {
      $d=$msg["date"];
      $t=$msg["time"];
    }
    else {// set current date and time
      $dateTime=explode( "~",date("j.m.Y~G-i") );    
      $d=$dateTime[0];
      $t=$dateTime[1];
    }
    $stmt->bindValue(':date',$d, SQLITE3_TEXT);
    $stmt->bindValue(':time',$t, SQLITE3_TEXT);
    
    $stmt->bindValue(':author',$msg["author"], SQLITE3_TEXT);
    $stmt->bindValue(':message',$msg["message"], SQLITE3_TEXT);
    $stmt->bindValue(':comment',$msg["comment"], SQLITE3_TEXT);    
    $stmt->execute();
  }  
   
  public function getOneMsg($id) {
    $qGetOneMsg="SELECT id, date, time, author, message, comment
      FROM '".self::$table."' 
      WHERE id=:id";
    $stmt=parent::$forumDbo->prepare($qGetOneMsg);
    $stmt->bindValue(':id',$id,SQLITE3_INTEGER);
    $result = $stmt->execute();
    $msg=$result->fetchArray(SQLITE3_ASSOC);
    return($msg);
  }
  
  public function yieldPackMsg($startId,$length) {
    // from lower numbers to higher ones
    $qGetPackMsg="SELECT id, date, time, author, message, comment
      FROM '".self::$table."' 
      WHERE id>=:startId AND id<=:finId ORDER BY id ASC";
    $stmt=parent::$forumDbo->prepare($qGetPackMsg);
    $stmt->bindValue(':startId',$startId,SQLITE3_INTEGER);
    $finId=$startId+$length;
 
    $stmt->bindValue(':finId',$finId,SQLITE3_INTEGER);
    $result = $stmt->execute();
    //$msgs=array();
    $count=0;
    while ( ( $msg=$result->fetchArray(SQLITE3_ASSOC) ) ) {
      //$msgs[]=$msg;
      $count++;
      yield $count=>$msg;// not yield ($count=>$msg); !
    }
    //return($msgs);
  }
  
  public function getLimits(&$low,&$high,$getCount=false) {
    $qGetLowLimit="SELECT id FROM '".self::$table."' ORDER BY id ASC";
    $low=parent::$forumDbo->querySingle($qGetLowLimit);
    $qGetHighLimit="SELECT id FROM '".self::$table."' ORDER BY id DESC";
    $high=parent::$forumDbo->querySingle($qGetHighLimit);
    if (!$getCount) return ("");
    $qGetCount="SELECT COUNT(*) FROM '".self::$table."'";
    $count=parent::$forumDbo->querySingle($qGetCount);
    return ($count);
  }
  
  public function deletePackMsg($low,$high) {
    // operation is allowed only on first or last n messages
    $this->getLimits($foundLow,$foundHigh);
    if($low!=$foundLow && $high!=$foundHigh) throw new UsageException ("You are supposed to remove messages from the beginning or from the end");
    
    $qDeletePack="DELETE FROM '".self::$table."' WHERE id>=:low AND id<=:high";
    $stmt=parent::$forumDbo->prepare($qDeletePack);
    $stmt->bindValue(':low',$low,SQLITE3_INTEGER);
    $stmt->bindValue(':high',$high,SQLITE3_INTEGER);
    $stmt->execute();
    return ("");
  }
  
}// end CardfileSqlt




// JUNK STORE

/*class helperSqlite {

  public static function createTable($dbo) {
    $qCreateTable="CREATE TABLE 'LTforum' (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date TEXT,
      time TEXT,
      author TEXT,
      message TEXT,
      comment TEXT
    )";
    $dbo->exec($qCreateTable);
  }
  public static function addFirstMsg($dbo,$dbFileName) {
    $dateTime=explode( "~",date("j.m.Y~G-i") );
    
    $qAddFirstMsg="INSERT INTO 'LTforum' (
      date, time, author, message, comment ) VALUES ('".
      $dateTime[0]."','".$dateTime[1]."','Creator', 'New hangout ".$dbFileName." is prepared for you!',''
    )";
    $dbo->exec($qAddFirstMsg);
  }
  public static function addMsg($dbo,array $msg) {
    $qAddMsg="INSERT INTO 'LTforum' (
      date, time, author, message, comment ) VALUES (
      :date, :time, :author, :message, :comment
    )";
    
    $stmt=$dbo->prepare($qAddMsg);
    $dateTime=explode( "~",date("j.m.Y~G-i") );
    $stmt->bindValue(':date',$dateTime[0], SQLITE3_TEXT);
    $stmt->bindValue(':time',$dateTime[1], SQLITE3_TEXT);
    $stmt->bindValue(':author',$msg["author"], SQLITE3_TEXT);
    $stmt->bindValue(':message',$msg["message"], SQLITE3_TEXT);
    $stmt->bindValue(':comment',$msg["comment"], SQLITE3_TEXT);    
    $stmt->execute();
  
  }
  public static function getOneMsg($dbo,$id) {
    $qGetOneMsg="SELECT id, date, time, author, message, comment
      FROM LTforum 
      WHERE id=:id";
    $stmt=$dbo->prepare($qGetOneMsg);
    $stmt->bindValue(':id',$id,SQLITE3_INTEGER);
    $result = $stmt->execute();
    $msg=$result->fetchArray(SQLITE3_ASSOC);
    return($msg);
  }
  public static function yieldPackMsg($dbo,$startId,$length) {
    $qGetPackMsg="SELECT id, date, time, author, message, comment
      FROM LTforum 
      WHERE id<=:startId AND id>:finId ORDER BY id ASC";
    $stmt=$dbo->prepare($qGetPackMsg);
    $stmt->bindValue(':startId',$startId,SQLITE3_INTEGER);
    $finId=$startId-$length;
    if ($finId < 0) $finId=0;
    $stmt->bindValue(':finId',$finId,SQLITE3_INTEGER);
    $result = $stmt->execute();
    //$msgs=array();
    $count=0;
    while ( ( $msg=$result->fetchArray(SQLITE3_ASSOC) ) ) {
      //$msgs[]=$msg;
      $count++;
      yield $count=>$msg;// not yield ($count=>$msg); !
    }
    
    //return($msgs);
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
}*/ 


?>
