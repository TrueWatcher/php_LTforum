<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */
/**
 * Form View elements for New, EditLast and EditAny commands.
 * @uses  ViewRegistry $context
 */

class EditanyElements extends FormElements {

  static function titleSuffix (ViewRegistry $context) {
    return ( l("edit message ").$context->g("id") );
  }

  static function hiddenFields ($pageContext) {
    $h=self::genericInput ("hidden","act","ua");
    $h.=self::genericInput ("hidden","current",$pageContext->g("current"));
    $h.=self::genericInput ("hidden","forum",$pageContext->g("forum"));
    $h.=self::genericInput ("hidden","pin",$pageContext->g("pin"));
    return ($h);
  }

  static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
    return( parent::authorInput("Author :","author",$context->g("author"),null,null) );
  }

  static function deleteChkbx () {} // disabled

  static function snapChkbx () {} // disabled

  //static function script1 () {}

  //static function script2 () {}

  //static function script3 () {}
}

class NewElements extends FormElements {

  static function titleSuffix (ViewRegistry $context) {
    return (l("write new message"));
  }

  static function hiddenFields ($pageContext) {
    $h=self::genericInput ("hidden","act","add");
    $h.=self::genericInput ("hidden","length",$pageContext->g("length"));
    return ($h);
  }

  static function idP ($context) {}

  static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {} // disabled
  /*static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
    return( parent::authorInput("Your name :","user",$pageContext->g("user"),null,null) );
  }*/

  static function clearChkbx () {} // disabled

  static function deleteChkbx () {} // disabled

  static function txtText ($settings,$context,$labelText=null) {
    return ( parent::txtText($settings,$context,l("Your message")) );
  }

  static function commText ($settings,$context) {} // disabled

  //static function snapChkbx () {}

  static function script3 () {} // disabled
}

class EditElements extends FormElements {

  static function titleSuffix (ViewRegistry $context) {
    return ( l("edit message ").$context->g("id") );
  }

  static function hiddenFields ($pageContext) {
    $h=self::genericInput ("hidden","act","upd");
    $h.=self::genericInput ("hidden","current",$pageContext->g("current"));
    //$h.=self::genericInput ("hidden","user",$pageContext->g("user"));
    $h.=self::genericInput ("hidden","length",$pageContext->g("length"));
    return ($h);
  }

  static function authorInput ($label,$inputName,$authorName,$context) {} // disabled
  /*static function authorInput ($label,$inputName,$authorName,$context) {
    return( parent::userP($context) );
  }*/

  static function clearChkbx () {} // disabled

  //static function deleteChkbx () {}

  static function commText ($settings,$context) {} // disabled

  //static function script1 () {}

  //static function script2 () {}

  static function script3 () {} // disabled
}