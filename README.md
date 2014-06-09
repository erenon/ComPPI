README
======

What is ComPPI?
---------------

Comppi is a Compartmentalized Protein-Protein Interaction database

Requirements
------------

The ComPPI software is currently built on the top of Symfony2.
Please refer to "[Symfony2][1]" requirements and supported databases.
To install Symfony2 dependecies git is recommended, but you can also download it manually.

Installation
------------

Get the ComPPI code:

    $ git clone http://bificomp2.sote.hu:22422/comppi

Locate the configuration parameters file and adjust the settings related to database connection to your environment.

    $ cp ./app/config/parameters.ini.dist ./app/config/parameters.ini
    $ $EDITOR ./app/config/parameters.ini

Install symfony dependencies:

    $ ./bin/vendors install

Build the database
------------------

Grab the sources:

    $ ./bin/download_sources.sh

This will put the 3rd party source databases under `./databases`. You may want to add other custom sources indicated by the download script. To load them into the configured database, issue the following command:

    $ ./bin/build_database.sh

Visit demo page
---------------

Assuming you put the project under your HTTP servers document root into the directory named `comppi`, you can access to the demo page via the following url:

    http://localhost/comppi/web/app_dev.php/stat

If everything went fine (including you have already loaded the plaintext files into your database), you should see some statistics about the database.

[1]: http://symfony.com/
