# SMTP

SMTP in Eden is relatively easier. We use `fsocket()` rather than PHP's built in SMTP functions. We chose to use `fsocket()` because PHP's `mail()` function will most likely put your mail in the junk folder. SMTP works better because emails sent is sent by your actual email server versus your web host. `Figure 1` shows how to set up the SMTP object.

**Figure 1. Setting up the SMTP Object**

	$smtp = eden('mail')->smtp(
		'smtp.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		465, 
		true);

Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL. The next part is simply send your email.

**Figure 2. Simply Send**

	$smtp->setSubject('Welcome!')
		->setBody('<p>Hello you!</p>', true)
		->setBody('Hello you!')
		->addTo('email1@gmail.com')
		->addTo('email2@gmail.com')
		->addCC('email3@gmail.com')
		->addCC('email4@gmail.com')
		->addBCC('email5@gmail.com')
		->addBCC('email6@gmail.com')
		->addAttachment('file.jpg', '/path/to/file.jpg', 'mime-type')
		->send();

In `Figure 2`, we basically laid out all the possible combinations of methods you can use to send email. It's important to set the `addTo()` method at least once.

**Figure 3. Don't Forget to Disconnect!**

	$smtp->disconnect(); 
