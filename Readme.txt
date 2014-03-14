FOLDER NAME STRUCTURE

skeletongateway
The folder name must be in lowercase and have no spaces or weird characters.



FILE NAME STRUCTURE

callback.php
This file will be called by the gateway to send notifications about the payment transactions.
Must remember to update the line:
$_GET['plugin'] = 'skeletongateway'; //replace 'skeletongateway' with the respective plugin folder name.

PluginSkeletongateway.php
This file will contain the plugin code. This is the one that sends the requests to the gateway.
The naming of the file must be:
Plugin[Pluginfoldername].php
where [Pluginfoldername] is the exact same name you used for the folder, but with the first letter in uppercase.

PluginSkeletongatewayCallback.php
This file will contain the callback code. This is the one that process the responses from the gateway.
The naming of the file must be:
Plugin[Pluginfoldername]Callback.php
where [Pluginfoldername] is the exact same name you used for the folder, but with the first letter in uppercase.

skeletongateway.gif
This file is just an optional iconic image associated to the plugin.
The naming of the file must be:
[pluginfoldername].gif
where [pluginfoldername] is the exact same name you used for the folder.