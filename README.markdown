RxNormRef
=========
**Ronaldo Barbachano**

**Sep. 2011**


This version eventually will only support json responses and caching to couch db's only. Inclusion of
these features is incidential.

Whats Different
===============

**Couch Coolness**

Post-variable based couch document titles resulting in quick record lookups/writes without map reduce using
curl functions executed directly from php.

**NdfRt Response Rewrite**

This version rewrites NdfRT responses in a slightly more efficent manner (check out clean_ndf function) automatically.

**Drug Interactions**

Only checked for ndf records that are drugs (speed increase)

Cached in the couch db and automatically retrieved when corresponding record is accessed.



Requirements
============

PHP 5.1 or Greater (5.0 may work?)

CURL (This usually comes installed with php 5.1)

JSON / Couchdb support typically requires PHP 5.2 or greater.

Apache(Not required, but some caching directives are designed for apache)


Quickstart
==========

Edit config.php, create cache directories if desired to reference in config.php.

Each configuration option is explained in detail.

Upload files to web server.

Files in public/ are to be accessed to the public (images,css,basic html). 

Take care if these files are moved (update the paths to the lib folder in php files). 

