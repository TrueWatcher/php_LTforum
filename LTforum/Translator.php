<?php

/**
 * Entry point into Translator.
 * Also transforms [formatString,var1,varN] with vsprint, so is required even when Translator is not used.
 * @param string|Array $text
 * @return string
 */
function l($text) {
  $args=null;
  if(is_array($text)) {
    $string=array_shift($text);// $text became 1 piece shorter
    $args=$text;
  }
  else if (is_string($text)) {
    $string=$text;
  }
  else throw new UsageException("Invalid argument:".$text."!");
  if (class_exists("Translator") && Translator::$enabled) {
    $string=Translator::go($string);
  }
  if ($args) {
    //var_dump($args);
    $string=vsprintf($string,$args);
  }
  return $string;
}

/**
 * Filters strings through a lookup table.
 * May collect strings without translation.
 */
abstract class Translator {
  public static $enabled=0;// 0 - do nothing, 1 - collect only, 2 - try to translate
  protected static $lang="";
  protected static $lookup=[];
  protected static $lookupKeys=[];
  protected static $langs=["en","mock","ru"];
  protected static $csvSeparator="~";
  protected static $csvNl="\n";
  protected static $fileName="translations.csv";
  protected static $myFile="";
  
  public static function init($lang,$path,$allowCreate=0) {
    //echo("init lang=".$lang."\n");
    if($lang=="en" && self::$enabled==2) { 
      self::$enabled=0;
      return;
    }
    self::$enabled=2;
    self::$lang=$lang;
    $myFile=$path.self::$fileName;
    if(!file_exists($myFile)) {
      if($allowCreate) {
        touch($myFile);
        if(!file_exists($myFile) || !is_writable($myFile)) throw new AccessException ("Failed to create writable file ".$myFile.", check the permissions");
        $headLine=implode(self::$csvSeparator,self::$langs).self::$csvNl;
        file_put_contents($myFile,$headLine);
        self::$myFile=$myFile;
      }
      else throw new AccessException ("Failed to find file ".$myFile."!");
    }
    else {
      self::$myFile=$myFile;
      $lines=file($myFile,FILE_IGNORE_NEW_LINES+FILE_SKIP_EMPTY_LINES);
      //$headline=implode(self::csvSeparator,self::langs).self::csvNl;
      $line0=array_shift($lines);
      self::$langs=explode(self::$csvSeparator,$line0);
      //print_r(self::$langs);
      $langIndex=array_search($lang,self::$langs);
      if($langIndex===false) throw new UsageException("No language ".$lang." found in the dictionary");
      foreach( $lines as $line ) {
        //if (strlen($line)<2) continue; 
        $fields=explode(self::$csvSeparator,$line);
        if ( count($fields)!== count(self::$langs) ) throw new UsageException ("Wrong translations line:".$line);
        self::$lookup[$fields[0]]=$fields[$langIndex];
        self::$lookupKeys[]=$fields[0];
      }
      //print_r(self::$lookup);
    }
  }
  
  public static function go($string) {
    if (self::$enabled==0) return($string);
    if (in_array($string,self::$lookupKeys)) {
      if (self::$enabled==2) {
        $found=self::$lookup[$string];
        if(empty($found)) return ($string);
        return ($found);
      }
      else return ($string);
    }
    $line=$string;
    for ($i=1;$i<count(self::$langs);$i++) { $line.=self::$csvSeparator; }
    $line.=self::$csvNl;
    file_put_contents(self::$myFile,$line,FILE_APPEND);
    return($string);
  }

}
?>