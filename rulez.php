<?php
/**
 * @pakage LTforum
 * @version 1.4 added ini files
 */
/**
 * LTforum Admin panel, common for all forum-threads, entry point.
 * Defines paths and includes LTmessageManager from the main script directory
 */

$adminEntryParams=[
  "mainPath"=>"LTforum/",/* relative to here */
  "templatePath"=>"LTforum/templates/",/* relative to here */
  "assetsPath"=>"assets/",/* relative to here */
  "forumsPath"=>""/* relative to here */
];

require_once ($adminEntryParams["mainPath"]."LTmessageManager.php");
?>


