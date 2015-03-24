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
		mustLogIn = $ '.cache-buddy-must-log-in'
		if readCookie 'cache_buddy_id'
			loggedInMessage = $ '.cache-buddy-logged-in-as'
				.detach()
				.show()
			profileURL = loggedInMessage.data 'profile-url'
			loggedInMessage.find 'a[href=""]:empty'
				.html readCookie 'cache_buddy_username'
				.attr href: profileURL
			$ '.cache-buddy-comment-fields-wrapper'
				.html loggedInMessage
		else if mustLogIn.length
			$ "##{mustLogIn.data 'form-id'}"
				.html mustLogIn.detach().show()
 			$ '.comment-reply-link'
 				.hide()

		else if readCookie 'cache_buddy_comment_name'
			$('#author').val readCookie 'cache_buddy_comment_name'
			$('#email').val  readCookie 'cache_buddy_comment_email'
			$('#url').val    readCookie 'cache_buddy_comment_url'
