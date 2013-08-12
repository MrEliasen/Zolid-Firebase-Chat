<?php
/**
 *  Zolid Chat - 0.1.0
 *
 *  A realtime chat based on the awesome technology from Firebase <3
 *
 *  @author     Mark Eliasen
 *  @copyright  (c) 2013 - Mark Eliasen
 *  @version    0.1.0
 */

define('CORE_PATH', dirname(__FILE__));
session_start();

if( empty($_SESSION['username']) )
{
	$_SESSION['username'] = uniqid('User_');
}

if( empty($_POST['request']) )
{
	$_SESSION['isadmin'] = ( !empty($_GET['admin']) ? true : false );
}

require_once(CORE_PATH . '/libs/php-jwt/JWT.php'); // Required for firebase
require_once(CORE_PATH . '/classes/security.class.php'); // Static class
require_once(CORE_PATH . '/classes/chat.class.php');

$Chat = new ZolidChat();