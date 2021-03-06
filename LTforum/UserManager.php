<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */

/**
 * A Controller subunit to perfom administrative operations, related to users, like dreation/deletion and setting/unsetting administrative rights.
 * @uses AccessHelper::$groupFileName
 * @uses MyExceptions
 */
class UserManager {

  protected static $forumName;
  protected static $users=[];
  protected static $admins=[];
  protected static $visitors=[];
  protected static $groupFile;
  //protected static $groupFileName=".group";

  function __construct() {
    if (self::$forumName || self::$groupFile) throw new UsageException("You are not expected to create instances of UserManager class, use it statically!");
  }

  static function init($path="",$forum="") {
    if ($path && $forum) {
      self::$forumName=$forum;
      if ( !class_exists("AccessHelper") ) throw new UsageException ("UserManager: please, include all dependencies");
      self::$groupFile = $path . AccessHelper::$groupFileName;//$path.self::$groupFileName;
      if ( ! file_exists(self::$groupFile) ) {
        throw new AccessException ("No such file:".self::$groupFile."!");
        // this should not happen because of AccessHelper::createEmptyGroupFile($path,$forum);
      }
    }
    self::readGroup(self::$forumName,self::$users,self::$admins,self::$visitors);
    //AccessHelper::readGroup(self::$forumName,self::$users,self::$admins);
  }
  
  /**
   * Main entry point.
   * @return false on unknown command, ViewRegistry instance on success or error
   */
  static function go(PageRegistry $apr, SessionRegistry $asr) {
    //print_r($_REQUEST);
    try {
      $vr=ViewRegistry::getInstance( 1,ViewRegistry::getAdminDefaults() );
      self::init($asr->g("forumsPath").$apr->g("forum")."/",$apr->g("forum"));
      
      switch ( $apr->g("act") ) {
      case ("lu"):
        $vr->s("userList",implode(", ",UserManager::listUsers() ) );
        break;
        
      case ("la"):
        $vr->s("adminList",implode(", ",UserManager::listAdmins() ) );
        break;
        
      case ("uAdd"):
        $ret=UserManager::manageUser("add",$apr->g("uEntry"));
        if ($ret) {
          $vr->clearInstance();
          return(AdminAct::showAlert ($ret));
        }
        $vr->s("userList",implode(", ",UserManager::listUsers() ) );
        break;
        
      case ("uDel"):
        $ret=UserManager::manageUser("del",$apr->g("uEntry"));
        if ($ret) {
          $vr->clearInstance();
          return(AdminAct::showAlert ($ret));
        }
        $vr->s("userList",implode(", ",UserManager::listUsers() ) );
        break;
        
      case ("aAdd"):
        $ret=UserManager::manageAdmin("add",$apr->g("aUser"));
        if ($ret) {
          $vr->clearInstance();
          return(AdminAct::showAlert($ret));
        }
        $vr->s("adminList",implode(", ",UserManager::listAdmins() ) );
        break;
        
      case ("aDel"):
        $ret=UserManager::manageAdmin("del",$apr->g("aUser"));
        if ($ret) {
          $vr->clearInstance();
          return(AdminAct::showAlert($ret));
        }
        $vr->s("adminList",implode(", ",UserManager::listAdmins() ) );
        break;
        
      case ("markVisitor"):
        $ret=UserManager::markAsVisitor($apr->g("aUser"),$apr->g("period"));
        if ($ret) {
          $vr->clearInstance();
          return(AdminAct::showAlert($ret));
        }
        // fall-through
      case ("lv"):
        $vl=UserManager::listVisitors();
        $r=[];
        $t=time();
        foreach ($vl as $name=>$expire) {
          $r[]=$name." ".($expire-$t);
        }
        $r=implode(", ",$r);
        $vr->s("visitorList",$r );
        break;
        
      case ("userInfo"):
        if (empty($apr->g("aUser"))) throw new UsageException("Empty username");
        if (array_key_exists( $apr->g("aUser"),self::$users ) ) {
          $vr->s("userList",$apr->g("aUser"));
        }
        break;
        
      default:
        $vr->clearInstance();
        return(false);
      }
      return($vr);
      
    } catch (AccessException $e) {
      $vr->clearInstance();
      return(AdminAct::showAlert($e->getMessage()));
    }
  }
  
  /**
   * Reads users and admins inrormation from the config file.
   * @param string $forumName
   * @param array $users output! pairs userName=>passwordHash
   * @param array $admins output! pairs userName=>""
   * @return nothing
   */
  static function readGroup($forumName,&$users,&$admins,&$visitors) {
  
    $parsed=parse_ini_file(self::$groupFile,true);
    //print_r($parsed);
    if ( !array_key_exists($forumName,$parsed) || !array_key_exists($forumName."Admins",$parsed) ) throw new AccessException ("Invalid group file");
    $users=$parsed[$forumName];
    $admins=$parsed[$forumName."Admins"];
    if (empty($users) || empty($admins)) {
      // this should not happen as AccessHelper::createEmptyGroupFile should had been called already
      throw new AccessException ("Empty group file");
      // create admin/admin
      /*self::manageUser("add",null,"admin",$forumName,"admin");
      self::manageAdmin("add","admin");*/
    }
    $visitors=$parsed[$forumName."Visitors"];
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
  static protected function delFromIniFile($entry,$header,$keepTheLastLine=true) {
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
    if($keepTheLastLine && ($before=="]" || $before===false) && ($after=="[" || $after===false ) ) return ("This seems to be the only record in that section. Create another one first");
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
      $ha=AccessHelper::makeHa1($userName,$realm,$password);
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
      if (array_key_exists( $userName,self::$admins ) && count(self::$admins)==1) return("Can not remove the only admin");
      
      $ret=self::delFromIniFile($entry,self::$forumName);
      // main record removed from the file, but there may be others
      // self::lists have not been reindexxed yet
      if ( ! $ret && array_key_exists( $userName,self::$admins ) ) {
        $ret=self::manageAdmin("del",$userName);
      }
      if ( ! $ret && array_key_exists( $userName,self::$visitors ) ) {
        $entry=$userName."=".self::$visitors[$userName].$nl;
        $ret=self::delFromIniFile( $entry, self::$forumName."Visitors", false );
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
   * @param string $userName
   * @return string empty on success, error message on failure
   */
  static function manageAdmin($addOrDel,$userName) {
    $nl="\n";
    if ( ! array_key_exists($userName,self::$users)) return("No such user $userName");
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
   * Sets session expire time for the given user.
   * @param string $userName
   * @param int $period session lifetime from now in seconds
   * @return string empty on success, error message on failure
   */
  static function markAsVisitor($userName,$period) {
    $nl="\n";
    if ( ! array_key_exists($userName,self::$users)) return("No such user $userName");
    if (array_key_exists($userName,self::$admins)) return("Cannot make an admin a visitor");
    if (array_key_exists($userName,self::$visitors)) {
    // she's already a visitor, need to remove that entry
      $entry=$userName."=".self::$visitors[$userName].$nl;
      //echo(" trying to remove \"$entry\" ");
      $ret=self::delFromIniFile( $entry, self::$forumName."Visitors", false );
      if($ret) return ($ret);//throw new UsageException($ret);
    }
    if ($period) { // period=0 unmarks a visitor into a normal user
      $entry=$userName."=".(time()+(int)$period).$nl;
      $ret=self::addToIniFile( $entry, self::$forumName."Visitors" );
    }
    if ($ret) return ($ret);
    self::init();// re-read group file
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
  
  /**
   * @return array list of visitors name=>deadline
   */
  static function listVisitors() {
    return (self::$visitors);
  }  


}// end UserManager
?>