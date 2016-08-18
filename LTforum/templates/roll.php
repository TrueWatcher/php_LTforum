<?php
/**
 * A View to display a list of messages plus some nice control elements.
 * Under construction.
 */
/**
 * Functions just for this View.
 */
class RollHelper {
  /**
   * Adds an Edit link to the latest message, if it is allowed.
   */
  static function editLink ($id,$topId,$editable) {
    if($editable && $id==$topId) {
      return ('<b title="Edit/Delete"><a href="?act=el">§</a></b>&nbsp;');
    }
  }

  static function makeLine ($field,$editLink) {
    $newline="<hr />\r\n";
    //$newline.='<!--'.$field['IP'].'; '.time()."-->\r\n";
    $newline.='<address>'.$field['author'].' <em>wrote us on '.$field["date"]." at ".$field["time"]."</em>:";
    $newline.='<span class="fr">'.$editLink;
    $newline.='<b title="'.$field["id"].'">#</b></span>'."\r\n";
    $newline.="</address>\r\n";
    $newline.='<p class="m">'.$field['message']."</p>\r\n";
    $newline.='<p class="n">'.$field['comment']."</p>\r\n";
    return ($newline);
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title") ) ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($assetsPath."talk.css") ?>" media="all" />
</head>
<body>
<!--<h2>Hi, I'm LTforum/LTforum/templates/roll.php</h2>-->
<p id="add"><a href="form_t.php">Добавить запись</a></p>
<?php
//require_once("TemplateHelper.php");
foreach ($toShow as $i=>$msg) {
  print ( RollHelper::makeLine(
    $msg,
    RollHelper::editLink($msg["id"],$h,$topIsEditable)) 
  );
}
?>
<hr />
<!--127.0.0.1; 1263924650-->
<address>Админ () <em>написал(а) нам 16.03.2010 в 0-30</em>:</address>
<p class="m">Добро пожаловать в нашу новую флудильню!<br />При наборе сообщений не забывайте вставлять переводы строк<br />А при просмотре - нажимать на кнопку "Обновить"</p>
<hr />
<p id="footer"><?php 
$outcome="viewed~".$i; 
if( $sr->g("toPrintOutcome") ) print("<!--".$outcome."-->"); 
?></p>
<table class="low"><tr>
  <td><!-- <a href="talk99.html">Предыдущая страница</a> --></td>
  <td><a href="lock.php?n=talk">Закрыть доступ</a></td>
</tr></table>
</body>
</html>
