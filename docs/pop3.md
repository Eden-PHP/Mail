# POP3

*Eden's* POP3 object does uses `fsocket()` rather than PHP's built in IMAP functions for POP3. We chose to use `fsocket()` instead purely because of performance reasons. You'll find setting up POP3 is overall easier with *Eden*. `Figure 1` shows how to set up the POP3 object.

**Figure 1. Setting up the POP3 Object**

	$pop3 = eden('mail')->pop3(
		'pop.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		995, 
		true);

Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.Now that we have set the connection information, we can now continue to get a list of emails. `Figure 2` shows how we would go about doing that.

**Figure 2. Get Emails**

	$emails = $pop3->getEmails(0, 10); 
	$count = $pop3->getEmailTotal();

Executing the above figure would return you a list of emails as well as the total count. Some other 	actions you would probably like to perform is removing an email. The following figures shows basically the rest of the possible actions you can perform with *Eden*.

**Figure 3. Delete Email**

	$pop3->remove(100); 

**Figure 4. Don't Forget to Disconnect!**

	$pop3->disconnect(); 
