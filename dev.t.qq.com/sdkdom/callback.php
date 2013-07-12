<?php
@header('Content-Type:text/html;charset=utf-8'); 
session_start();
@require_once('config.php');
@require_once('oauth.php');
@require_once('opent.php');

$o = new MBOpenTOAuth( MB_AKEY , MB_SKEY , $_SESSION['keys']['oauth_token'] , $_SESSION['keys']['oauth_token_secret']  );
$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;//获取ACCESSTOKEN
$_SESSION['last_key'] = $last_key;
header('Location: cmdlist.php');
exit();
?>