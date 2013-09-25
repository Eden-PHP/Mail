# IMAP

*Eden's* IMAP object does uses `fsocket()` rather than PHP's built in IMAP functions. We chose to use `fsocket()` instead purely because of performance reasons. You'll find setting up IMAP is overall easier with *Eden*. `Figure 1` shows how to set up the IMAP object.

**Figure 1. Setting up the IMAP Object**

	$imap = eden('mail')->imap(
		'imap.gmail.com', 
		'your_email@gmail.com', 
		'[YOUR PASSWORD]', 
		993, 
		true);

Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.

## Mailboxes

The next thing we want to know are the available mailboxes we can set to active. To get a list of mailboxes follow `Figure 2`.

**Figure 2. Mailbox List**

	$mailboxes = $imap->getMailboxes(); 

Executing the above command will give you the following results.

	Array (
		[0] => Deleted Messages
		[1] => Drafts
		[2] => INBOX
		[3] => Junk E-mail
		[4] => Notes
		[5] => Sent Messages
		[6] => Trash
		[9] => [Gmail]/All Mail
		[10] => [Gmail]/Drafts
		[11] => [Gmail]/Important
		[12] => [Gmail]/Personal
		[13] => [Gmail]/Sent Mail
		[14] => [Gmail]/Spam
		[15] => [Gmail]/Starred
		[16] => [Gmail]/System
		[17] => [Gmail]/Trash
		[18] => [Gmail]/Unsorted
	)

Every mail server we looked at has the *INBOX* folder. So when in doubt, we would say this should be the default active mailbox. Next let's set the *INBOX* as the active mailbox. `Figure 3` shows how we would set and retrieve the active mailbox in one line.

**Figure 3. Set Active Mailbox FIRST!**

	echo $imap->setMailboxes('INBOX')->getActiveInbox(); //--> INBOX 

## Emails

Now that we have set the active mailbox, we can now continue to get a list of emails. `Figure 4` shows how we would go about doing that.

**Figure 4. Getting a List of Emails**

	$emails = $imap->getEmails(0, 3); 
	$count = $imap->getEmailTotal(); 

Executing the above figure would yield you results similar to the snippet below.

**Figure 4a. Example Email Results**

	Array (
		[0] => Array
			(
				[id] => <50041ab1c9383_178f6b3294527919f@job01.tmail>
				[parent] => 
				[topic] => Trending Startups and Updates
				[mailbox] => INBOX
				[uid] => 22363
				[date] => 1342446257
				[subject] => Trending Startups and Updates
				[from] => Array( [name] => AngelList [email] => noreply@angel.co )
				[flags] => Array ( [0] => seen )
				[to] => Array ( [0] => Array( [email] => youremail@gmail.com ) )
				[cc] => Array()
				[bcc] => Array()
			)
		 
		...
	)

As you might realize, the email list does not come with the full body nor the attachments. Standard *IMAP* practices explain you should not try to get a detailed list, because the mere size of the full data could take a long while. The idea for when you should retrieve the full details is when a user specifically call to action to see the body. `Figure 5` shows how we would get the details of an email.

**Figure 5. Get the Complete Email Details**

	$email = $imap->getUniqueEmails(22363, true); 
	echo $email['body']['text/html'];

In the figure above, we call `getUniqueEmails()` the first argument should be the unique identifier (UID) found in `Figure 4a`. The second argument is whether if you want the body, leave this true usually. You could get a group of emails comma separated by UIDs in the following manner.

**Figure 5a. Get More Than One Email Detail**

	$emails = $imap->getUniqueEmails(array(22363, 22364), true);

Including the body and attachments in a list request would definitely take a long time and is not recommended.

## Searching

Searching emails follows the basic IMAP format for searching. An example of how to search can be found in `Figure 6`.

Figure 6. Searching

	$emails = $imap->search(array('TO "youremail@gmail.com"'), 0, 3); 

Search is confined to the emails in the active mailbox. This is an IMAP standard. There is no work around for this.

The combinations of search queries can be referenced below:

	ALL - return all messages matching the rest of the criteria
	ANSWERED - match messages with the \\ANSWERED flag set
	BCC "string" - match messages with "string" in the Bcc: field
	BEFORE "date" - match messages with Date: before "date"
	BODY "string" - match messages with "string" in the body of the message
	CC "string" - match messages with "string" in the Cc: field
	DELETED - match deleted messages
	FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
	FROM "string" - match messages with "string" in the From: field
	KEYWORD "string" - match messages with "string" as a keyword
	NEW - match new messages
	OLD - match old messages
	ON "date" - match messages with Date: matching "date"
	RECENT - match messages with the \\RECENT flag set
	SEEN - match messages that have been read (the \\SEEN flag is set)
	SINCE "date" - match messages with Date: after "date"
	SUBJECT "string" - match messages with "string" in the Subject:
	TEXT "string" - match messages with text "string"
	TO "string" - match messages with "string" in the To:
	UNANSWERED - match messages that have not been answered
	UNDELETED - match messages that are not deleted
	UNFLAGGED - match messages that are not flagged
	UNKEYWORD "string" - match messages that do not have the keyword "string"
	UNSEEN - match messages which have not been read yet

## Actions

Some other actions you would probably like to perform is moving an email and removing an email. The following figures shows basically the rest of the possible actions you can perform with Eden.

**Figure 7. Move Email to Another Folder**

	$imap->move(22363, 'Notes'); 

**Figure 8. Delete Email**

	$imap->remove(22363, true); 

**Figure 9. Don't Forget to Disconnect!**

	$imap->disconnect(); 
