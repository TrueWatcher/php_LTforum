<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */
 
/**
 * A Controller subunit to perfom administrative operations, related to users, like dreation/deletion and setting/unsetting administrative rights.
 * @uses AccessController::$groupFileName
 * @uses MyExceptions
 */
class UserManager {
  
  protected static $forumName;
  protected static $users;
  protected static $admins;
  protected static $groupFile;
  //protected static $groupFileName=".group";
  
  function __construct() {
    if (self::$forumName || self::$groupFile) throw new UsageException("You are not expected to create instances of UserManager class, use it statically!");
  }
    
  static function init($path="",$forum="") {     
    if ($path && $forum) {
      self::$forumName=$forum;
      if ( !class_exists("AccessController") ) throw new UsageException ("UserManager: please, include all dependencies");
      self::$groupFile = $path . AccessController::$groupFileName;//$path.self::$groupFileName;
      if ( ! file_exists(self::$groupFile) ) {
        throw new AccessException ("No such file:".self::$groupFile."!");
        // this should not happen because of AccessController::createEmptyGroupFile($path,$forum);
      }
    }
    self::readGroup(self::$forumName,self::$users,self::$admins);
  }

  /**
   * Reads users and admins inrormation from the config file.
   * @param string $forumName
   * @param array $users output! pairs userName=>passwordHash
   * @param array $admins output! pairs userName=>""
   * @return nothing
   */
  static protected function readGroup($forumName,&$users,&$admins) {
    $parsed=parse_ini_file(self::$groupFile,true);
    //print_r($parsed);
    if ( !array_key_exists($forumName,$parsed) || !array_key_exists($forumName."Admins",$parsed) ) throw new AccessException ("UserManager: invalid group file"); 
    $users=$parsed[$forumName];
    $admins=$parsed[$forumName."Admins"];
    if (empty($users) || empty($admins)) {
      // this should not happen as AccessController::createEmptyGroupFile should had been called already
      throw new AccessException ("UserManager: empty group file");
      // create admin/admin
      /*self::manageUser("add",null,"admin",$forumName,"admin");
      self::manageAdmin("add","admin");*/
    }
  }
  
  /**
   * Adds one entry to config file.
   * @param string $entry must end with NL!
   * @param string $header name of target section
   * @return empty string
   */
  static protected function addToIniFile($entry,$header) {
    $nl="\n";
    $buf=file_get_contents(self::$groupFile);
    $buf=str_replace("\r","",$buf);
    $beginSection=strpos($buf,"[".$header."]");
    if ($beginSection===false) throw new AccessException ("Section ".$header." not found in the file ".$groupFile);
    
    if (strpos($buf,$entry,$beginSection)!==false) return("This entry already exists");
    $head=substr($buf,0,$beginSection+strlen($header)+3);
    $tail=substr($buf,$beginSection+strlen($header)+3);
    $buf=$head.$entry.$tail;
    file_put_contents(self::$groupFile,$buf);
    return("");
  }
  
  /**
   * Removes one entry from config file.
   * @param string $entry must end with NL!
   * @param string $header name of target section
   * @return string empty on success, error message on failure
   */
  static protected function delFromIniFile($entry,$header) { 
    $nl="\n";
    $buf=file_get_contents(self::$groupFile);
    $buf=str_replace("\r","",$buf);
    $beginSection=strpos($buf,"[".$header."]");
    if ($beginSection===false) throw new AccessException ("Section ".$header." not found in the file ".$groupFile);
    
    $where=strpos($buf,$entry,$beginSection);
    if ($where===false) return ("Missing or invalid entry. Try manual editing");
    $after=@substr($buf,$where+strlen($entry),1);
    $before=@substr($buf,$where-2,1);
    //echo(" before:".$before."; after:".$after."; ");
    if( ($before=="]" || $before===false) && ($after=="[" || $after===false ) ) return ("This seems to be the only record in that section. Create another one first");
    $head=substr($buf,0,$where);
    $tail=substr($buf,$where+strlen($entry));
    $buf=$head.$tail;
    file_put_contents(self::$groupFile,$buf);
    return("");
  }  
  
  /**
   * Adds or removes one user to/from the user list of config file.
   * @param string $addOrDel command:"add", "del"
   * @param string $userEntry optional! "userName=passwordHash<NL>"
   * @param string $userName optional!
   * @param string $realm optional!
   * @param string $password optional!
   * @return string empty on success, error message on failure
   */ 
  static function manageUser($addOrDel,$userEntry="",$userName="",$realm="",$password="") {
    $nl="\n";
    if ($userEntry) { 
      $entry=$userEntry.$nl;
      $userName=explode("=",$userEntry)[0];
    }  
    else {
      if ( !$userName || !$realm || !$password ) throw new UsageException("UserManager::manageUser: no arguments");
      $ha=AccessController::makeHa1($userName,$realm,$password);
      $entry=$userName."=".$ha.$nl;
    }
    $found=array_key_exists($userName,self::$users);
    switch ($addOrDel) {
    case "add":
      if ($found) return("This user already exists");
      $ret=self::addToIniFile($entry,self::$forumName);
      break;
    case "del":
      if (!$found) return("No such user");
      if (count(self::$users)==1) return("Can not remove the only user");
      $ret=self::delFromIniFile($entry,self::$forumName);
      if ( !$ret && array_key_exists( $userName,self::$admins ) ) {
        $ret=self::manageAdmin("del",$userName);
      }
      break;
    default :
      return ("Wrong command ".$addOrDel);
    }
    if ($ret) return ($ret);// something was wrong
    self::init();// success, re-read group file
    return ("");
  }
  
  /**
   * Adds or removes one user to/from the admin list of config file.
   * @param string $addOrDel command:"add", "del"
   * @param string $userName optional!
   * @return string empty on success, error message on failure
   */
  static function manageAdmin($addOrDel,$userName) {
    $nl="\n";
    if (!array_key_exists($userName,self::$users)) return("No such user");
    $entry=$userName."=".$nl;
    
    switch ($addOrDel) {
    case "add":
      if (array_key_exists($userName,self::$admins)) return("This user is an admin already");
      $ret=self::addToIniFile( $entry, self::$forumName."Admins" );
      break;
    case "del":
      if (count(self::$admins)==1) return("Can not remove the only admin");
      $ret=self::delFromIniFile( $entry, self::$forumName."Admins" );
      break;
    default :
      return ("Wrong command ".$addOrDel);
    }
    if ($ret) return ($ret);// something was wrong
    self::init();// success, re-read group file
    return ("");
  }    
  
  /**
   * @return array list of user names
   */
  static function listUsers() {
    return (array_keys(self::$users));  
  }

  /**
   * @return array list of admin names
   */
  static function listAdmins() {
    return (array_keys(self::$admins));  
  }
  

}// end UserManager
?>