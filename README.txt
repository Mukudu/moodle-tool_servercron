Installation Notes
==================
################# WARNING:  THIS PLUGIN IS NOT FOR USE ON WINDOWS SERVERS #######################################################

Admin Plugin - Server Cron Manager

Pre-requisites
==============
You must have access to the server containing Moodle. This can be direct access, through a network or to a remote server through Internet with an FTP client, you can't do it from "inside" Moodle itself.

INSTALLATION
============
1.  Latest version of the zipped file for this plug is available from https://github.com/bencellis/moodle-tool_servercron

2.  Unzip the zipped file somewhere on your local computer and rename the folder bencellis-moodle-tool_servercron-xxxxxx to servercron

3.  Upload the unzipped folder to /local/ folder in the moodle root folder e.g /var/www/html/ on each of the Moodle servers

4.  Alternatively the zip file can be uploaded to the folder in step 3 and the zipped file unzipped on the servers.

5.  Ensure that the folder has the same permissions and owner as the other folders in the directory -

    1.  chown -R apache:apache servercron
    2.  chmod -R 755 servercron

6.  In your browser, go to your Moodle site, login as administrator and choose Site Administration > Notifications  and click on the Continue Button.

7.  Moodle will report successful completion or any errors.  Click on continue.

UNINSTALLATION
==============
1.  In your browser, go to your Moodle site, login as administrator and choose SiteAdministration -> Plugins -> Local plugins -> Manage Local Plugins, find the plugin's entry and select 'Delete'

2.  Select 'Continue' on the next page

3.  Delete the relevant folder /admin/tool/servercron from the moodle root e.g. /var/www/html/blocks/servercron then select Continue in the browser.

4.  The plugin should no longer appear in the list.
