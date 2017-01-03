<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */
/**
 * Functions just for View, usually creating control elements.
 * Need refactoring.
 * @uses $vr ViewRegistry
 */

class RollElements extends SectionElements {

  static function titleSuffix (ViewRegistry $context) {
    $s=$context->g("begin")."..".$context->g("end")." (".$context->g("pageCurrent")."/".$context->g("pageEnd").")";
    return ($s);
  }
  /**
   * Adds an Edit link to the latest message, if it is allowed.
   */
  static function editLink ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    if( $msg["id"]==$context->g("forumEnd") && strcmp($msg["author"],$pageContext->g("user"))==0 ) {
      $userParam="";
      //if ( !empty($pageContext->g("user")) ) $userParam="&amp;user=".urlencode($pageContext->g("user"));
      $qs="act=el".$userParam."&amp;current=".$msg["id"]."&amp;length=".$context->g("length");
      $link=self::genericLink($qs,"§");
      return('<b title="Edit/Delete">'.$link.'</b>&nbsp;');
    }
  }

  static function idTitle ($msg) {
    if ( !empty($msg["id"]) ) return('<b title="'.$msg["id"].'">#</b>');
    return("");
  }

  static function localControls ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    $c=self::editLink($msg,$context,$pageContext).self::idTitle($msg);
    return ($c);
  }

  static function prevPageLink (ViewRegistry $context,$anchor,$showDeadAnchor=false,$fragment="") {
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
    return ( self::genericLink($qs,$anchor,$fragment) );
  }

  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="Next page",$showDeadAnchor=false) {
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
    return ( self::genericLink($qs,$anchor) );
  }

  static function genericRewind (ViewRegistry $context,$anchor,$multi,$showDeadAnchor) {
    $step = $context->g("length") - $context->g("overlay");
    $step *= $multi;
    $nextBegin = $context->g("begin") + $step;
    $nextEnd = $context->g("end") + $step;

    if ( $nextEnd <= $context->g("forumBegin") ) {
      if ($showDeadAnchor) return($anchor);
      return("");
    }
    if ( $nextBegin >= $context->g("forumEnd") ) {
      //$pageIsLast=true;
      if ($showDeadAnchor) return($anchor);
      return("");
    }
    if ( $nextBegin < $context->g("forumBegin") ) $nextBegin = $context->g("forumBegin");
    if ( $nextEnd > $context->g("forumEnd") ) $nextEnd = $context->g("forumEnd");

    switch ( $context->g("base") ) {
    case "begin" :
      $qs="begin=".$nextBegin."&length=".$context->g("length");
      break;
    case "end" :
      $qs="end=".$nextEnd."&amp;length=".$context->g("length");
      break;
    default : throw new UsageException ("Illegal value at \"base\" key :".$context->g("base").'!');
    }
    return ( self::genericLink($qs,$anchor) );
  }

  static function firstPageLink (ViewRegistry $context) {
    $qs="begin=".$context->g("forumBegin")."&amp;length=".$context->g("length");
    return ( self::genericLink($qs,"1") );
  }

  static function lastPageLink (ViewRegistry $context) {
    $qs="end=".$context->g("forumEnd")."&amp;length=".$context->g("length");
    return ( self::genericLink($qs,$context->g("pageEnd"),"footer") );
  }

  /*
   * A small panel with current page number and rewind links.
   * Like this: 1 < 23 > 99
   */
  static function pagePanel (ViewRegistry $context) {
    $panel="";
    $panel.="<span drawer=\"Page\">";// marker for drawers.js
    $panel.=self::firstPageLink ($context)."&nbsp;&nbsp;";
    $panel.=self::genericRewind ($context,"-3",-3,true)."&nbsp;&nbsp;";
    $panel.=self::prevPageLink($context,"-1",true)."&nbsp;&nbsp;";
    $panel.="Page:&nbsp;".$context->g("pageCurrent")."&nbsp;&nbsp;";
    $panel.=self::nextPageLink($context,$no,"1+",true)."&nbsp;&nbsp;";
    $panel.=self::genericRewind ($context,"3+",3,true)."&nbsp;&nbsp;";
    $panel.=self::lastPageLink ($context);
    $panel.="</span>";
    return($panel);
  }

  static function newMsgLink (ViewRegistry $context) {
    $el='<a href="?act=new&amp;length='.$context->g("length").'">Write new</a>';
    return ($el);
  }

  static function searchLinkForm (ViewRegistry $context) {
    $el='<a href="?act=search&amp;query=&amp;length='.$context->g("length").'" target="_blank">Search</a>';
    return ($el);
  }
  /*
   * A small form to change page length.
   * Tries to keep upper/lower record in same place
   */
  static function lengthForm (ViewRegistry $context) {
    $lengths=array(10,20,50,100,"*");

    $form="<form action=\"\" method=\"get\" id=\"perPage\" drawer=\"Per\"><p>Per page: <select name=\"length\">";
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
  /*
   * A small form to go to any message by its number.
   */
  static function numberForm (ViewRegistry $context) {
    $form="<form action=\"\" method=\"get\" id=\"messageNumber\" drawer=\"Rec\"><p>Message&nbsp;(".$context->g("forumBegin")."..".$context->g("forumEnd")."): ";
    $form.="<input type=\"text\" name=\"begin\" style=\"width:5em;\" value=\"".$context->g("begin")."\" />";
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\" />";
    $form.="</p></form>";
    return ($form);
  }

  static function logoutLink() {
    //return ( "<td>" . self::genericLink("reg=reset","Log out") . "</td>" );
    return ( "<td>" . self::genericLink("reg=deact","Log out") . "</td>" );
  }
  /*
   * Checks screen width and loads minifier script (drawers.js) if it is narrow
   */
  static function onreadyScript (SessionRegistry $sessionContext) {
    $s="<script>";
    $s.="var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;";
    $s.="if ( width<=".$sessionContext->g("narrowScreen")." ) {";
    $s.="var src=\"".$sessionContext->g("assetsPath")."drawers.js"."\";";
    $s.="var s=document.createElement('script'); s.setAttribute( 'src', src );
  document.body.appendChild(s);";
    $s.="}";
    $s.="</script>";
    return ($s);
  }

  static function bottomAlert (PageRegistry $pageContext,$actualCount) {}

}
?>