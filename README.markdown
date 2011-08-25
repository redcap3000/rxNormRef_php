RxNormRef
=========
**Ronaldo Barbachano**

**Aug. 2011**

This repo was forked from RxNormRef on CFA, that I created earlier this summer. This version runs on rxnix.com.

Goals
=====

Provide a fast, rhobust interface to RxNorm medical terminology database while developing a barebones framework for API-centric web development.



Technical Features
==================

**Servers can cache all queries in XML,HTML, or both**

**Progressive loading via ob_cacher**

**Low Memory Use - Usually under a meg**

**Fast XML Processing with SimpleXMLElement**

**No Database Required**

**No 3rd Party Membership or API key needed**


Requirements
============

PHP 5.1 or Greater (5.0 may work?)
CURL (This usually comes installed with php 5.1)

Apache(Not required, but some caching directives are designed for apache)


Quickstart
==========

Edit config.php, create cache directories if desired to reference in config.php.

Each configuration option is explained in detail.

Upload files to web server.

Files in public/ are to be accessed to the public (images,css,basic html). 

Take care if these files are moved (update the paths to the lib folder in php files). 
