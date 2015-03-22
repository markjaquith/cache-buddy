do ($ = jQuery) ->
	readCookie = (cname) ->
		name = "#{cname}="
		ca = document.cookie.split ';'
		for c in ca
			while c.charAt(0) is ' '
				c = c.substring 1
			if c.indexOf(name) is 0
				return decodeURIComponent c.substring(name.length, c.length).replace(/\+/, ' ')
		''

	$ ->
		$('#author').val readCookie 'cache_buddy_comment_name'
		$('#email').val readCookie 'cache_buddy_comment_email'
		$('#url').val readCookie 'cache_buddy_comment_url'
