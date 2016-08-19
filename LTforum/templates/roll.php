<?php
/**
 * A View to display a list of messages plus some nice control elements.
 * Under construction.
 */
/**
 * Functions just for this View.
 */
class RollElements {
  /**
   * Adds an Edit link to the latest message, if it is allowed.
   */
  static function editLink ($id,SetGet $context) {
    if( $id==$context->g("forumEnd") && $context->g("topIsEditable")) {
      return ('<b title="Edit/Delete"><a href="?act=el">§</a></b>&nbsp;');
    }
  }

  static function oneMessage ($field,SetGet $context) {
    $newline="<hr />\r\n";
    //$newline.='<!--'.$field['IP'].'; '.time()."-->\r\n";
    $newline.='<address>'.$field['author'].' <em>wrote us on '.$field["date"]." at ".$field["time"]."</em>:";
    $newline.='<span class="fr">'.self::editLink($field["id"],$context);
    $newline.='<b title="'.$field["id"].'">#</b></span>';//."\r\n";
    $newline.="</address>\r\n";
    $newline.='<p class="m">'.$field['message']."</p>\r\n";
    if (! empty($field['comment']) ) $newline.='<p class="n">'.$field['comment']."</p>\r\n";
    return ($newline);
  }
  
  static function prevPageLink (SetGet $context,$anchor="Previous page") {
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $min = $context->g("forumBegin");
    $bs = $context->g("base");
    $step = $context->g("length") - $context->g("overlay");
    switch ($bs) {
    case "begin" :
      $to=$context->g("begin")-$step;
      if ( $to <= $min-$context->g("length")+1 ) return("");// now on first page
      if ( $to < $min ) $to=$min;
      $qs="begin=".$to."&length=".$context->g("length");
      break;
    case "end" :
      $to=$context->g("end")-$step;
      if ($to < $min) return("");// now on first page
      $qs="end=".$to."&length=".$context->g("length");
      break;
    default : throw new UsageException ("Illegal value at \"base\" key :".$bs.'!');
    }
    return($linkHead.$qs.$linkTail_1.$anchor.$linkTail_2);
  }
  static function nextPageLink (SetGet $context,&$pageIsLast=false,$anchor="Next page") {
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $max = $context->g("forumEnd");
    $bs = $context->g("base");
    $step = $context->g("length") - $context->g("overlay");
    switch ($bs) {
    case "begin" :
      $to=$context->g("begin")+$step;
      if ($to > $max) {
        $pageIsLast=true;
        return("");
      }
      $qs="begin=".$to."&length=".$context->g("length");
      break;
    case "end" :
      $to=$context->g("end")+$step;
      if ( $to >= $max+$context->g("length")-1 ) {
        $pageIsLast=true;
        return("");
      }
      if ($to > $max) $to=$max;
      $qs="end=".$to."&length=".$context->g("length");
      break;
    default : throw new UsageException ("Illegal value at \"base\" key :".$bs.'!');
    }
    return($linkHead.$qs.$linkTail_1.$anchor.$linkTail_2);
  }
  static function firstPageLink (SetGet $context) {
    $linkHead='<a href="?';
    $linkTail='">1</a>';
    $min = $context->g("forumBegin");
    $qs="begin=".$min."&length=".$context->g("length");
    return($linkHead.$qs.$linkTail);
  }
  static function lastPageLink (SetGet $context) {
    $linkHead='<a href="?';
    $linkTail_1='">';
    $linkTail_2='</a>';
    $max = $context->g("forumEnd");
    $num = $context->g("pageEnd");
    $qs="end=".$max."&length=".$context->g("length");
    return($linkHead.$qs.$linkTail_1.$num.$linkTail_2);
  }
  static function pagePanel (SetGet $context) {
    $panel="";
    $panel.=self::firstPageLink ($context)." ";
    $panel.=self::prevPageLink($context,"<")." ";
    $panel.=$context->g("pageCurrent")." ";
    $panel.=self::nextPageLink($context,$no,">")." ";
    $panel.=self::lastPageLink ($context);
    return($panel);
  }
  static function newMsgLink (SetGet $context) {
    $linkHead='<a href="?';
    $linkTail='">Write new</a>';
    return ($linkHead."act=new".$linkTail);
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $rr->g("title") ) ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($assetsPath."talk.css") ?>" media="all" />
</head>
<body>
<!--<h2>Hi, I'm LTforum/LTforum/templates/roll.php</h2>-->
<p id="add"><!--<a href="form_t.php">Добавить запись</a>--><?php print ( RollElements::prevPageLink($rr) ); ?></p>
<?php
//require_once("TemplateHelper.php");
foreach ($rr->g("msgGenerator") as $i=>$msg) {
  print ( RollElements::oneMessage($msg,$rr) ); 
}
?>
<hr />
<p id="footer"><?php 
$outcome="viewed~".$i; 
if( $sr->g("toPrintOutcome") ) print("<!--".$outcome."-->"); 
?></p>
<table class="low"><tr>
  <td><?php 
  print ( RollElements::nextPageLink($rr,$lastPage) );
  if ($lastPage) print ( RollElements::newMsgLink($rr) );
  ?></td>
  <td><?php print ( RollElements::pagePanel($rr) ); ?></td>
  <td></td>
</tr></table>
</body>
</html>
