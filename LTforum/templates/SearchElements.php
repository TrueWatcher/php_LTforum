<?php
/**
 * @pakage LTforum
 * @version 1.1 search command
 */ 
/**
 * Functions just for View (Search Results), usually creating control elements.
 * @uses $vr ViewRegistry
 */
class SearchElements {

  static function titleSuffix (ViewRegistry $context) {
    $s="search for ".$context->g("query");
    return ($s);
  }

  static function idLink ($msg,ViewRegistry $context) {
    $qs="act=&amp;begin=".$msg["id"]."&amp;length=".$context->g("length");
    $link=RollElements::genericLink($qs,"#");
    return('<b title="View page">'.$link.'</b>&nbsp;');     
  }
  
  static function localControls ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    $c=self::idLink($msg,$context);
    return ($c);
  } 
  
  static function oneMessage ($msg,$localControlsString) {
    return( RollElements::oneMessage ($msg,$localControlsString) );
  }
  
  static function prevPageLink (ViewRegistry $context,$anchor="View first page",$showDeadAnchor=false) {
    $qs="act=&amp;begin=1&amp;length=".$context->g("length");
    return( RollElements::genericLink($qs,$anchor) );    
  }
  
  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="View last page",$showDeadAnchor=false) {
    $qs="act=&amp;end=-1&amp;length=".$context->g("length");
    return( RollElements::genericLink($qs,$anchor) );   
  }
  
  static function pagePanel (ViewRegistry $context) {}
    
  static function onreadyScript () {}
  
  static function numberForm (ViewRegistry $context) {}
  
  static function lengthForm (ViewRegistry $context) {
    $lengths=array(10,20,50,100,"*");
    
    $form="<form action=\"\" method=\"get\" id=\"resultsPerPage\"><p>Results per page: ";
    $form.="<select name=\"searchLength\">";
    $optList="";
    foreach ($lengths as $l) {
      $optList.="<option value=\"".$l."\"";
      if ( $l==$context->g("searchLength") ) $optList.=" selected=\"selected\"";
      $optList.=">".$l."</option>";
    } 
    //<option value="10">10</option>
    $form.=$optList;
    $form.="</select> <input type=\"submit\" value=\"Apply\"/>";
    $form.="<input type=\"hidden\" name=\"act\" value=\"search\"/>";
    $form.="<input type=\"hidden\" name=\"query\" value=\"".$context->g("query")."\"/>";
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\"/>";
    $form.="<input type=\"hidden\" name=\"order\" value=\"".$context->g("order")."h\"/>";
    $form.="</p></form>";
    return ($form);
  }
  
  static function searchLinkForm (ViewRegistry $context) {
    $form="<form action=\"\" method=\"get\" id=\"resultsPerPage\"><p>Search: ";
    $form.="<input type=\"text\" name=\"query\" value=\"\"/>";
    $form.=" order : <input type=\"radio\" name=\"order\" value=\"desc\" /> from new to old, descending&nbsp;&nbsp;";  
    $form.="<input type=\"radio\" name=\"order\" value=\"asc\" checked=\"checked\" /> from old to new, ascending";
    $form.="</select> <input type=\"submit\" value=\"Search\"/>";    
    $form.="<input type=\"hidden\" name=\"act\" value=\"search\"/>";    
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\"/>";  
    $form.="<input type=\"hidden\" name=\"searchLength\" value=\"".$context->g("searchLength")."\"/>";
    $form.="</p></form>";
    return ($form);
  }
  
  


}