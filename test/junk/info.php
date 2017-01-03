<?php
/**
 * @pakage LTforum
 * @version 1.2
 */
/**
 * An LTforum service to get info about latest messages without registration.
 * URI: http://my_forum.com/info.php?forum=test&user=Editor
 * If parameters are found invalid, this service blocks itself completely for some time (one minute by default). The details are accessible in http://my_forum.com/infoLock.dat .
 */

/**
 * Manages locking.
 */
class InfoLock {

  private static $lockFileName="infoLock.dat";
  private static $lockPeriod=60; // seconds

  /**
   * @return string Error message or empty string on success
   */
  public function check() {
    if ( !file_exists(self::$lockFileName) ) {
      $this->createEmpty();
      if ( !file_exists(self::$lockFileName) ) {
        exit ("Failed to create data file. Check the permissions");
      }
    }
    $parsed=parse_ini_file(self::$lockFileName);
    if ( !array_key_exists("notBefore",$parsed) || empty($parsed["notBefore"]) ) return ("Invalid time limit");
    if ( time() < $parsed["notBefore"] ) return ("Service is locked");
    return("");
  }

  /**
   * Creates lock and writes the data file.
   * @param string $message optional error message
   * @return void
   */
  public function lock($message="") {
    $nl="\n";
    $s="";
    $s.="notBefore=".(time()+self::$lockPeriod).$nl;
    $s.="IP=".$_SERVER["REMOTE_ADDR"].$nl;
    $s.="hostname=\"".gethostbyaddr($_SERVER['REMOTE_ADDR'])."\"".$nl;
    $s.="UA=\"".$_SERVER["HTTP_USER_AGENT"]."\"".$nl;
    $s.="query=\"".$_SERVER["QUERY_STRING"]."\"".$nl;
    if ($message) $s.="message=\"".$message."\"".$nl;
    file_put_contents(self::$lockFileName,$s);
  }

  /**
   * Creates a dummy lock file.
   */
  private function createEmpty() {
    $nl="\n";
    $s="";
    $s.="notBefore=".(time()).$nl;
    file_put_contents(self::$lockFileName,$s);
  }
}

//$forumName="test";// canonical forum name
//$adminTitle="LTforum informer";// page title
$mainPath="LTforum/";// relative to here
//$templatePath="LTforum/templates/"; // // relative to here
//$assetsPath="assets/"; // relative to here
$forumsPath=""; // relative to here

require_once ($mainPath."MyExceptions.php");

$il=new InfoLock;
// initial lock check
if ( $ret=$il->check() ) exit($ret);

require_once ($mainPath."Act.php");
// query parameters check
if ( !array_key_exists("forum",$_REQUEST) || !array_key_exists("user",$_REQUEST) || Act::charsInString($_REQUEST["forum"],"<>'\"") || Act::charsInString($_REQUEST["user"],"<>'\"") ) {
  $il->lock("Missing or invalid arguments");
  exit();
}

// connect to database
$targetForum=$_REQUEST["forum"];
$targetPath=$forumsPath.$targetForum."/".$targetForum;
require_once ($mainPath."CardfileSqlt.php");
try {
  $dbm=new CardfileSqlt($targetPath,false);
}
catch (Exception $e) { // invalid forum name
  $il->lock( $e->getMessage() );
  exit();
}

$user=$_REQUEST["user"];
$found=$dbm->getLastMsgByAuthor($user);
if (!$found) { // no messages by this user in this forum
  $il->lock("No such user");
  exit();
}

// check if the latsest message is authored by this user
$last=$dbm->getLastMsg();
$lastIsUsers=( $found["id"] === $last["id"] );
// prepare the answer
$nl="\n";
$r="";
$r.="Latest:".$last["date"]." ".$last["time"];
if ($lastIsUsers) {
  $r.=", by this author".$nl;
}
else {
  $r.=$nl."New messages (since the author's):".($last["id"]-$found["id"]).$nl;
}
$r.="<a href=\"./".$targetForum."\">Go read</a>";
exit($r);
?>


