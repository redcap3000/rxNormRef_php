<?php

class obcer{
	static function cache_token($settings=NULL){
	// generates file name for cache storage
		if($_POST){
		// also check count of array to see if it is all of the checkbox, in that case use _allRelatedInfo
			foreach($_POST as $value){
				if(is_array($value)){
					foreach($value as $v2){
						$token2 [] = trim($v2);
						}
					$token2 = implode('_',$token2);
					$token []= $token2;		
					}
				else		
					$token []= str_replace(' ','',strtolower(trim($value)));
				}
				
			return (HIDE_CACHE && $settings != 'db'?'.':NULL).($token2 || $_POST['nui']?implode('__',$token):implode('_',$token));
		}
	}

	function cascade_token(){
		// returns a token that is cascaded out - values that are equal to -'on','true' are ignored 
		// for storing cache files in a file system with directories as post key values
		// makes for more organized storage and hopefully faster file lookups (file_exists looking through a folder with 10,000 files vs. 1,000)
		
		// the order of the post var is kinda tricky ...
		foreach($_POST as $key=>$value)
			if(!is_array($value) && !is_object($value)){
				$end_path []= (FILE_CACHE_HIDE_DIR? '.' : NULL) .$key;
				$file_name []=$value;
			}elseif(is_array($value) || is_object($value))
				foreach($value as $loc=>$item)
					$file_name []= $item;
		$file_name = (is_array($file_name) ? implode('/',$end_path) .'/'. implode('_',$file_name) : implode('/',$end_path)) ;
		// remove 'on' or 'true' from the final file name.. should make this more rhobust or provide a class variable that tells it what to strip  
		return str_replace(array('_on_','_on','on_','_true','_true_','true_'),array(''),$file_name);
	}
	
	static function ob_cacher($stop=NULL){
	// implement HTML file caching here
	// move the other query checks here for couch etc. ?
		if(PROGRESSIVE_LOAD){		
			if(CACHE_QUERY && $stop){
				$put_file = CACHE_STORE . self::cache_token();
				$cache = ob_get_flush();
				if(!file_exists($put_file)){
				// compressing output doesn't seem to make the files drastically smaller
					if(COMPRESS_OUTPUT) $cache =  preg_replace("/\r?\n/m", "",$cache);
						file_put_contents("$put_file", $cache);
					}
				}
			elseif(CACHE_QUERY != true){
				ob_end_flush();
				if($stop!= NULL ) ob_start();	
			}elseif($_POST && CACHE_QUERY == TRUE){
				$cache_token = CACHE_STORE.self::cache_token();
				if(file_exists($cache_token)){
					$this->cache = 1;
					echo file_get_contents($cache_token);
					// tell the rest of the object to not render!
					return true;
					}
			}
		}
	}
}
