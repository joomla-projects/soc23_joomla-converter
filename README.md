# Create the packaged zip

In the root's repository on your local computer, run:

`composer install` (the first time you create the zip)

`vendor\bin\robo build`

You will find the package in /dist.
Make sure you have the php zip extension enabled!

# Install the Migrate to Joomla extension

Install the package pkg_migratetojoomla.zip (soon, you will be able to install regular releases. Right now, go to Code -> Download zip. Unzip and use pkg_migratetojoomla.zip in your Joomla installer).

Go to System -> Manage -> Plugins -> filter by migratetojoomla and enable all plugins.

# Run the migration tool

This tool only works for WordPress at the moment, where Joomla and Wordpress are installed on the same server.

Go to Components -> Migrate to Joomla.
