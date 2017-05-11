<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */
/**
 * Forum thread entry point
 * Defines paths (possibly thread-specific), then passes to main module (common for all threads)
 */

//echo ("I'm LTforum/chat/index.php");

$threadEntryParams=[
  "forum"=>"chat",/* canonical forum name */
  "title"=>"Chat room",/* page title */
  "mainPath"=>"../LTforum/",/* relative to here */
  "templatePath"=>"templates/",/* relative to main LTforum folder */
  "assetsPath"=>"../assets/",/* relative to main LTforum folder */
  /* "lang"=>"mock" en */
];

require_once ($threadEntryParams["mainPath"]."LTforum.php");
?>