<?php
/**
 * @pakage LTforum
 * @version 0.3.0 (0.2.0 improvements and fixes) needs admin panel and docs
 */

/**
 * A container for database handler.
 */
class ForumDb {

  protected static $forumDbo=null;// DB handler for one forum thread
  
  protected function __construct($forumDbFile,$allowCreate) {
    if (! is_null(self::$forumDbo) ) {
      throw new UsageException ("You are supposed to have only one instance of ForumDb class");
      //return(0);
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
    $forumPath="";// relative to /thread/index.php
    $forumDbFile=$forumPath.$forumName.".db";
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
    // AUTOINCREMENT with PRIMARY KEY prevent the reuse of ROWIDs from previously deleted rows.
    // https://www.sqlite.org/autoinc.html
    $qCreateTable="CREATE TABLE '".$tableName."' (
      id INTEGER PRIMARY KEY,
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
      $dateTime[0]."','".$dateTime[1]."','Creator', 'A new hangout \"".$dbFileName."\" have been prepared for your pleasure !',''
    )";
    parent::$forumDbo->exec($qAddFirstMsg);
  }
  
  public static function addMsg(array $msg,$overwrite=false) {
    $qAddMsg="INSERT INTO '".self::$table."' (
      date, time, author, message, comment ) VALUES (
      :date, :time, :author, :message, :comment
    )";
    $qUpdateMsg="UPDATE '".self::$table."' SET date=:date, time=:time, author=:author, message=:message, comment=:comment WHERE id=:id";
    
    if ( empty($msg["author"]) ) throw new UsageException ("New message must have an author");
    
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
  
  public function getLastMsg() {
    $qGetLastMsg="SELECT id, date, time, author, message, comment
      FROM '".self::$table."' 
      ORDER BY id DESC";
    $msg=parent::$forumDbo->querySingle($qGetLastMsg,true);
    return($msg);
  }
  
  public function yieldPackMsg($startId,$finId) {
    // from lower numbers to higher ones
    $qGetPackMsg="SELECT id, date, time, author, message, comment
      FROM '".self::$table."' 
      WHERE id>=:startId AND id<=:finId ORDER BY id ASC";
    $stmt=parent::$forumDbo->prepare($qGetPackMsg);
    $stmt->bindValue(':startId',$startId,SQLITE3_INTEGER);
    //$finId=$startId+$length;
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
  
  public function getLimits(&$low,&$high,&$topAuthor,$getCount=false) {
    $qGetLowLimit="SELECT id FROM '".self::$table."' ORDER BY id ASC";
    $low=parent::$forumDbo->querySingle($qGetLowLimit);
    
    $qGetHighLimit="SELECT id,author FROM '".self::$table."' ORDER BY id DESC";
    $high2=parent::$forumDbo->querySingle($qGetHighLimit,true);
    $high=$high2["id"];
    $topAuthor=$high2["author"];
    
    if (!$getCount) return ("");
    $qGetCount="SELECT COUNT(*) FROM '".self::$table."'";
    $count=parent::$forumDbo->querySingle($qGetCount);
    return ($count);
  }
  
  public function deletePackMsg($low,$high) {
    // operation is allowed only on first or last n messages
    $this->getLimits($foundLow,$foundHigh,$no);
    if($low!=$foundLow && $high!=$foundHigh) throw new UsageException ("You are supposed to remove messages from the beginning or from the end");
    
    $qDeletePack="DELETE FROM '".self::$table."' WHERE id>=:low AND id<=:high";
    $stmt=parent::$forumDbo->prepare($qDeletePack);
    $stmt->bindValue(':low',$low,SQLITE3_INTEGER);
    $stmt->bindValue(':high',$high,SQLITE3_INTEGER);
    $stmt->execute();
    return ("");
  }
  
}// end CardfileSqlt
?>
