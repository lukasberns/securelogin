$(function() {
	var form = $('form');
	var username_input = form.find('[name=username]');
	var password_input = form.find('[name=password]');
	var submit_button = form.find('.submit_button');
	var inputs = username_input.add(password_input).add(submit_button);
	var error_message = form.find('.error');
	
	username_input.add(password_input).keydown(function() {
		error_message.html('');
	});
	
	form.submit(function() {
		try {
			var username = username_input.val();
			var password = password_input.val();
			
			if (!username) {
				username_input.focus();
				error_message.html('Please enter your username.');
				throw 0;
			}
			if (!password) {
				password_input.focus();
				error_message.html('Please enter your password.');
				throw 0;
			}
			
			inputs.attr('disabled', 'disable');
			
			$.get('?step=1', { username: username }, function(step1) {
				
				// process resp and go to step2
				var hash1 = hex_md5(step1.salt1 + username + password);
				var hash2 = hex_md5(step1.salt2 + username + password);
				var resp = hex_md5(step1.challenge + hash1);
				var session_id = step1.session_id;
				
				$.post('?step=2', { session_id: session_id, hash2: hash2, resp: resp }, function(step2) {
					if (!step2 || isNaN(step2)) {
						// login failed / error
						// clear password field and show error message
						console.log('Login error: '+step2);
						
						inputs.removeAttr('disabled');
						password_input.val('').focus();
						error_message.html('Username or password wrong.');
					}
					else {
						// login succeeded
						// add sessionAuthHash to localStorage and reload
						console.log('Logged in. Session id: '+session_id);
						
						localStorage.sessionAuthHash = hex_md5(session_id + hash1);
						localStorage.nonce = 10000; // TODO: randomize? limit?
						
						location.reload();
					}
				}, 'json');
				
			}, 'json');
		}
		finally {
			return false;
		}
	});
	
	inputs.removeAttr('disabled');
	username_input.focus();
});
