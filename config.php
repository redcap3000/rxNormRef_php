<?php
/* Runtime Options*/

// If turned off pages will load faster, but will also use more 
// memory and if the api connection hangs so will rendering of the page.
define('PROGRESSIVE_LOAD', true);

/* Couch DB Support Got one ? Get it! Uses exec with curl for virtually no php overhead
  Will be adding more features shortly...

  */

define('COUCH',true);

define('COUCH_HOST','http://localhost:5984/');
define('COUCH_DB','test');


// for couch STAT's... COUCH_STAT is the name of the stats database on the same host as COUCH_HOST

define('COUCH_STAT','stats');

// this reports a few more things

define('COUCH_STAT_VERBOSE',false);

// user agent strings can get verbose... disable to cutback on space use.

define('COUCH_STAT_UA',false);

// use this if you have another database that is linked (replicated) to the couch host to show existing records from
// i.e. record gets stored to one database, is automatically rep. to another, then is served back from the other with super simple api call
// ex : https://<username>:<password>@couch.host/databasename
//define('COUCH_VIEW_HOST','https://me:mypass7@awesomeness.mycloudhost.com/database');
define('COUCH_VIEW',false);

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
