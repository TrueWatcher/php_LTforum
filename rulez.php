<?php
/**
 * @pakage LTforum
 * @version 0.3.3 (needs admin panel and docs) (workable export-import) workable exp-imp-del-ea
 */
/**
 * LTforum Admin panel, common for all forum-threads, entry point.
 * Defines paths and includes LTmessageManager from the main script directory
 */

//$forumName="test";// canonical forum name
$adminTitle="LTforum messages manager";// page title
$mainPath="LTforum/";// relative to here
$templatePath="LTforum/templates/"; // // relative to here
$assetsPath="assets/"; // relative to here
$forumsPath=""; // relative to here

require_once ($mainPath."LTmessageManager.php");

?>


