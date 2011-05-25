<?php

$securelogin_dir = dirname(__FILE__);
$securelogin_root = str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $securelogin_dir);
if ($securelogin_root[0] != '/') $securelogin_root = "/$securelogin_root";
if (substr($securelogin_root, -1) == '/') $securelogin_root = substr($securelogin_root, 0, -1);

require($securelogin_dir.'/read_config.php');

if (!defined('SECURELOGIN_REQUIRE_NONCE')) {
	define('SECURELOGIN_REQUIRE_NONCE', false);
}

if (isset($_GET['step'])) {
	switch ($_GET['step']) {
		case 1:
			require($securelogin_dir.'/step1.php');
			die();
		case 2:
			require($securelogin_dir.'/step2.php');
			die();
	}
}

if (!isset($_COOKIE['session_id'])) {
	// display login form
	
	if (@$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'):
		echo '"Not logged in"';
	else:
	
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Login</title>
	<link rel="stylesheet" href="<?=$securelogin_root?>/css/styles.css" />
</head>
<body>
	<form>
		<h1>Login</h1>
		<label>Username <input type="text" name="username" disabled="" /></label>
		<label>Password <input type="password" name="password" disabled="" /></label>
		<input type="submit" value="Login" class="submit_button" disabled="" />
		<div class="error">
			<noscript>You have to turn javascript on to login</noscript>
		</div>
	</form>
	
	<script type="text/javascript">window.require_nonce = <?=SECURELOGIN_REQUIRE_NONCE?'true':'false'?>;</script>
	<script type="text/javascript" src="<?=$securelogin_root?>/js/jquery.js"></script>
	<script type="text/javascript" src="<?=$securelogin_root?>/js/md5.js"></script>
	<script type="text/javascript" src="<?=$securelogin_root?>/js/noncedUrl.js"></script>
	<script type="text/javascript" src="<?=$securelogin_root?>/js/loginform.js"></script>
</body>
</html>
<?
	
	endif;
	die();
}
else {
	// session_id present
	
	$session_id = $_COOKIE['session_id'];
	
	function validate_session($session_id) {
		// check if user is valid and session has not expired
		$accountsTable = SECURELOGIN_ACCOUNTS_TABLE;
		$sessionsTable = SECURELOGIN_SESSIONS_TABLE;
		$usedNoncesTable = SECURELOGIN_USED_NONCES_TABLE;
		
		$q = sprintf(
			"SELECT * FROM `$accountsTable` a
			JOIN `$sessionsTable` s
			ON a.id = s.account
			WHERE s.id = '%s'
			AND expire > NOW()
			AND ip = '%s'
			LIMIT 1",
			mysql_real_escape_string($session_id),
			mysql_real_escape_string($_SERVER['REMOTE_ADDR']));
		$qr = mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		$user = mysql_fetch_assoc($qr);
		
		if (!$user) {
			return false;
		}
		
		if (!SECURELOGIN_REQUIRE_NONCE) {
			return true;
		}
		
		if (!isset($_GET['nonce']) or !isset($_GET['nonceHash'])) {
			return false;
		}
		
		$nonce = $_GET['nonce'];
		$nonceHash = $_GET['nonceHash'];
		
		// check if nonce has not been used
		$q = sprintf(
			"SELECT COUNT(session_id) FROM `$usedNoncesTable`
			WHERE session_id = '%s'
			AND nonce = '%s'",
			mysql_real_escape_string($session_id),
			mysql_real_escape_string($nonce));
		$qr = mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		
		if (mysql_result($qr, 0) > 0) {
			return false;
		}
		
		// check if nonce matches nonceHash
		if ($nonceHash != md5($user['sessionAuthHash'] . $nonce)) {
			return false;
		}
		
		// session is valid, so invalidate nonce
		$q = sprintf(
			"INSERT INTO `$usedNoncesTable` (session_id, nonce)
			VALUES ('%s', '%s')",
			mysql_real_escape_string($session_id),
			mysql_real_escape_string($nonce));
		mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		
		return true;
	}
	
	if (!validate_session($session_id) or isset($_GET['logout'])) {
		// session timeout management,
		// session hijack and csrf prevention
		
		$sessionsTable = SECURELOGIN_SESSIONS_TABLE;
		
		// log user out
		$q = sprintf("DELETE FROM `$sessionsTable` WHERE id = '%s'", mysql_real_escape_string($session_id));
		mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		
		setcookie('session_id', 'deleted', time()-30000000);
		
		$url = preg_replace('/&?logout=?/', '', $_SERVER['REQUEST_URI']);
		if (substr($url, -1) == '?') {
			$url = substr($url, 0, -1);
		}
		header('Location: '.$url);
		die();
	}
}

unset($securelogin_dir);
unset($securelogin_root);

// only valid logins can continue
