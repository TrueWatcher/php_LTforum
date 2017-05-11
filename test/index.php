<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */
/**
 * Forum thread entry point
 * Defines paths (possibly thread-specific), then passes to main module (common for all threads)
 */

//echo ("I'm LTforum/test/index.php");

$threadEntryParams=[
  "forum"=>"test",/* canonical forum name */
  "title"=>"The dedicated tests thread",/* page title */
  "mainPath"=>"../LTforum/",/* relative to here */
  "templatePath"=>"templates/",/* relative to main LTforum folder */
  "assetsPath"=>"../assets/",/* relative to main LTforum folder */
  "lang"=>"mock"/* "ru" "mock" "en" */
];

require_once ($threadEntryParams["mainPath"]."LTforum.php");
?>