<?php 
require_once(dirname(__FILE__).'/qTranslate.php');


header('Content-Type: text/html; charset=utf-8');
print qTranslate::str_available_languages( (isset($_GET['available']) ? explode(',', $_GET['available']) : array('en','nl') ),(isset($_GET['l']) ? $_GET['l'] : 'en'));
//print '<pre>'; print_r(qTranslate::get_settings()); print '</pre>';

$qTstr = 'Neutral<!--:fy-->Frysk<!--:--> &rArr; [:nl]Nederlands[:en]English[:];';
print '<pre>';
	print json_encode(qTranslate::tree($qTstr));
	print ' &rArr; <span style="color: #993349; background-color: #EEE;">'.qTranslate::parse($qTstr)."</span>\n\n";
print '</pre>';
?>