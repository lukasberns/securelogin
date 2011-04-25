<?php

$securelogin_dir = dirname(__FILE__);
$securelogin_root = str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $securelogin_dir);
if ($securelogin_root[0] != '/') $securelogin_root = "/$securelogin_root";
if (substr($securelogin_root, -1) == '/') $securelogin_root = substr($securelogin_root, 0, -1);

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
	?>
	
	<link rel="stylesheet" href="<?=$securelogin_root?>/css/styles.css" />
	
	<form>
		<label>Username <input type="text" name="username" value="user" /></label>
		<label>Password <input type="password" name="password" value="password" /></label>
		<noscript>You have to turn javascript on to login</noscript>
		<input type="submit" value="Login" class="submit_button" />
	</form>
	
	<script>window.securelogin_root = '<?=htmlentities($securelogin_root)?>'</script>
	<script src="<?=$securelogin_root?>/js/jquery.js"></script>
	<script src="<?=$securelogin_root?>/js/md5.js"></script>
	<script src="<?=$securelogin_root?>/js/loginform.js"></script>
	
	<?
	
	die();
}
else {
	// session_id present
	
	$session_id = $_COOKIE['session_id'];
	
	function validate_session($session_id, $nonce, $nonceHash) {
		// check if user is valid and session has not expired
		$q = sprintf(
			"SELECT * FROM accounts a
			JOIN sessions s
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
		
		// check if nonce has not been used
		$q = sprintf(
			"SELECT COUNT(session_id) FROM usedNonces
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
			"INSERT INTO usedNonces (session_id, nonce)
			VALUES ('%s', '%s')",
			mysql_real_escape_string($session_id),
			mysql_real_escape_string($nonce));
		mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		
		return true;
	}
	
	if (!isset($_GET['nonce'])
		or !isset($_GET['noncehash'])
		or !validate_session($session_id, $_GET['nonce'], $_GET['noncehash'])) {
		// session timeout management,
		// session hijack and csrf prevention
		
		// log user out
		$q = sprintf("DELETE FROM sessions WHERE id = '%s'", mysql_real_escape_string($session_id));
		mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
		
		setcookie('session_id', 'deleted', time()-30000000);
		header('Location: '.$_SERVER['REQUEST_URI']);
		die();
	}
}

unset($securelogin_dir);
unset($securelogin_root);

// only valid logins can continue
