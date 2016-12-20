<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
 */
/**
 * Common template for AuthElements.
 * @uses  ViewRegistry $context
 */
abstract class AuthElements {

  protected static function wrapRow ($str,$id=null) {
    if ($id) return('<tr id="'.$id.'">'.$str."</tr>");
    return("<tr>".$str."</tr>");
  }

  protected static function wrapFldTh ($str) {
    return("<th>".$str."</th>");
  }

  protected static function wrapFldTd ($str,$id=null) {
    if ($id) return('<td id="'.$id.'">'.$str."</td>");
    return("<td>".$str."</td>");
  }

  protected static function genericInput ($type,$name,$value) {
    return("<input type=\"".$type."\" id=\"".$name."\" name=\"".$name."\" value=\"".$value."\" />");
  }

  protected static function genericLabel ($name,$labelText) {
    return("<label for=\"".$name."\">".$labelText."</label>");
  }

  //abstract static function titleSuffix (ViewRegistry $context);
  static function titleSuffix (AuthRegistry $context) {
    return ( "Login : ".$context->g("realm") );
  }

  static function hiddenFields ($context) {
    if (class_exists("AdminAct")) {
      return( self::genericInput ( "hidden","forum",$context->g("realm") ) );
    }
  }

  static function alertP (AuthRegistry $context) {
    if ( empty($context->g("alert")) ) return ("");
    return ( self::wrapRow ( self::wrapFldTh("Alert :").self::wrapFldTd($context->g("alert"),"alert") ) );
  }
  
  static function realmP (AuthRegistry $context) {
    $label="Thread : ";
    return ( self::wrapRow ( self::wrapFldTh($label).self::wrapFldTd($context->g("realm")) ) );
  }

  static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
    $row=self::wrapFldTh( self::genericLabel($inputName,$label) );
    $row.=self::wrapFldTd( self::genericInput("text",$inputName,$authorName) );
    return ( self::wrapRow ($row) );
  }
  
  static function pswInput ($label,$inputName,$pswValue,$context,$pageContext) {
    $row=self::wrapFldTh( self::genericLabel($inputName,$label) );
    $row.=self::wrapFldTd( self::genericInput("text",$inputName,$pswValue) );
    return ( self::wrapRow ($row) );
  }

  static function plainChkbx () {
    $label="Send as plain text ";
    $row=self::wrapFldTh( self::genericLabel("plain",$label) );
    $row.=self::wrapFldTd( '<input type="checkbox" id="plain" name="plain" checked="checked" />' );
    // should be unchecked by javaScript
    return ( self::wrapRow ($row) );
  }

  protected static function wrapJs ($str) {
    return ("\r\n".'<script type="text/javascript">'."\r\n".$str."\r\n".'</script>'."\r\n");
  }

  static function scriptHelper ($context,$path=null) {
    if (empty($path)) return ("");
    $s="\r\n".'<script type="text/javascript" src="'.$context->g("assetsPath").$path.'"></script>'."\r\n";
    return($s);
  }

  static function scriptOnready ($s=null) {
    if (empty($s)) return ("");
    return( self::wrapJs($s) );
  }
  
  static function submitButton () {
    $s='<input class="submit" type="submit" value="Submit" />';
    $s='<td id="enter" colspan="2">'.$s."</td>\r\n";
    $s=self::wrapRow ($s);
    return($s);
  }
}