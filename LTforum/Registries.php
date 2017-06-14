<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */
 
class PageRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;// private causes access error
    
  public function readInput($inputKeys) {
    foreach ($inputKeys as $k) {
      if ( array_key_exists($k,$this->input) ) $this->s($k,$this->input[$k]);
      else $this->s($k,"");
    }
  }

  public function readSession($keys) {
    if ( empty($this->session)/*!$_SESSION*/ ) throw new UsageException("PageRegistry: no active session found !");//return(false);
    $i=0;
    foreach($keys as $sessKey=>$regKey) {
      if ( array_key_exists($sessKey,$this->session) && $this->session[$sessKey] ) {
        $this->s($regKey,$this->session[$sessKey]);
        $i++;
      }
    }
    return($i);
  }

  public function tryInitCardfile(SessionRegistry $sr, $helperClass) {
    try {
      $cs=new CardfileSqlt( $sr->g("forum"), true, null, $sr->g("timeShiftHrs") );
      $this->s( "cardfile", $cs);
    }
    catch (Exception $e) {
      $vr=$helperClass::showAlert ($e->getMessage());
      $vr->display();
      exit;
    }      
  }
  
  protected $input;
  protected $session;
  protected $helperClass;
  
  /**
   * All pageRegistry initialization.
   * Takes data from input, session, sessionRegistry. Allows for isolation testing.
   * @return void
   */
  public function initAllAfterAuth($input=null, $session=null, SessionRegistry $sr, $helperClass, $forumTitle, $forumName) {
    if( !isset($input) ) $this->input = $_REQUEST;
    else $this->input = $input;
    if( !isset($session) ) $this->session = & $_SESSION;
    else $this->session = &$session;
    $this->helperClass = $helperClass;
    
    $inputKeys=[ "act", "current", "begin", "end", "length", /*"user",*/ "txt", "comm", "snap", "del", "query", "searchLength", "order" ];
    $inputs=readByKeys($this->input,$inputKeys);
    $this->addFreshPairsFrom($inputs);

    if (is_array($this->session)) {
      $sessionKeys=["authName"=>"user", /*"current"=>"current", conflicts with readInput*/ "updated"=>"updated" ];
      //$this->readSession($sessionKeys); 
      $sessvars=readByKeys($this->session,$sessionKeys); 
      $this->addFreshPairsFrom($sessvars);
    }

    if ($forumTitle) $this->s("title",$forumTitle);
    else $this->s("title","LTforum::".$forumName);
    $this->s( "viewLink", $helperClass::addToQueryString($this,"end=-1","length")."#footer" );
    $this->tryInitCardfile($sr, $helperClass);
  }
  
  public function queryStringIsEmpty() {
    //echo('act='.$this->g("act").',begin='.$this->g("begin").',end='.$this->g("end") );
    return ( empty($this->g("act")) && empty($this->g("begin")) && empty($this->g("end")) );
  }
  
  public function initAdmBeforeAuth($input=null, $session=null, SessionRegistry $sr, $helperClass, $adminTitle) {
  // $session is not used by admin functions
    if( !isset($input) ) $this->input = $_REQUEST;
    else $this->input = $input;
    //if( !isset($session) ) $this->session = & $_SESSION;
    //else $this->session = &$session;
    $this->helperClass = $helperClass;
    
    $inputKeys=["act","forum","current","begin","end","length","obj","order","kb","newBegin","txt","comm","author","clear","uEntry","user","aUser","period"];
    $inputs=readByKeys($this->input,$inputKeys);
    $this->addFreshPairsFrom($inputs);
    
    // SESSION will not work before AccessController
    
    $this->s("title",$adminTitle);
    if ( empty( $this->g("forum") ) ) {
      sleep(10);
      exit("You should specify the target forum");
    }
    $forumName=$this->g("forum");
    $targetPath=$sr->g("forumsPath").$forumName."/".$forumName;
    $this->s("targetPath",$targetPath);
    $this->s( "title",$adminTitle." : ".$forumName );
    $this->s( "viewLink",$helperClass::addToQueryString($this,"","forum") );
  }
  
  public function initAdmAfterAuth(SessionRegistry $asr) {
    $hc=$this->helperClass;
    try {
      $this->s("cardfile",new CardfileSqlt($this->g("targetPath"),true));
    }
    catch (Exception $e) {
      // of no use as new db was created
      exit ($e->getMessage());
    }
    $total=$this->g("cardfile")->getLimits($forumBegin,$forumEnd,$a,true);
    $this->s("forumBegin",$forumBegin);
    $this->s("forumEnd",$forumEnd);
    $missing=$forumEnd-$forumBegin+1-$total;
    if ($missing) exit("There are ".$missing." missing messages");
    $forumLang=$this->findThreadLang($asr);
    $this->s("forumLang",$forumLang);
  }
  
  protected function findThreadLang($asr) {
    $apr=$this;
    $targetPath=$asr->g("forumsPath").$apr->g("forum")."/";
    if( ! file_exists($targetPath."index.php")) throw new AccessException('Something is wrong with "'.$targetPath.'" folder');
    //echo("=$targetPath= ");
    $threadIni=getIniParams($targetPath);
    //print_r($threadIni);  
    if( isset($threadIni["thread"]["lang"]) ) return($threadIni["thread"]["lang"]);
    $indexFile=file_get_contents($targetPath."index.php");
    $re='~"lang"\s*=>\s*"(\w+)"~';
    $i=preg_match($re,$indexFile,$found);
    //print_r($found);
    if($i) return($found[1]);
    $d=SessionRegistry::getDefaultsFrontend();
    if ( ! isset($d["lang"]) ) throw new AccessException("Something is wrong with SessionRegistry defaults");
    return $d["lang"];
  }
}

class SessionRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;
  
  public static function getDefaultsFrontend() {
    $r=[
      "viewDefaultLength"=>10, "viewOverlay"=>1, "mainPath"=>"", "templatePath"=>"", "assetsPath"=>"", "forum"=>"some forum", "title"=>"some title", "lang"=>"en", "timeShiftHrs"=>0, "maxMessageLetters"=>750, "narrowScreen"=>640, "autoRefresh"=>0
    ];
    return $r;
  }

  public static function getDefaultsBackend() {
    $r=[
      /*"viewDefaultLength"=>10,*/ "viewOverlay"=>1, "mainPath"=>"", "templatePath"=>"", "assetsPath"=>"", /*"forum"=>"some forum",*/ "adminTitle"=>"LTforum admin panel", "lang"=>"en", /*"timeShift"=>0,*/ "maxMessageLetters"=>750, /*"narrowScreen"=>640, "autoRefresh"=>0,*/
      "forumsPath"=>""
    ];
    return $r;
  }
  
}

class ViewRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;
  
  public static function getAdminDefaults() {
    $r=[
      "alert"=>"", "requireFiles"=>null,"includeTemplate"=>"admin.php",
      "userList"=>"", "adminList"=>"", "visitorList"=>""
    ];
    return $r;
  }
  
 /**
  * Allows for decoupling the View from the Controller.
  * First checks if redirect is demanded by ViewRegistry::redirectUri.
  * Then includes template classes as is stated in ViewRegistry::requireFiles,
  * and includes template itself as stated in ViewRegistry::includeTemplate
  * Makes shure SessionRegistry and PageRegistry instances are $sr and $pr as required by templates
  * @return void
  */
  public function display(SessionRegistry $sr,PageRegistry $pr) {
    //$this->dump();
    $vr=$this;// $vr is required for templates
    if ( $this->checkNotEmpty("redirectUri")) {
      header("Location: ".$this->g("redirectUri"));
      exit(0);
    }
    if ( $this->checkNotEmpty("autoRefresh")) {
      header("Refresh: ".$this->g("autoRefresh"));
    }
    if ($this->checkNotEmpty("requireFiles")) {
      $files=explode(",",$this->g("requireFiles"));
      foreach($files as $classFile) {
        require_once($sr->g("templatePath").$classFile);
      }
    }
    if ($this->checkNotEmpty("includeTemplate")) {
      include ($sr->g("templatePath").$this->g("includeTemplate"));
    }
    else {
      throw new UsageException("Empty ViewRegistry::includeTemplate");
    }
  }
}

/**
 *
 * @return array
 */
function renameKey(Array $arr,$old,$new) {
  if ( ! array_key_exists($old,$arr)) throw new UsageException("Missing key ".$old);
  if (array_key_exists($new,$arr)) throw new UsageException("Key ".$new." is already present");
  $arr[$new]=$arr[$old];
  unset($arr[$old]);
  return $arr;
}

/**
 *
 * @param Array $source
 * @param Array $keys can be [key1,key2,..] or [sourceKey1=>outputKey1,sourceKey2=>outputKey2,..]
 * @return array
 */
function readByKeys(Array $source,Array $keys,$dummy="") {
  $r=[];
  foreach($keys as $key1=>$key2) {
    if(is_int($key1)) {
      $key1=$key2;
    }
    //echo("key1=$key1,key2=$key2 ");
    if(array_key_exists($key1,$source)) {
      $r[$key2]=$source[$key1];
    }
    else {
      if($dummy!==false) $r[$key2]=$dummy;
    }
  }
  return $r;
}

function getIniParams($localPath,$globalPath=false) {
  $name=".ini";
  $sections=["thread","auth","intervals"];
  $empty=["thread"=>[],"auth"=>[],"intervals"=>[]];
  $buf="";
  $r=[];
  if($localPath && file_exists($localPath.$name)) {
    $buf=file_get_contents($localPath.$name);
  } else if($globalPath && file_exists($globalPath.$name)) {
    $buf=file_get_contents($localPath.$name);
  } else return ($empty);
  $r=parse_ini_string($buf,true,INI_SCANNER_RAW);
  foreach($sections as $s) {
    if (!array_key_exists($s,$r)) $r[$s]=[];
  }
  return $r;
}

?>