<?php



/* Bare Minimum Settings

	For RxNormRef to work it must know where it is located on your server(BASE_ROOT),
	And where it is accessible on the web (BASE_URL).

	To enable caching and modify other settings instructions are included below.

*/

// ex : /srv/www/yoursite.com/     ex: /var/www/
define('SERVER_ROOT','/var/www/rxNormRef_php/public');

define('BASE_URL','http://localhost/rxNormRef_php/public/');

/* Runtime Options*/

// If turned off pages will load faster, but will also use more 
// memory and if the api connection hangs so will rendering of the page.
define('PROGRESSIVE_LOAD', true);

// removes newlines and tabs from generated record (html) output.
define('COMPRESS_OUTPUT',false);

/* Couch DB Support Got one ? Get it! Uses exec with curl for virtually no php overhead
  Will be adding more features shortly...

  */
  
  
 // echo level rendering 
//define('RENDER_MODE','xml');
 define('RENDER_MODE','json');


// ideally you'll want to render as what you're file caching (if not html)

// format of what gets stored to the filesystem (if render mode &file cache mode differ each must be converted)

// hide directories too?
// if html mode is defined then rendermode is ignored when rendering from filesystem (die the existing html file)

// define('FILE_CACHE_MODE','html');

// support define('FILE_CACHE_MODE','all')
// define('FILE_CACHE_MODE','json')
// define('FILE_CACHE_MODE','json+xml')

// you -can- keep a file and couch cache - may speed up database lookups to look inside a directory vs hitting the db

// if couch cache true, and couch file cache supress true - empty files are stored instead of data.
define('COUCH_FILE_CACHE_SUPRESS',true);

define('COUCH_CACHE',true);

define('COUCH',true);
define('COUCH_HOST','http://localhost:5984');
define('COUCH_DB','test');


/* Cache Settings
	
	Concentrating on making one caching method the most rhobust so other caching methods will be
	added back as they are rewritten.
	
	Note - Responses from rxNorm are stored as is, along with its 'cache token' - which is generated
	based on what the $_POST variable is (never the $_GET, although it is used in the generation of records)
	
	
	For the NDT-rt , I removed a lot of nested children that were only-children, making reference easier, I also added some of the basic repsonse elements as fields.
	
	For these records, they will render in the new format, and if the record does not exist in the database, it will get the newly stored record in the same call.
	Caching to the file system is not really recommended since as traffic increases, and people begin navigating every available subject (or crawlers...) you could
	end up running out of HD space quickly.
	
	Couch DB's are quick and easy, and free.
	
	
	*/



// When true will use url access instead of 'file_get_contents'. Disabled for compatability.

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
