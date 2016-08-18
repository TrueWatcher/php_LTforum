<?php
class TemplateHelper {
  static function editLink ($id,$topId,$editable) {
    if($editable && $id==$topId) {
      return ('<em title="Edit/Delete"><a href="?act=el">!</a></em>&nbsp;');
    }
  }

  static function makeLine ($field,$editLink) {
    $newline="<hr />\r\n";
    //$newline.='<!--'.$field['IP'].'; '.time()."-->\r\n";
    $newline.='<address>'.$field['author'].' <em>wrote us on '.$field["date"]." at ".$field["time"]."</em>:";
    $newline.='<span class="fr">'.$editLink;
    $newline.='<em title="'.$field["id"].'">#</em></span>'."\r\n";
    $newline.="</address>\r\n";
    $newline.='<p class="m">'.$field['message']."</p>\r\n";
    $newline.='<p class="n">'.$field['comment']."</p>\r\n";
    return ($newline);
  }
}
?>