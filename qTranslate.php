<?php class qTranslate{
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
	function get_language(){
		if(isset($_GET['l'])){ return $_GET['l']; }
		$q_config = qTranslate::get_settings();
		if(isset($q_config['default_language'])){ return $q_config['default_language']; }
		return 'unknown';
	}
	function get_settings($root="./", $refresh=FALSE){
		/*gather*/ if($refresh === FALSE && (isset($this) && isset($this->settings) && is_array($this->settings) && $this->settings != array() ) ){ return $this->settings; }
		/*fix*/ if($root == "./"){ $root = dirname(__FILE__).'/'; }
		/*collect*/ $json = json_decode(file_get_contents($root.'settings.json'), TRUE);
		/*updating*/ if($refresh != FALSE || (isset($this) && isset($this->settings) && is_array($this->settings) && $this->settings == array() ) ){ $this->settings = $json; }
		return $json;
	}
	function str_available_languages($available_languages=array(), $lang=NULL){
		$q_config = qTranslate::get_settings();
		$lang = ($lang === NULL ? qTranslate::get_language() : $lang);
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

header('Content-Type: text/html; charset=utf-8');
print qTranslate::str_available_languages( (isset($_GET['available']) ? explode(',', $_GET['available']) : array('en','nl') ),(isset($_GET['l']) ? $_GET['l'] : 'en'));
//print '<pre>'; print_r(qTranslate::get_settings()); print '</pre>';

$qTstr = 'Neutral<!--:fy-->Frysk<!--:--> &rArr; [:nl]Nederlands[:en]English[:];';
print '<pre>';
	print json_encode(qTranslate::tree($qTstr));
	print ' &rArr; <span style="color: #993349; background-color: #EEE;">'.qTranslate::parse($qTstr)."</span>\n\n";
print '</pre>';
?>