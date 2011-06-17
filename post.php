<?php 
/**
 *
 * @package c2dm
 * @version $Id$
 * @copyright (c) 2011 lytsing.org
 *
 */

include_once('c2dm.php');

$c2dm = new c2dm();
$c2dm->getAuthToken("your-gmail", "yoour-gmail-passwd");
$c2dm->sendMessage("long-registration ID", 1);
 
?>

