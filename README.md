![logo](http://eden.openovate.com/assets/images/cloud-social.png) Eden Mail
----
[![Build Status](https://api.travis-ci.org/Eden-PHP/Mail.png)](https://travis-ci.org/Eden-PHP/Mail)
----

- [Install](#install)
- [Introduction](#intro)
- [IMAP](#imap)
- [POP3](#pop3)
- [SMTP](#smtp)
- [Contributing](#contributing)

----

<a name="install"></a>
## Install

`composer require eden/mail`

In order to use

----

## Enable Eden

The following documentation uses `eden()` in its example reference. Enabling this function requires an extra step as descirbed in this section which is not required if you access this package using the following.

```
Eden\Core\Control::i();
```

When using composer, there is not an easy way to access functions from packages. As a workaround, adding this constant in your code will allow `eden()` to be available after. 

```
Eden::DECORATOR;
```

For example:

```
Eden::DECORATOR;

eden()->inspect('Hello World');
```

----

<a name="intro"></a>
## Introduction

Eden sports the three common ways to interact with mail servers including SMTP, IMAP and POP3. Accessing each protocol can be acheived like in `Figure 1`.

**Figure 1. Factory**

```
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
```

For all three protocols there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.

----

<a name="imap"></a>
## IMAP

*Eden's* IMAP object does uses `fsocket()` rather than PHP's built in IMAP functions. We chose to use `fsocket()` instead purely because of performance reasons. You'll find setting up IMAP is overall easier with *Eden*. `Figure 2` shows how to set up the IMAP object.

**Figure 2. Setting up the IMAP Object**

```
$imap = eden('mail')->imap(
    'imap.gmail.com', 
    'your_email@gmail.com', 
    '[YOUR PASSWORD]', 
    993, 
    true);
```
Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.

### Mailboxes

The next thing we want to know are the available mailboxes we can set to active. To get a list of mailboxes follow `Figure 3`.

**Figure 3. Mailbox List**

```
$mailboxes = $imap->getMailboxes(); 
```

Executing the above command will give you the following results.

```
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
```

Every mail server we looked at has the *INBOX* folder. So when in doubt, we would say this should be the default active mailbox. Next let's set the *INBOX* as the active mailbox. `Figure 4` shows how we would set and retrieve the active mailbox in one line.

**Figure 4. Set Active Mailbox FIRST!**

```
echo $imap->setMailboxes('INBOX')->getActiveInbox(); //--> INBOX 
```

### Emails

Now that we have set the active mailbox, we can now continue to get a list of emails. `Figure 5` shows how we would go about doing that.

**Figure 5. Getting a List of Emails**

```
$emails = $imap->getEmails(0, 3); 
$count = $imap->getEmailTotal(); 
```

Executing the above figure would yield you results similar to the snippet below.

**Figure 5a. Example Email Results**

```
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
```

As you might realize, the email list does not come with the full body nor the attachments. Standard *IMAP* practices explain you should not try to get a detailed list, because the mere size of the full data could take a long while. The idea for when you should retrieve the full details is when a user specifically call to action to see the body. `Figure 6` shows how we would get the details of an email.

**Figure 6. Get the Complete Email Details**

```
$email = $imap->getUniqueEmails(22363, true); 
echo $email['body']['text/html'];
```

In the figure above, we call `getUniqueEmails()` the first argument should be the unique identifier (UID) found in `Figure 5a`. The second argument is whether if you want the body, leave this true usually. You could get a group of emails comma separated by UIDs in the following manner.

**Figure 6a. Get More Than One Email Detail**

```
$emails = $imap->getUniqueEmails(array(22363, 22364), true);
```

Including the body and attachments in a list request would definitely take a long time and is not recommended.

### Searching

Searching emails follows the basic IMAP format for searching. An example of how to search can be found in `Figure 7`.

Figure 7. Searching

```
$emails = $imap->search(array('TO "youremail@gmail.com"'), 0, 3); 
```

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

### Actions

Some other actions you would probably like to perform is moving an email and removing an email. The following figures shows basically the rest of the possible actions you can perform with Eden.

**Figure 8. Move Email to Another Folder**

```
$imap->move(22363, 'Notes'); 
```

**Figure 9. Delete Email**

```
$imap->remove(22363, true); 
```

**Figure 10. Don't Forget to Disconnect!**

```
$imap->disconnect(); 
```

----

<a name="pop3"></a>
## POP3

*Eden's* POP3 object does uses `fsocket()` rather than PHP's built in IMAP functions for POP3. We chose to use `fsocket()` instead purely because of performance reasons. You'll find setting up POP3 is overall easier with *Eden*. `Figure 11` shows how to set up the POP3 object.

**Figure 11. Setting up the POP3 Object**

```
$pop3 = eden('mail')->pop3(
    'pop.gmail.com', 
    'your_email@gmail.com', 
    '[YOUR PASSWORD]', 
    995, 
    true);
```

Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL.Now that we have set the connection information, we can now continue to get a list of emails. `Figure 12` shows how we would go about doing that.

**Figure 12. Get Emails**

```
$emails = $pop3->getEmails(0, 10); 
$count = $pop3->getEmailTotal();
```

Executing the above figure would return you a list of emails as well as the total count. Some other     actions you would probably like to perform is removing an email. The following figures shows basically the rest of the possible actions you can perform with *Eden*.

**Figure 13. Delete Email**

```
$pop3->remove(100); 
```

**Figure 14. Don't Forget to Disconnect!**

```
$pop3->disconnect(); 
```

----

<a name="smtp"></a>
## SMTP

SMTP in Eden is relatively easier. We use `fsocket()` rather than PHP's built in SMTP functions. We chose to use `fsocket()` because PHP's `mail()` function will most likely put your mail in the junk folder. SMTP works better because emails sent is sent by your actual email server versus your web host. `Figure 15` shows how to set up the SMTP object.

**Figure 15. Setting up the SMTP Object**

```
$smtp = eden('mail')->smtp(
    'smtp.gmail.com', 
    'your_email@gmail.com', 
    '[YOUR PASSWORD]', 
    465, 
    true);
```

Very simply, there are four requirements that's needed and all requirements are dependent on your specific email provider. In the figure above we use GMAIL settings as an example. The last argument is a flag. Set it to true if your email provider requires SSL. The next part is simply send your email.

**Figure 16. Simply Send**

```
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
```

In `Figure 16`, we basically laid out all the possible combinations of methods you can use to send email. It's important to set the `addTo()` method at least once.

**Figure 17. Don't Forget to Disconnect!**

```
$smtp->disconnect(); 
```

----

<a name="contributing"></a>
# Contributing to Eden

Contributions to *Eden* are following the Github work flow. Please read up before contributing.

## Setting up your machine with the Eden repository and your fork

1. Fork the repository
2. Fire up your local terminal create a new branch from the `v4` branch of your 
fork with a branch name describing what your changes are. 
 Possible branch name types:
    - bugfix
    - feature
    - improvement
3. Make your changes. Always make sure to sign-off (-s) on all commits made (git commit -s -m "Commit message")

## Making pull requests

1. Please ensure to run `phpunit` before making a pull request.
2. Push your code to your remote forked version.
3. Go back to your forked version on GitHub and submit a pull request.
4. An Eden developer will review your code and merge it in when it has been classified as suitable.
