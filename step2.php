<?php

if (!isset($_POST['session_id']) or !isset($_POST['hash2']) or !isset($_POST['resp'])) {
	die('"session_id, hash2 or resp missing"');
}

require('db.php');

$session_id = $_POST['session_id'];
$q = sprintf(
	"SELECT *
	FROM sessions s
	INNER JOIN accounts a
	ON s.account = a.id
	WHERE s.id = '%s'
	AND s.ip = '%s'
	AND s.challenge != ''
	AND s.expire > NOW()
	LIMIT 1", mysql_real_escape_string($session_id), $_SERVER['REMOTE_ADDR']);
$qr = mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);

if (mysql_num_rows($qr) < 1) {
	// user does not exist.
	// simply return 0 to indicate login error
	
	echo '0';
	die();
}

$session = mysql_fetch_assoc($qr);
if (md5($session['challenge'].$session['hash1']) == $_POST['resp']
	and $session['hash23'] == md5($session['salt3'] . $_POST['hash2'])) {
	// login successful
	
	$q = sprintf("UPDATE sessions SET expire = NOW() + INTERVAL 2 HOUR, challenge = '' WHERE id = '%s'", mysql_real_escape_string($session_id));
	mysql_query($q) or trigger_error(mysql_error(), E_USER_ERROR);
	
	setcookie('session_id', $session_id, time()+2*3600);
	
	echo "1";
	die();
}

echo "0";
die();