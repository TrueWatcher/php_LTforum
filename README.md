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

You can do with only FTP access to your site. To prepare Apache's passwords file you'll need a working Linux machine or SSH access to you server.  
Put all the files and folders into your target directory.  
Try http:://your_site/forum_directory/test  
If your see an error "unknown class SQLite3", your server needs PHP SQLite extension, it may happen, despite what php.net says about native support :(   
(sudo apt-get install php5-sqlite, sudo service apache2 restart).  
If your see a page with one or more messages, it's all right.  
Try http:://your_site/forum_directory/rulez.php?forum=test&pin=1  
You should see a panel of Messages Manager.  
Now it's time to set up Apache's HTTP Basic Authentication.  
Find a system path to your forum_directory and its "LTforum" folder. If you haven't got it (e.g. on a public hoster), go to "LTforum" folder, find ".htaccess" and rename it to "txt.htaccess". Then try http:://your_site/forum_directory/report_full_path.php and store the result. Remember to rename "txt.htaccess" back to ".htaccess".  
Go to the folder "demo", open "demo.htaccess" and insert full system path into it (two times). Then save it as ".htaccess" to the same folder. Upload this file to the same folder of your site.  
Do the same with the folder "chat".  
Do the same with root.htaccess in the forum root folder.  
Create the file ".users" in LTforum folder (unless your are happy with preset users/passwords, see LTforum/basic_authentication.txt).  
(htpasswd -cbB ".users" user1 password1 , htpasswd -bB ".users" user2 password2 , ...). Spaces and cyrillic letters in usernames are tolerated.  
Create the file ".groups" in a text editor. Arrange all users to groups "chat" and "demo" and administrator(s) to the group "admin".  
(chet: user1 user2 \n demo: user1 user2 \n admin: user1)  
Upload the files .users and .groups into "LTforum" folder.  
Optionally add "Options +Indexes" to the forum root folder's .htacces and remove index.html from there.  
Try http:://your_site/forum_directory/chat and http:://your_site/forum_directory/chat  
Try also http:://your_site/forum_directory/rulez.php?forum=demo and http:://your_site/forum_directory/rulez.php?forum=chat  as administrator.  
If your get a "500 Error" or do not get a login form -- something is wrong, most probably with paths in ".htaccess" file(s).  



##Adding/removing forums

Any forum folder can be simply removed at any time and can be put back same easily. All forum-specific settings are contained in its "index.php", all messages - in "forum_name.db", access parameters - in its ".htaccess" (and in common ".groups").  
To create a new forum:  
Create your new folder in the forum root folder.  
Copy the "index.php" from "demo" folder. Open it in text editor, modify $forumName="demo" with your new folder name and $forumTitle="Just another open miniforum" with your title. Put the edited "index.php" into your folder.  
Copy the ".htaccess" from "demo" folder. Replace "group demo" with "group your_new_forum". Put it to the new folder.  
Open the file ".groups" and add "your_new_forum: user1 user2". Put it back.  
This completes the preparations. The database will be created automatically.  
Try http:://your_site/forum_directory/your_new_forum  
If you don't like introductort message, add your own.  
Try http:://your_site/forum_directory/rulez.php?forum=your_new_forum as administrator.  
If you have added your welcome message, remove the messages from 1 to 1.  
If your have enough rights to set up HTTPS, your are advised to do it also ;).  


##Versions history
v.0.1.0    7 Aug 2016
started

v.0.3.3    28 Aug 2016
cleaned and tested view-add-edit-delete, workable import-export-delRange-editAny
need docs, more testing and .htaccess

v.1.0.0    31 Aug 2016
primary testing complete, ready for experimental deployment

v.1.0.6    6 Sep 2016
added "#footer" and fixed singletAssocWrapper
 
v.1.1.0    6 Sep 2016
"Search" command

v.1.1.2    9 Sep 2016
refactoring Viewers, new tests, improvements 

#TODOs
 
Nice frontend, possibly on Bootstrap  
RSS generator/formatter  
Conception of localization  
Access Controller  
Users and threads Manager  

