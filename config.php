<?php

/* Bare Minimum Settings

	For RxNormRef to work it must know where it is located on your server(BASE_ROOT),
	And where it is accessible on the web (BASE_URL).

	To enable caching and modify other settings instructions are included below.

*/

// ex : /srv/www/yoursite.com/     ex: /var/www/
define('SERVER_ROOT','/var/www/');

define('BASE_URL','http://localhost/');

/* Runtime Options*/

// If turned off pages will load faster, but will also use more 
// memory and if the api connection hangs so will rendering of the page.
define('PROGRESSIVE_LOAD', true);

// removes newlines and tabs from generated record (html) output.
define('COMPRESS_OUTPUT',false);


/* Cache Settings
	- Want to cache NOW? Change ('CACHE_XML' ,false) to ('''', true), create 
	the cache_xml/ directory in your defined SERVER_ROOT location.

	-With Caching enabled folders with appropriate permissions are required.
	-Stores cache files based on POST variables. If POST variable is found, and matches existing file, 
	script loads that file and doesn't load RxNormRef (In case of HTML caching).
	-XML Caching stores entire datasets directly from the server; requires additional processing, but
	file sizes are about 1/4 of cached html files.
*/


define('CACHE_XML',false);
//define('XML_STORE','cache_xml/');

// stores HTML output - faster but larger files
define('CACHE_QUERY',false);
//define('CACHE_STORE','cache_query/');


// When true will use url access instead of 'file_get_contents'. Disabled for compatability.
define('XML_URL_ACCESS',false);

// this adds a period to hide cached files in file system  highly recommended if your cache store folder is publicly visible
define('HIDE_CACHE',true);


/* 
	Column Settings - Optional - You probably won't need to change this
*/

define('SUPPRESS_EMPTY_COL',true);

// shows the umlscui column, enabled by default
define('SHOW_UML',true);

// force 'synonymn' column to show (for debugging)
define('SHOW_ALL_SYNONYM',true);

// changing this will def. mess up the included template
define('SHOW_RXCUI',true);

define('SHOW_NAME',true);

// these still work, but will change the layout signfigantly depending on total number of columns
// these are all very redudant to display so disabled by default

define('SHOW_LANGUAGE',false);

define('SHOW_TTY',false);

define('SHOW_SUPPRESS',false);

// use if you're a data miner and have written another xml stucture... the
// rxNorm structure is verbose and very embeded, it could definately use a shift

define('SHOW_ALL',false);
