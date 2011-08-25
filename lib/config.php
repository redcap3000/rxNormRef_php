<?php
// the location of all public files, used mainly for URL generation etc..
// ex : /srv/www/yoursite.com
// ex: /var/www
define('SERVER_ROOT','/var/www/');
define('BASE_URL','http://localhost/');

// not recommend that this be turned off, uses ob_cache to render elements to screen as they are encountered.
// If turned off pages will load faster, but will also use more memory and if the api connection hangs so will rendering of the page.
define('PROGRESSIVE_LOAD', true);
// removes newlines and tabs from generated record output, recommended!
define('COMPRESS_OUTPUT',false);

/* when true, and xml caching disabled, stores search queries as HTML
This method results in larger cached files, but reduced memory use
since the API libraries and API connection does not need to be enabled to work (too look
existing queries).
*/
define('CACHE_QUERY',false);

// caching requires progressive load to be true
// make sure this folder has proper permissions
//define('CACHE_STORE',SERVER_ROOT.'cache_query/');

// this adds a period to hide cached files in file system 
// highly recommended if your cache store folder is publicly visible
define('HIDE_CACHE',true);
// this stores all raw data from queries in XML 
// if xml is enabled then cache_query is automatically disabled.

define('CACHE_XML',false);

// some servers disallow this change in your php ini
// XML caching results in smaller files and stores all fields, but requires
// the api libraries to load to look up the RXCUID
define('XML_URL_ACCESS',true);

// we don't add the base_url for proper url redirection
//define('XML_STORE','cache_xml/');

/* This will remove empty columns, this may break some layout displays dependent on tables/columns (as
 one record may have the column, another wont, so the elements will flow to fill the missing space, 
 misaligning the row above -- if you are making something that isn't layed out like this, then turn this on
 to reduce the size of transfered data.
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
