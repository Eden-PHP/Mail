# Mail

Eden sports the three common ways to interact with mail servers including SMTP, IMAP and POP3. Accessing each protocol can be acheived like in `Figure 1`.

**Figure 1. Factory**

	$imap = eden('mail')->imap(
		'imap.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		993, 
		true);

	$pop3 = eden('mail')->pop3(
		'pop.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		995, 
		true);
	
	$smtp = eden('mail')->smtp(
		'smtp.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		465, 
		true);

For all three protocols there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.