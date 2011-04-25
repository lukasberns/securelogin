function noncedUrl(url) {
	localStorage.nonce++;
	var nonceHash = hex_md5(localStorage.sessionAuthHash + localStorage.nonce);
	
	// split at '?'
	var indexQ = url.indexOf('?');
	if (indexQ == -1) {
		indexQ = url.length;
	}
	var beforeQ = url.substr(0, indexQ);
	var afterQ = url.substr(indexQ + 1); // excludes '?'
	
	// split at '#'
	var indexH = afterQ.indexOf('#');
	if (indexH == -1) {
		indexH = afterQ.length;
	}
	var args_string = afterQ.substr(0, indexH);
	var hash = afterQ.substr(indexH); // includes '#'
	
	// parse args
	var args_array = args_string.split('&');
	var args = {};
	for (var i = 0, l = args_array.length; i < l; i++) {
		var split = args_array[i].split('=');
		args[split.shift()] = split.join('=');
	}
	
	// modify args
	args['nonce'] = localStorage.nonce;
	args['nonceHash'] = nonceHash;
	delete args['logout'];
	
	// reassemble url
	args_array = [];
	for (i in args) {
		if (i) {
			args_array.push(i+'='+args[i]);
		}
	}
	var noncedUrl = beforeQ + '?' + args_array.join('&') + hash;
	
	return noncedUrl;
}