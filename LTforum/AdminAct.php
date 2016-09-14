<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
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
    require_once($asr->g("templatePath")."SectionElements.php");
    require_once($asr->g("templatePath")."ExportElements.php");    
    //require_once($asr->g("templatePath")."RollElements.php");//will be removed
    
    //Act::view($apr,$asr);
    if ( empty($apr->g("begin")) || ( empty($apr->g("end")) && empty($apr->g("kb")) )  ) Act::showAlert($apr,$asr,"You should give begin and end|kb");
    $begin=$apr->g("begin");
    $end=$apr->g("end");
    if ( $begin < $apr->g("forumBegin") ) $begin = $apr->g("forumBegin");
    if ( $end > $apr->g("forumEnd") ) $end = $apr->g("forumEnd");
    
    $messages=""; 
    $size=0;
    $i=$begin;
    $processed=0;
    $warning="";
    $processedBytes=0; 
    
    $msgList=[];
    while (true) {
      $m=$apr->g("cardfile")->getOneMsg($i);
      if ( !$m ) {
        //print("EOF");
        $warning="\r\nWarning! Records in database ended abruptly ";
        break;
      }
      $processed++;
      if ( $apr->g("newBegin") >= 1 ) $m["id"] = $apr->g("newBegin") + $processed - 1;
      if ( empty($apr->g("newBegin")) ) $m["id"] = "";
      $html=ExportElements::oneMessage ( $m,ExportElements::idTitle ($m),$no=null );
      
      $processedBytes+=strlen($html);
      //$messages.=$html;// remove this, only length is needed
      
      $msgList[]=$m;
      if ( $apr->g("kb")>=1 && ceil($processedBytes/1000)>=$apr->g("kb") ) {
        //print("size exceeded");
        break;
      }
      if ( $end>=1 && $i==$end ) {      
        //print ("count ended");
        break;        
      }
      $i++;
    }
    $realEnd=$begin+$processed-1;
    //print($messages);

    if ( $apr->g("newBegin") >= 1 ) {
      $newBegin=$apr->g("newBegin");
      $newEnd=$newBegin+$processed-1; 
    }
    else {
      $newBegin=$begin;
      $newEnd=$realEnd;
    }
    
    $vr=ViewRegistry::getInstance(2,array("begin"=>$newBegin,"end"=>$newEnd,"msgGenerator"=>$msgList,"controlsClass"=>"ExportElements"
    ));
    $pr=PageRegistry::getInstance();
    $sr=clone $asr;// need to change assetsPath  
    $sr->s( "assetsPath","../".$asr->g("assetsPath") );
    
    $file=$apr->g("obj");
    if ( empty($file) ) $file=$apr->g("forum")."_".$newBegin."_".$newEnd;
    $fullFile=$asr->g("forumsPath").$apr->g("forum")."/".$file.".html";
    //print ($fullFile);
    touch ($fullFile);
    if (! file_exists($fullFile) ) throw new AccessException ("Cannot create file ".$fullFile." , check the folder permissions");
    $apr->s("exportFileFull",$fullFile);    

    //---here comes the interception of the presentation ---
    
    ob_flush();
    ob_end_clean();
    ob_start(["AdminAct","obWrite"],4096);
    
    include ($asr->g("templatePath")."section.php");
    
    ob_end_clean();
    Act::showAlert($apr,$asr,"Exported messages ".$begin."..".$realEnd." to ".$fullFile." , total ".$processed.", about ".ceil($processedBytes/1000)."KB. ".$warning);
    
    return;    
    /* just history
    // $apr,$asr,$newBegin,$newEnd,$processed,$warning,$begin,$realEnd,$messages
    //print("!!".$asr->g("templatePath")."export.html");
    $template=file_get_contents($asr->g("templatePath")."export.html");
    $template=str_replace("@title@",$apr->g("forum")." : ".$newBegin."..".$newEnd,$template);
    $template=str_replace("@assets_path@",$asr->g("assetsPath"),$template);
    $template=str_replace("@prev_link@","<a href=\"\">Previous page</a>",$template);    
    $template=str_replace("@next_link@","<a href=\"./?begin=".($newEnd+1)."\">Next page</a>",$template);
    $template=str_replace("@messages@",$messages,$template);
    $file=$apr->g("obj");
    if ( empty($file) ) $file=$apr->g("forum")."_".$newBegin."_".$newEnd;
    $fullFile=$asr->g("forumsPath").$apr->g("forum")."/".$file.".html";
    //print ($fullFile);
    touch ($fullFile);
    if (! file_exists($fullFile) ) throw new AccessException ("Cannot create file ".$fullFile." , check the folder permissions");
    file_put_contents($fullFile,$template);    
    Act::showAlert($apr,$asr,"Exported messages ".$begin."..".$realEnd." to ".$fullFile." , total ".$processed.", about ".ceil(strlen($template)/1000)."KB. ".$warning);*/    
  }
  
  /**
   * Writes php output buffer into export file after being called by ob_start.
   * @param string $buffer by specification
   * @returns string empty to clear buffer
   */
  public static function obWrite ($buffer) {
    static $handler=null;
    if ( empty($handler) ) {
      $apr=PageRegistry::getInstance(0,[]);
      $handler=fopen( $apr->g("exportFileFull"),"w" );
    }
    fwrite($handler,$buffer);
    return("");// clear the buffer
  }
  
  public function importHtml (PageRegistry $apr, SessionRegistry $asr) {
    //print("import");
    $fullFile=$asr->g("forumsPath").$apr->g("forum")."/".$apr->g("obj").".html";
    if ( !file_exists($fullFile) ) Act::showAlert($apr,$asr,"File not found: ".$fullFile." Have you really created it?");
    $i=0;
    $pieces=self::getOneMessage ($fullFile,$apr->g("order"));
    foreach($pieces as $m) {
      $parsed=self::parseMessage($m);
      //print_r(self::parseMessage($m));
      $apr->g("cardfile")->addMsg($parsed);
      $i++;
    }
    Act::showAlert($apr,$asr,"Added ".$i." messages in ".$apr->g("order")." order");
  }
  
  private static function getOneMessage ($file,$order) {
    $separator="<hr />";
    
    //print("init");
    $buf=file_get_contents($file);
    $size=strlen($buf);
    if ($order=="desc") $pos=strrpos($buf,$separator)-1;
    else $pos=strpos($buf,$separator)+strlen($separator);

    if ($order=="desc") { // search backward
      while ( is_int($newPos=strrpos($buf,$separator,$pos-$size)) ) {
        $m=substr($buf,$newPos,$pos-$newPos);
        $pos=$newPos-1;
        yield($m);
      }
    }
    else { // search forward
      while ( is_int($newPos=strpos($buf,$separator,$pos)) ) {
        $m=substr($buf,$pos,$newPos-$pos);
        $pos=$newPos+strlen($separator);
        yield($m);
      }    
    }
  }

  private static function getTagContent ($m,$tag) {
    $first=strpos($m,"<".$tag);
    $next=strpos($m,">",$first+strlen($tag)-1);
    $closing=strpos($m,"</".$tag,$next);
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
    $mes=self::getTagContent($m,"p");
    //print $m;
    $ret["message"]=$mes;
    $posClosingP=strpos($m,"</p>");
    $mm=substr($m,$posClosingP+4);
    $c=self::getTagContent($mm,"p");
    $ret["comment"]=$c;
    return($ret);
  }

  public function deleteRange (PageRegistry $apr, SessionRegistry $asr) {
    $begin=$apr->g("begin");
    $end=$apr->g("end");
    if ( empty($begin) || empty($end) ) Act::showAlert($apr,$asr,"You must specify both begin and end");
    if ( $begin < $apr->g("forumBegin") ) $begin = $apr->g("forumBegin");
    if ( $end > $apr->g("forumEnd") ) $end = $apr->g("forumEnd");
    if ( $begin != $apr->g("forumBegin") && $end != $apr->g("forumEnd") ) Act::showAlert($apr,$asr,"Your block must border begin or end of forum thread");
    if ( $begin <= $apr->g("forumBegin") && $end >= $apr->g("forumEnd") ) Act::showAlert($apr,$asr,"You are not allowed to delete all messages, leave at least one");    
    $apr->g("cardfile")->deletePackMsg($begin,$end);
    Act::showAlert($apr,$asr,"Removed ".($end+1-$begin)." messages from ".$begin." to ".$end );
  }
  
  public function editAny (PageRegistry $apr, SessionRegistry $asr) {
    $targetId=$apr->g("current");
    if ( $targetId < $apr->g("forumBegin") || $targetId > $apr->g("forumEnd") ) Act::showAlert($apr,$asr,"Invalid message number : ".$targetId);
    $m=$apr->g("cardfile")->getOneMsg($targetId);
     
    $pr=$apr;
    $sr=$asr;
    $vr=ViewRegistry::getInstance( 2, ["id"=>$targetId, "author"=>$m["author"], "message"=>$m["message"], "comment"=>$m["comment"], "controlsClass"=>"EditanyElements"] );
    require_once($asr->g("templatePath")."FormElements.php");
    require_once($asr->g("templatePath")."SubFormElements.php");    
    // show form
    include ($asr->g("templatePath")."form.php");
    exit(0);  
  }  

  public function updateAny (PageRegistry $apr, SessionRegistry $asr) {
    $apr->s( "formLink",Act::addToQueryString($apr,"act=ea","forum","pin","current") );  
    $targetId=$apr->g("current");
    if ( $targetId < $apr->g("forumBegin") || $targetId > $apr->g("forumEnd") ) Act::showAlert($apr,$asr,"Invalid message number : ".$targetId);
    $m=$apr->g("cardfile")->getOneMsg($targetId);    
    // check input strings
    $author=trim($apr->g("author"));
    if ( empty($author) ) Act::showAlert ($apr,$asr,"Empty username is not allowed");
    if ( Act::charsInString($author,"<>&\"':;()") ) 
      Act::showAlert ($apr,$asr,"Username ".htmlspecialchars($author)." contains forbidden symbols");
    $txt=$apr->g("txt");
    if( empty($txt) ) Act::showAlert ($apr,$asr,"Please, leave something in the Message field");
    $txt=Act::prepareInputText($txt,$asr);
    $comm=$apr->g("comm");
    $comm=Act::prepareInputText($comm,$asr);
    // update it now
    $m["author"]=$author;
    $m["message"]=$txt;
    $m["comment"]=$comm;
    if ( !empty($apr->g("clear")) ) $m["time"]=$m["date"]="";// current date and time will be set
    $apr->g("cardfile")->addMsg($m,true);// true for overwrite
  
    Act::showAlert ($apr,$asr,"Message ".$apr->g("current")." has been updated");    
    
  }   
}
