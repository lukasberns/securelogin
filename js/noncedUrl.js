function noncedUrl(url) {
	localStorage.nonce++;
	var nonceHash = hex_md5(localStorage.sessionAuthHash + localStorage.nonce);
	
	// split at '#'
	var indexH = url.indexOf('#');
	if (indexH == -1) {
		indexH = url.length;
	}
	var beforeH = url.substr(0, indexH);
	var hash = url.substr(indexH); // includes '#'
	
	// split at '?'
	var indexQ = beforeH.indexOf('?');
	if (indexQ == -1) {
		indexQ = beforeH.length;
	}
	var beforeQ = beforeH.substr(0, indexQ);
	var args_string = beforeH.substr(indexQ + 1); // excludes '?'
	
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