<?php

class obcer{
	static function cache_token(){
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
				// account for hidden file name if present (HIDE_CACHE), also adds 'allRelatedInfo' for specifying the default type if none is provided (via post).
			return (HIDE_CACHE?'.':NULL).($token2?implode('__',$token):implode('_',$token).'_allRelatedInfo');
		}
	}
	
	static function ob_cacher($stop=NULL){
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