<?php
if(!defined("qTranslate_DEFAULT_LANGUAGE")){
	$q_config = qTranslate::get_settings();
	define("qTranslate_DEFAULT_LANGUAGE", $q_config["default_language"]);
}

class qTranslate{
	var $language = NULL;
	var $settings = array();
	
	function qTranslate(){}
	function parse($str){
		$blocks = qTranslate::blocks($str, NULL);
		$l = qTranslate::get_language();
		$result = NULL;
		foreach($blocks as $i=>$obj){
			if(!isset($obj['language']) || $obj['language'] == $l){ $result .= $obj['block']; }
		}
		return $result;
	}
	function set_language($l){
		if(isset($this) && isset($this->language)){ $this->language = $l; }
		@session_start();
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
		/*fix*/ if(defined("qTranslate_DEFAULT_LANGUAGE")){ $q_config["default_language"] = qTranslate_DEFAULT_LANGUAGE; }
		/*fix*/ if(qTranslate::get_language()){ $q_config["language"] = qTranslate::get_language(FALSE); }
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
		if($simpletype == NULL){ $str = preg_replace("#[<][!][-]{2}[:]([a-z]{2}|[a-z]{2}_[a-z]{2})?[-]{2}[>]#i", "[:\\1]", $str); $simpletype = TRUE; }
		if($simpletype === TRUE){
			$set = explode('[:', $str);
			foreach($set as $i=>$blob){
				$json[$i] = array('block' => $blob);
				if($i>0 && preg_match("#^([a-z]{2}|[a-z]{2}_[a-z]{2})?[\]](.*)$#i", $blob, $buffer)){
					$json[$i]['block'] = $buffer[2];
					if(strlen($buffer[1])>0){ $json[$i]['language'] = $buffer[1]; }
				}
			}
		}
		else{
			$set = explode('<!--:', $str);
			foreach($set as $i=>$blob){
				$json[$i] = array('block' => $blob);
				if($i>0 && preg_match("#^([a-z]{2}|[a-z]{2}_[a-z]{2})?[-]{2}[>](.*)$#i", $blob, $buffer)){
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
}
?>