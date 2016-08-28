<?php
/**
 * @pakage LTforum
 * @version 0.3.2 (tests and bugfixing) (needs admin panel and docs) workable export-import
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
    //Act::view($apr,$asr);
    if ( empty($apr->g("begin")) || ( empty($apr->g("end")) && empty($apr->g("kb")) )  ) Act::showAlert($apr,$asr,"You should give begin and end|kb");
    $begin=$apr->g("begin");
    $end=$apr->g("end");
    if ( $begin < $apr->g("forumBegin") ) $begin = $apr->g("forumBegin");
    if ( $end > $apr->g("forumEnd") ) $end = $apr->g("forumEnd");
    
    $messages=""; 
    $size=0;
    $i=$begin;
    $j=0;
    while (true) {
      $m=$apr->g("cardfile")->getOneMsg($i);
      if ( !$m ) break;
      $i++;
      $j++;
      if ( $end && $i>$end+1 ) break;
      if ( $apr->g("newBegin") >= 1 ) $m["id"] = $apr->g("newBegin") + $j;
      if ( empty($apr->g("newBegin")) ) $m["id"] = "";
      $html=RollElements::oneMessage($m,RollElements::idTitle($m));
      $messages.=$html;
      if ( $apr->g("kb") && length($messages)>$apr->g("kb") ) break;
    }
    //print($messages);
    
    $template=file_get_contents($asr->g("templatePath")."export.html");
    $template=str_replace("@title@",$apr->g("title")." : ".$apr->g("begin")."..".$apr->g("end"),$template);
    $template=str_replace("@assets_path@",$asr->g("assetsPath"),$template);
    $template=str_replace("@prev_link@","<a href=\"\">Previous page</a>",$template);    
    $template=str_replace("@next_link@","<a href=\"?begin=".($end+1)."\">Next page</a>",$template);
    $template=str_replace("@messages@",$messages,$template);
    $file=$apr->g("obj");
    if ( empty($file) ) $file=$apr->g("forum")."_".$begin."_".$end;
    $fullFile=$asr->g("forumsPath")."/".$apr->g("forum")."/".$file.".html";
    //print ($fullFile);
    touch ($fullFile);
    if (! file_exists($fullFile) ) throw new AccessException ("Cannot create file ".$fullFile." , check the folder permissions");
    file_put_contents($fullFile,$template);
    Act::showAlert($apr,$asr,"Exported ".$j." messages to ".$fullFile);    
  }
  
  public function importHtml (PageRegistry $apr, SessionRegistry $asr) {
    //print("import");
    $fullFile=$asr->g("forumsPath")."/".$apr->g("forum")."/".$apr->g("obj").".html";
    if ( !file_exists($fullFile) ) return("File not found: ".$fullFile." Have you really created it?");
    //print($apr->g("order"));
    $i=0;
    $pieces=self::getOneMessage ($fullFile,$apr->g("order"));
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
    //static $separator="<hr />";
    //static $buf=null;
    //static $pos=null;
    //static $size=null;
    $separator="<hr />";
    
    //if ( is_null($buf) ) {
      print("init");
      $buf=file_get_contents($file);
      $size=strlen($buf);
      if ($order=="desc") $pos=strrpos($buf,$separator)-1;
      else $pos=strpos($buf,$separator)+strlen($separator);
    //}
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
    $aSpaceOpenParnth=strpos($a,"(");
    if ( $aSpaceOpenParnth===false ) $aSpaceOpenParnth=strpos($a,"<");
    $ret["author"]=trim(substr($a,0,$aSpaceOpenParnth));
    $d="not found";
    preg_match("~\s[0-9\.]+\s~",$a,$d,PREG_OFFSET_CAPTURE,$aSpaceOpenParnth);
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