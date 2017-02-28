# php_LTforum

##Description

Minimalistic forum engine on Linux Apache - PHP - SQLite.



##Targets

Easy communication tool like basic_forum/offline_messenger/guestbook,  
which is simple enough to be used without instructions,  
and can work on public hosters and cloud instances.  
Support for several forum threads, as independent as possible.  
Easy addition/removal/transfer of contents.  
An exercise in OOP :)



##Installation

You can do with only FTP access to your site. To feel more confident about passwords, SSH/SFTP is recommended.  

Just put all the files and folders into your target directory ( not nesessarily the site root, http://somesite.com/myAwfullForum/ will also do ).  
  
Check that all's working alright with the /test thread, which normally contains some residual data from automatic testings:  
Try frontend: http:://your_site[/forum_directory]/test , login/password: admin/admin  
If your see an error "unknown class SQLite3", your server needs PHP SQLite extension, it may happen, despite what php.net says about native support :(   
(sudo apt-get install php5-sqlite, sudo service apache2 restart).  
If your see a page with one or more messages, it's all right.  
Try backend: http:://your_site[/forum_directory]/rulez.php?forum=test  
You should see a panel of Messages Manager. If for some reason you again see the login form, repeat admin/admin  
You may try adding new users, promoting them to administrator, and removing ( to remove a user through AdminPanel you need his/her password ).  
You may do the same with /demo or /chat folders/threads.


##Adding/removing forums

Any forum folder can be simply removed at any time and can be put back same easily. All forum-specific settings are contained in its "index.php" file, all messages are stored in "forum_name.db" file, users' names and password hashes - in its ".group" file ( ".db" and ".group" are created automatically on the first script call and are initialized with one user admin/admin ).  
  
To create a new forum "your_new_forum":  
Create a folder "your_new_forum" in the forum root folder.  
Copy the "index.php" from "/demo" folder. Open it in text editor, change $forumName="demo" into $forumName="your_new_forum" and $forumTitle="Just another open miniforum" into  $forumTitle="your_title". Put the edited "index.php" into your new folder.  
If you happened to copy any of ".group" or "*.db" or ".htaccess" files, remove them.   
Try frontend: http:://your_site[/forum_directory]/your_new_forum , login/password: admin/admin .  
You should see a forum page with one welcome message.  
Try AdminPanel: http:://your_site[/forum_directory]/rulez.php?forum=your_new_forum  
Add a new user with some non-trivial name and long password.   
Add this new user to administrators.  
( If you have secure file access, but no HTTPS on site, you'd be more safe to click "Generate Entry" and manually insert the resulting string into the newly created "/your_new_forum/.group" file, section "[your_new_forum]", followed by entry "newUserName=" in the section "[your_new_forumAdmins]" )  
Add all your other users with their passwords.  
Remove user admin/admin ( to remove a user through AdminPanel you need his/her password ).  
Click "Log out" and try your new administrator on backend and new users on frontend.  
You may also want to add your own welcome message and remove the automatic one through the AdminPanel ( Delete message block from 1 to 1 ), or simply change it in-place ( Edit any message id=1 ).  
  
If your have enough rights to set up HTTPS, your are advised to do it ;).  
If HTTPS is unavailable, it's recommended to enforce JS-Digest authentication:  
open /LTforum/LTforum.php and in line 73 set "authMode"=>2  
then do the same with /LTforum/LTmessageManager.php , line 61  
Note that it can protect your passwords only from passive eavesdropping, not from a deliberate attack.


##Versions history
v.0.1.0    7 Aug 2016
started

v.1.0.0    31 Aug 2016
primary testing complete, ready for experimental deployment
 
v.1.1.0    6 Sep 2016
"Search" command

v.1.1.5    20 Nov 2016
improvements for narrow screen, probably last version in 1.1 line
(now moved to the branch "Apache_Http_Authentication")

v.1.2.11   4 Dec 2016
new AccessController and UserManager, tests, refactoring

v.1.2.22   9 Jan 2017
rewritten AccessController, unit tests, feature "number of unanswered messages" on registration form



#TODOs

Conception of localization  
Nice frontend  
RSS generator/formatter  
