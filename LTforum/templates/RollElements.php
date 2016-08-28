<?php
/**
 * @pakage LTforum
 * @version 0.3.2 (tests and bugfixing) (needs admin panel and docs) workable export-import
 */ 
/**
 * Functions just for View, usually creating control elements.
 * Need refactoring.
 * @uses $vr ViewRegistry
 */
class RollElements {
  /**
   * Adds an Edit link to the latest message, if it is allowed.
   */
  static function editLink ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    if( $msg["id"]==$context->g("forumEnd") && strcmp($msg["author"],$pageContext->g("user"))==0 ) {
      $userParam="";
      if ( !empty($pageContext->g("user")) ) $userParam="&amp;user=".urlencode($pageContext->g("user"));
      return ('<b title="Edit/Delete"><a href="?act=el'.$userParam.'&amp;end='.$msg["id"].'&amp;length='.$context->g("length").'">§</a></b>&nbsp;');
    }
  }
  
  static function idTitle ($msg) {
    if ( !empty($msg["id"]) ) return('<b title="'.$msg["id"].'">#</b>');
    return("");
  }  

  static function oneMessage ($msg,$localControls) {
    $newline="<hr />\r\n";
    $newline.='<address>'.$msg['author'].' <em>wrote us on '.$msg["date"]." at ".$msg["time"]."</em>:";
    if ( $localControls ) $newline.='<b class="fr">'.$localControls.'</b>';
    $newline.="</address>\r\n";
    $newline.='<p class="m">'.$msg['message']."</p>\r\n";
    if ( !empty($msg['comment']) ) $newline.='<p class="n">'.$msg['comment']."</p>\r\n";
    return ($newline);
  }
  
  static function prevPageLink (ViewRegistry $context,$anchor="Previous page",$showDeadAnchor=false) {
  // needs refactoring
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $step = $context->g("length") - $context->g("overlay");
    $nextBegin = $context->g("begin") - $step;
    $nextEnd = $context->g("end") - $step;
    switch ( $context->g("base") ) {
    case "begin" :
      if ( $nextEnd <= $context->g("forumBegin") ) {
        if ($showDeadAnchor) return($anchor);
        return("");
      }
      if ( $nextBegin < $context->g("forumBegin") ) $nextBegin = $context->g("forumBegin");
      $qs="begin=".$nextBegin."&amp;length=".$context->g("length");//."&amp;nextEnd=".$nextEnd;
      break;
    case "end" :
      if ( $nextEnd <= $context->g("forumBegin") ) {
        if ($showDeadAnchor) return($anchor);
        return("");
      }
      $qs="end=".$nextEnd."&amp;length=".$context->g("length");//."&amp;nextBegin=".$nextBegin;        
      break;
    default : throw new UsageException ("Illegal value at \"base\" key :".$context->g("base").'!');
    }// end switch
    return($linkHead.$qs.$linkTail_1.$anchor.$linkTail_2);
  }
  
  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="Next page",$showDeadAnchor=false) {
  // needs refactoring
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $step = $context->g("length") - $context->g("overlay");
    $nextBegin = $context->g("begin") + $step;
    $nextEnd = $context->g("end") + $step;    
    switch ( $context->g("base") ) {
    case "begin" :
      if ( $nextBegin >= $context->g("forumEnd") ) {
        $pageIsLast=true;
        if ($showDeadAnchor) return($anchor);
        return("");
      }
      $qs="begin=".$nextBegin."&length=".$context->g("length");
      break;
    case "end" :
      if ( $nextBegin >= $context->g("forumEnd") ) {
        $pageIsLast=true;
        if ($showDeadAnchor) return($anchor);
        return("");
      }
      if ( $nextEnd > $context->g("forumEnd") ) $nextEnd = $context->g("forumEnd");
      $qs="end=".$nextEnd."&amp;length=".$context->g("length");
      break;
    default : throw new UsageException ("Illegal value at \"base\" key :".$context->g("base").'!');
    }
    return($linkHead.$qs.$linkTail_1.$anchor.$linkTail_2);
  }
  
  static function firstPageLink (ViewRegistry $context) {
    $linkHead='<a href="?';
    $linkTail='">1</a>';
    $min = $context->g("forumBegin");
    $qs="begin=".$min."&amp;length=".$context->g("length");
    return($linkHead.$qs.$linkTail);
  }
  
  static function lastPageLink (ViewRegistry $context) {
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $max = $context->g("forumEnd");
    $num = $context->g("pageEnd");
    $qs="end=".$max."&amp;length=".$context->g("length");
    return($linkHead.$qs.$linkTail_1.$num.$linkTail_2);
  }
  
  /*
   * A small panel with current page number and rewind links.
   * Like this: 1 < 23 > 99
   */
  static function pagePanel (ViewRegistry $context) {
    $panel="";
    $panel.=self::firstPageLink ($context)."&nbsp;&nbsp;";
    $panel.=self::prevPageLink($context,"-1",true)."&nbsp;&nbsp;";
    $panel.="Page:&nbsp;".$context->g("pageCurrent")."&nbsp;&nbsp;";
    $panel.=self::nextPageLink($context,$no,"1+",true)."&nbsp;&nbsp;";
    $panel.=self::lastPageLink ($context);
    return($panel);
  }
  
  static function newMsgLink (ViewRegistry $context) {
    $el='<a href="?act=new&amp;length='.$context->g("length").'">Write new</a>';
    return ($el);
  }
  /*
   * A small form to change page length.
   * Tries to keep upper/lower record in same place
   */
  static function lengthForm (ViewRegistry $context) {
    $lengths=array(10,20,50,100,"*");
    
    $form="<form action=\"\" method=\"get\" id=\"perPage\"><p>Per page: <select name=\"length\">";
    $optList="";
    foreach ($lengths as $l) {
      $optList.="<option value=\"".$l."\"";
      if ( $l==$context->g("length") ) $optList.=" selected=\"selected\"";
      $optList.=">".$l."</option>";
    } 
    //<option value="10">10</option>
    $form.=$optList;
    $form.="</select> <input type=\"submit\" value=\"Apply\"/>";
    $defineBase="<input type=\"hidden\" name=\"";
    $bs=$context->g("base");
    $defineBase.=$bs."\" value=\"";
    if ( $bs == "begin" ) $defineBase.=$context->g("begin");
    else if ( $bs == "end" ) $defineBase.=$context->g("end");
    else throw new UsageException ("Illegal value at \"base\" key :".$bs.'!');
    $defineBase.="\"/>";
    $form.=$defineBase."</p></form>";
    return ($form);
  }
  static function numberForm (ViewRegistry $context) {
    $form="<form action=\"\" method=\"get\" id=\"messageNumber\"><p>Message&nbsp;(".$context->g("forumBegin")."..".$context->g("forumEnd")."): "; 
    $form.="<input type=\"text\" name=\"begin\" style=\"width:5em;\" value=\"".$context->g("begin")."\" />";
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\" />";
    $form.="</p></form>";
    return ($form);
  }
}
?>