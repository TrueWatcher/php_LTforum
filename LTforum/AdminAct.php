<?php
/**
 * @pakage LTforum
 * @version 0.3.0 (tests and bugfixing) needs admin panel and docs
 */

/**
 * Helper class for Administration Controller
 */
class AdminAct {

  public function checkThreadPin (PageRegistry $apr, SessionRegistry $asr) {
    if ( empty($apr->g("forum")) ) return ("You must specify a forum thread");
    if ( empty($apr->g("user")) && strcmp($apr->g("pin"),$asr->g("pin"))!==0 ) return ("You must present PIN. Or, better, set up authentication");
    if ( !file_exists($apr->g("targetPath").".db") ) return ("You must specify a valid forum thread ".$apr->g("targetDb"));    
  }
  
  public function exportHtml (PageRegistry $apr, SessionRegistry $asr) {
    //print("export");  
    Act::view($apr,$asr);
  }
  
  public function importHtml (PageRegistry $apr, SessionRegistry $asr) {
    //print("import");
    $file=$apr->g("obj").".html";
    if ( !file_exists($file) ) return("File not found: ".$file." Have you uploaded it?");
    //print($apr->g("order"));
    $i=0;
    $pieces=self::getOneMessage ($file,$apr->g("order"));
    foreach($pieces as $m) {
      //print("\r\n".$m."\r\n");
      //print("\r\n".self::getTag($m,"address")."\r\n");
      $parsed=self::parseMessage($m);
      //print_r(self::parseMessage($m));
      $apr->g("cardfile")->addMsg($parsed);
      $i++;
    }
    Act::showAlert($apr,$asr,"Added ".$i." messages in ".$apr->g("order")." order");
    
  }
  
  private static function getOneMessage ($file,$order) {
    static $separator="<hr />";
    static $buf=null;
    static $pos=null;
    static $size=null;
    if ( is_null($buf) ) {
      //print("init");
      $buf=file_get_contents($file);
      $size=strlen($buf);
      if ($order=="desc") $pos=strrpos($buf,$separator)-1;
      else $pos=strpos($buf,$separator)+strlen($separator);
    }
    if ($order=="desc") { // search backward
      while ( is_int($newPos=strrpos($buf,$separator,$pos-$size)) ) {
        //print($newPos);
        $m=substr($buf,$newPos,$pos-$newPos);
        $pos=$newPos-1;
        yield($m);
      }
    }
    else { // search forward
      while ( is_int($newPos=strpos($buf,$separator,$pos)) ) {
        //print($newPos);
        $m=substr($buf,$pos,$newPos-$pos);
        $pos=$newPos+strlen($separator);
        yield($m);
      }    
    }
  }

  private static function getTagContent ($m,$tag) {
    $first=strpos($m,"<".$tag);
    $next=strpos($m,">",$first+strlen($tag)-1);
    $closing=strpos($m,"</".$tag,$next+1);
    if ( !is_int($first) || !is_int($next) || !is_int($closing) ) return(""); 
    return( substr($m,$next+1,$closing-$next-1) );
  }
  
  private static function parseMessage ($m) {
    $ret=[];
    $a=self::getTagContent($m,"address");
    $aSpaceOpenParnth=strpos($a," (");
    $ret["author"]=substr($a,0,$aSpaceOpenParnth);
    $d="not found";
    preg_match("~\s[0-9\.]+\s~",$a,$d,PREG_OFFSET_CAPTURE,$aSpaceOpenParnth);
    //print_r($d[0][0]);
    $ret["date"]=trim($d[0][0]);
    $aFirstNumber=$d[0][1];
    preg_match("~\s[0-9\-]+~",$a,$t,0,$aFirstNumber+6);
    $ret["time"]=trim($t[0]);
    //print_r($t);
    $m=self::getTagContent($m,"p");
    //print $m;
    $ret["message"]=$m;
    $posClosingP=strpos($m,"</p>");
    $mm=substr($m,$posClosingP+4);
    $c=self::getTagContent($mm,"p");
    $ret["comment"]=$c;
    return($ret);
  }

}