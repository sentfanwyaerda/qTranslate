<?php
if(!defined("qTranslate_DEFAULT_LANGUAGE")){
	$q_config = qTranslate::get_settings();
	define("qTranslate_DEFAULT_LANGUAGE", $q_config["default_language"]);
}

define('qTranslate_LANGUAGE_PATTERN', "([a-z]{2,3}|[a-z]{2,3}[_-][A-Z]{2}|Cy-[a-z]{2}[_-][A-Z]{2}|Lt-[a-z]{2}[_-][A-Z]{2})");

class qTranslate{
	var $language = NULL;
	var $settings = array();
	
	function qTranslate(){}
	function parse($str, $l=NULL){
		$blocks = qTranslate::blocks($str, NULL);
		//*debug*/ print '<!-- '.print_r($blocks, TRUE).' -->';
		if($l === NULL){ $l = qTranslate::get_language(TRUE); }
		$result = NULL;
		foreach($blocks as $i=>$obj){
			if(!isset($obj['language']) || $obj['language'] == $l){ $result .= $obj['block']; }
		}
		return $result;
	}
	function set_language($l){
		if(isset($this) && isset($this->language)){ $this->language = $l; }
		session_start();
		$_SESSION['language'] = $l;
	}
	function enable_language($l){}
	function disable_language($l){}
	function get_language($load=TRUE){
		if(isset($_GET['l'])){ qTranslate::set_language($_GET['l']); return $_GET['l']; }
		if(isset($_GET['lang'])){ qTranslate::set_language($_GET['lang']); return $_GET['lang']; }
		if(isset($_GET['language'])){ qTranslate::set_language($_GET['language']); return $_GET['language']; }
		
		if(isset($this) && isset($this->language)){ return $this->language; }
		if(isset($_SESSION['language'])){ return $_SESSION['language']; }
		
		if($load == TRUE){
			$q_config = qTranslate::get_settings();
			if(isset($q_config['default_language'])){ return $q_config['default_language']; }
		}
		return NULL;
	}
	function get_settings($root="./", $refresh=FALSE){
		/*gather*/ if($refresh === FALSE && (isset($this) && isset($this->settings) && is_array($this->settings) && $this->settings != array() ) ){ return $this->settings; }
		/*fix*/ if($root == "./"){ $root = dirname(__FILE__).'/'; }
		/*collect*/ $q_config = json_decode(file_get_contents($root.(substr($root, -1) != '/' ? '/' : NULL).'settings.json'), TRUE);
		/*fix*/ if(defined("qTranslate_DEFAULT_LANGUAGE")){ $q_config["language"] = $q_config["default_language"] = qTranslate_DEFAULT_LANGUAGE; }
		/*fix*/ if(qTranslate::get_language(FALSE)){ $q_config["language"] = qTranslate::get_language(FALSE); }
		/*updating*/ if($refresh != FALSE || (isset($this) && isset($this->settings) && is_array($this->settings) && $this->settings == array() ) ){ $this->settings = $q_config; }
		return $q_config;
	}
	function str_available_languages($available_languages=array(), $lang=NULL){
		$q_config = qTranslate::get_settings();
		$lang = ($lang === NULL ? qTranslate::get_language() : $lang);
		if(!is_array($available_languages) || $available_languages === array()){
			$available_languages = $q_config['enabled_languages'];
		}
		// display selection for available languages
		$available_languages = array_unique($available_languages);
		rsort($available_languages);
		$language_list = "";
		if(preg_match('/%LANG:([^:]*):([^%]*)%/',$q_config['not_available'][$lang],$match)) {
			$normal_separator = $match[1];
			$end_separator = $match[2];
			// build available languages string backward
			$i = 0;
			foreach($available_languages as $language) {
				if(isset($q_config['language_name'][$language])){
					if($i==1) $language_list  = $end_separator.$language_list;
					if($i>1) $language_list  = $normal_separator.$language_list;
					$language_list = '<a href="'.qTranslate::convertURL('', $language, false, true).'">'.$q_config['language_name'][$language].'</a>'.$language_list;
					$i++;
				}
			}
		}
		return '<p class="alert alert-warning"><i class="icon fa fa-warning"></i>'.preg_replace('/%LANG:([^:]*):([^%]*)%/', $language_list, $q_config['not_available'][$lang])."</p>";
	}
	function convertURL($url='', $lang='', $forceadmin = false, $showDefaultLanguage = false){
		return '#'.$lang;
		$q_config = qTranslate::get_settings();
		if($lang=='') $lang = $q_config['language'];
	}
	function blocks($str, $simpletype=NULL){
		$json = array();
		if($simpletype === NULL){ $str = preg_replace("#[<][!][-]{2}[:]".qTranslate_LANGUAGE_PATTERN."?([\*!])?[-]{2}[>]#i", "[:\\1\\2]", $str); $simpletype = TRUE; }
		if($simpletype === TRUE){
			$set = explode('[:', $str);
			foreach($set as $i=>$blob){
				$json[$i] = array('block' => $blob);
				if($i>0 && preg_match("#^".qTranslate_LANGUAGE_PATTERN."?[\]]#i", $blob, $buffer)){ //
					if(isset($buffer[1])){
						$json[$i]['block'] = substr($blob, strlen($buffer[1])+1);
						if(strlen($buffer[1])>0){ $json[$i]['language'] = $buffer[1]; }
					} else {
						/* [:]-end signal */
						$json[$i]['block'] = substr($blob, 1);
						$json[$i]['language'] = NULL;
					}
				}
			}
		}
		else{
			$set = explode('<!--:', $str);
			foreach($set as $i=>$blob){
				$json[$i] = array('block' => $blob);
				if($i>0 && preg_match("#^(".qTranslate_LANGUAGE_PATTERN.")?[-]{2}[>](.*)$#i", $blob, $buffer)){
					$json[$i]['block'] = $buffer[2];
					if(strlen($buffer[1])>0){ $json[$i]['language'] = $buffer[1]; }
				}
			}
		}
		return $json;
	}
	function tree($str, $simpletype=NULL){
		$json = array(); $gid = 0;
		$blocks = qTranslate::blocks($str, $simpletype);
		foreach($blocks as $i=>$block){
			if(!isset($block['language'])){
				/*fix*/ if(isset($json[$gid])){ $gid++; }
				$json[$gid] = $block['block'];
				$gid++;
			}
			else{
				$json[$gid][$block['language']] = $block['block'];
			}
		}
		return $json;
	}
	function multiple_parse($str, $language=NULL, $get_all=FALSE){
		if(/*($language === NULL || $language == 'any' || $language == '*') &&*/ $get_all === FALSE){ return qTranslate::parse($str, $language); }
		else{
			$tree = qTranslate::tree($str);
			$lset = $all = array();
			foreach($tree as $i=>$block){
				if(is_array($block)){ $lset = array_merge($lset, array_keys($block)); }
			}
			$lset = array_unique($lset);
			foreach($lset as $i=>$l){
				$all[$l] = qTranslate::parse($str, $l);
			}
			if($language == 'any'){ $all['any'] = qTranslate::parse($str); }
			//print "<!-- qTranslate[ ".$str." ] : ".print_r($lset, TRUE).' & '.print_r($all, TRUE)." -->\n";
			return $all;
		}
	}
	function in_array($needle, $haystack){
		return (qTranslate::array_search($needle, $haystack) === FALSE ? FALSE : TRUE);
	}
	function array_search($needle, $haystack){
		foreach($haystack as $key=>$value){
			$set = qTranslate::multiple_parse($value, 'any', TRUE);
			//*debug*/ print '<!-- '.$key.' := '.$value.' ('.print_r($set, TRUE).') -->'."\n";
			if(!is_array($value) && !is_object($value) && in_array($needle, $set) ){ return $key; }
		}
		//return in_array($needle, $haystack);
		return FALSE;
	}
	function array_search_language($needle, $haystack){
		foreach($haystack as $key=>$value){
			$set = qTranslate::multiple_parse($value, 'any', TRUE);
			//*debug*/ print '<!-- '.$key.' := '.$value.' ('.print_r($set, TRUE).') -->'."\n";
			if(!is_array($value) && !is_object($value) ){
				foreach($set as $lang=>$str){
					if($needle == $str){ return $lang; }
				}
			}
		}
		//return in_array($needle, $haystack);
		return FALSE;
	}
	function translate($needle, $haystack, $language=NULL){
		if(isset($haystack[$needle])){ $key = $needle; }
		else{ $key = qTranslate::array_search($needle, $haystack); }
		$res = qTranslate::parse($haystack[$key], $language);
		return ($res == NULL ? $needle : $res);
	}
	function url_translate($str, $haystack, $language=NULL){
		preg_match_all('#href="/([^"?]+)([?][^"]+)?"#i', $str, $buffer);
		//*debug*/ print '<!-- '.print_r($buffer, TRUE).' -->';
		foreach($buffer[1] as $i=>$needle){
			if(preg_match('#([?&])(l|lang|language)=([^&]+)#i', $buffer[2][$i], $bb)){
				$ltemp = $bb[3]; $lmatch = $bb[0]; $lprefix = $bb[1];
				//*debug*/ print '<!-- '.print_r($bb, TRUE).' -->';				
			}
			else{ $ltemp = NULL; $lmatch = NULL; $lprefix = NULL; }
			$np = qTranslate::translate($needle, $haystack, ($ltemp == NULL ? $language : $ltemp));
			$lpostfix = ($needle =! $np || $ltemp == qTranslate::get_language() ? str_replace($lmatch, ($lprefix == '?' && strlen($buffer[2][$i]) > strlen($lmatch) ? '?' : NULL), $buffer[2][$i]) : $buffer[2][$i]);
			$str = str_replace($buffer[0][$i], 'href="'.$np.$lpostfix.'"', $str);
		}
		return $str;
	}
}
?>