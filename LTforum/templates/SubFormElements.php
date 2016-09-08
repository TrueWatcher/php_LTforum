<?php
/**
 * @pakage LTforum
 * @version 1.1.2 refactored Form classes
 */ 
/**
 * Form View elements for New, EditLast and EditAny commands.
 * @uses  ViewRegistry $context
 */
 
class EditanyElements extends FormElements {

  static function hiddenFields ($pageContext) {
    $h=self::genericInput ("hidden","act","ua");
    $h.=self::genericInput ("hidden","end",$pageContext->g("end"));
    $h.=self::genericInput ("hidden","forum",$pageContext->g("forum"));  
    $h.=self::genericInput ("hidden","pin",$pageContext->g("pin"));
  return ($h);  
  }
  
  static function authorInput ($label,$inputName,$authorName,$context) {
    return( parent::authorInput("Author :","author",$context->g("author"),null) );
  }
  
  static function deleteChkbx () {} // disabled
  
  static function snapChkbx () {} // disabled
  
  //static function script1 () {} 
  //static function script2 () {}
  //static function script3 () {} // disabled
} 