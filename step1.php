<?php

// search for user and return salts and a challenge

if (!isset($_GET['username'])) {
	die('"Error: No username specified"');
}

require('db.php');

// delete old session data
$q = "DELETE FROM sessions WHERE expire < NOW()";
mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);

$username = $_GET['username'];
$q = sprintf("SELECT * FROM accounts WHERE username = '%s'", mysql_real_escape_string($username));
$qr = mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
$user = mysql_fetch_assoc($qr);

$session_id = md5(uniqid(rand(), true));
$challenge = md5(uniqid(rand(), true));
if (!$user) {
	// user does not exist.
	// generate random salts so attacker does not know this account does not exist
	
	$salt1 = md5(rand());
	$salt2 = md5(rand());
}
else {
	$salt1 = $user['salt1'];
	$salt2 = $user['salt2'];
	
	$sessionAuthHash = md5($session_id . $user['hash1']);
	
	$q = sprintf(
		"INSERT INTO sessions (id, account, ip, challenge, expire, sessionAuthHash)
		VALUES ('%s', '%s', '%s', '%s', NOW() + INTERVAL 10 SECOND, '%s')",
		$session_id,
		$user['id'],
		$_SERVER['REMOTE_ADDR'],
		$challenge,
		$sessionAuthHash
		);
	mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
}

echo json_encode(array(
	'session_id' => $session_id,
	'challenge' => $challenge,
	'salt1' => $salt1,
	'salt2' => $salt2
));
