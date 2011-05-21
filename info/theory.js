server step1(<POST> $username </POST>) {
	$user = db.user($username)
	$challenge = random()
	if ($user) {
		$user.ip = $_SERVER['REMOTE_ADDR']
		$user.challenge = $challenge
		$salt1 = $user.salt1
		$salt2 = $user.salt2
	}
	else {
		$salt1 = random()
		$salt2 = random()
	}
	return { $challenge, $salt1, $salt2 }
}

server step2(<POST> $username, $responce, $hash2 </POST>) {
	$user = db.user($username)
	if (!$user
		or $user.ip != $_SERVER['REMOTE_ADDR']
		or $responce != hash($challenge . $user.hash1)
		or $user.hash23 != hash($user.salt3 . $hash2)) {
			$user.clear(challenge, ip)
			return false
	}
	$user.clear(challenge)
	$user.session_id = random()
	setCookie($user.session_id)
	return true
}


browser {
	get $username, $password from user input
	$step1 = step1($username)
	$hash1 = hash($step1.salt1 . $password)
	$hash2 = hash($step1.salt2 . $password)
	$responce = hash($step1.challenge . $hash1)
	
	if (step2($username, $responce, $hash2)) {
		// login successful
	}
	else {
		// login failed. try again
	}
}

/****************************************

Login attacks

Scenario 1: replay attack (all transfers visible)
	attacker knows: username, challenge, responce, hash2, salt1, salt2
	
	step2(username, responce, hash2)
	fails because challenge has been invalidated / changed

Scenario 2: all transfers visible
	attacker knows: username, challenge, responce, hash2, salt1, salt2
	
	step1(username) ok
	step2(username, responce, hash2)
	fails because attacker can't generate responce as he doesn't know hash1

Scenario 3: attacker got data inside the db
	attacker knows: username, hash1, hash23, salt1, salt2, salt3
	
	step2(username, responce, hash2)
	fails because attacker doesn't know hash2

Scenario 4: attacker can edit the db
x	well then he doesn't need to login at all

Scenario 5: attacker has db data and sees all transfer
	attacker knows from db: username, hash1, hash23, salt1, salt2, salt3
	   plus from transfers: challenge, responce, hash2
	
	step1(username) ok
	step2(username, responce, hash2) ok when responce gets newly calculated using step1.challenge
x	login successful

Scenario 6: forged login request
	attacker has an account and lets user login to his using CSRF
	e.g. post into a hidden iframe, get the responce, process it, post again...
	
	step1(username) ok
	step2(username, responce, hash) ok
x	attack successful


*/


// Session management

table invalidNonces { session_id, nonce }

server validate_session(<cookie> session_id, <get> nonce, nonceAuth) {
	$user = db.user($session_id)
	$nonceAuthComp = hash(hash($session_id . $user.hash1) . $nonce)
	if ($user.ip == $_SERVER['REMOTE_ADDR']
		and !db.invalidNonce($session_id, $nonce)
		and $nonceAuthComp == $nonceAuth) {
		dn.invalidateNonce($session_id, $noncehash)
		return true
	}
	else {
		return false
	}
}

browser {
	static nonce = 1000
	
	noncehash = hash(localStorage.sessionAuthHash . nonce++)
	validate_session(session_id, noncehash)
}

broser on login {
	hash1 = hash(salt1 . password) // <-- this must not be transferred
	localStorage.sessionAuthHash = hash(session_id + hash1)
}

/**************************

Session attacks

Scenario 1: most simple session hijacking
	attacker knows some session_id and sets his cookie to the same
	
	validate_session(session_id, nonce, noncehash)
	fails as we require a nonce
	
Scenario 2: session hijacking by someone who knows the system
	attacker knows some session_id and generates a random nonce
	
	validate_session(session_id, nonce, noncehash)
	fails as attacker can't generate the noncehash because he doesn't know sessionAuthHash nor hash1
	
	this approach requires javascript and for the cross-window method to work the html5 localStorage API
	
	we have to make sure that
	1. session_id cannot be reused
	   thus it needs to be generated and managed on the server
	2. a nonce cannot be reused in one session
	   ensured by incalidating used nonces in the server db
	3. sessionAuthHash is not sent in plaintext
	   it's saved in the browser and get's generated on the server for verification
	4. sessionAuthHash is available across browser windows
	   ensured by the use of localStorage instead of sessionStorage
	5. sessionAuthHash cannot be reused after session is invalidated
	   ensured by requiring it to contain session_id
	

Scenario 3: CSRF (cross site request forgery)
	attacker knows nothing
	attacker lets user load a page like /delete/important/stuff (GET or POST)
	if user is logged in, cookies are sent along --> session_id ok
	because of the same-origin policy of the localStorage API, a js script on a different site cannot access it
	
	validate_session(session_id, nonce, noncehash)
	fails as sessionAuthHash cannot be obtained

*/