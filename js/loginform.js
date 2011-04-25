$(function() {
	$('.submit_button').show();
	username_input = $('[name=username]');
	password_input = $('[name=password]');
	
	var form = $('form')
	.submit(function() {
		try {
			if (!username_input.val()) { username_input.focus(); throw 0; }
			if (!password_input.val()) { password_input.focus(); throw 0; }
			
			var username = username_input.val();
			var password = password_input.val();
			
			$.get('?step=1', { username: username }, function(step1) {
				
				// process resp and go to step2
				var hash1 = hex_md5(step1.salt1 + password);
				var hash2 = hex_md5(step1.salt2 + password);
				var resp = hex_md5(step1.challenge + hash1);
				var session_id = step1.session_id
				
				$.post('?step=2', { session_id: session_id, hash2: hash2, resp: resp }, function(step2) {
					if (!step2 || isNaN(step2)) {
						// login failed / error
						// clear password field and show error message
						console.log('Login error: '+step2);
					}
					else {
						// login succeeded
						// add sessionAuthHash to localStorage and reload
						console.log('Logged in. Session id: '+session_id);
						
						localStorage.sessionAuthHash = hex_md5(session_id + hash1);
						localStorage.nonce = 10000; // TODO: randomize? limit?
						var nonceHash = hex_md5(localStorage.sessionAuthHash + localStorage.nonce);
						location.href += '?&nonce=' + localStorage.nonce + '&noncehash=' + nonceHash;
					}
				}, 'json');
				
			}, 'json');
		}
		finally {
			return false;
		}
	});
});