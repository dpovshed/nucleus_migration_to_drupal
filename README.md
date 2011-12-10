Nucleus to Drupal migration script
==================================

Purpose of this toolkit is to assist you in migrate Nucleus blog to Drupal CMS.
However there are other various approaches to do the job, idea is to use simple
commandline script.

Written and tested for versions: Nucleus 3.41 (http://nucleuscms.org) and Drupal 6.14 (http://drupal.org).

Environment
-----------

Apache/2.2.9 + PHP/5.2.6 + MySQL/5.0.67

The files
----------

- denuke.cfg.php      - configuration file
- denuke.php          - main conversion script
- denuke_dinfo.php    - gather key info about destination Drupal DB, readonly acess.
- denuke_ninfo.php    - gather key info about source Nucleus DB, readonly acess.


Usage
-----

- prepare a Drupal installation in standard way with http://drupal.org;
- create a Taxonomy vocabularies and content type to imported data;
- place deNuke files into the root of Drupal folder;
- configure denuke.cfg.php;
- make Drupal DB backup (optional);
- copy source site media files to Drupal destination directory
  manually (recommended) if you didn't enabled this option in config file;
- start denuke.php in commandline by running "php denuke.php"


Contacts
--------

This script was originally made by Dennis Povshedny (http://drupal.org/user/117896)
by request of http://weblab.tk .

Known bugs and limitations
--------------------------

1) Please make sure that source and destination databases are in UTF-8.
2) Current implementation parse media tags <%popup and <%image .
3) Modification of Drupal DB is made mostly by Drupal API.
   Small disadvantage of this aproach is that import cannot be fully
   enclosed in transaction. So please backup Drupal SQL data just in case
   before processing, if DB already contains useful info (as a result of
   previous imports, for example)


Changelog
---------
13-Jan-2010 v1.11 Fixes:
  - stripping all html tags in post titles;

26-Dec-2009 v1.1 Fixes:
  - full UTF-8 support;
  - comment processing;
  - correct processing of <%image tag without incomplete '('.

27-Nov-2009 v1.0 Initial release

Ideas and todos
---------------
* Nucleus do not track user registration time, Drupal do so. In many
social-oriented websites it is important factor how long person participated
on site. We can set user registration time to user's first post/comment time.
