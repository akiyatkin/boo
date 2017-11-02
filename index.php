<?php
use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use infrajs\ans\Ans;
use infrajs\access\Access;

Access::test(true);

return Rest::get( function () {
	$list = Boo::scandir(Boo::$conf['cachedir'], function (&$file, $dir) {
		if (Boo::is_file($dir.$file)) return false;
		//$data = Boo::file_get_json($dir.$file);
		$file = array(
			'name' => $file,
			'time' => Boo::filemtime($dir.$file)
		);
	});
	$data = array();
	$data['list'] = $list;
	$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');
	return Ans::html($html);
}, 'clear', [ function () {
		$data = array();
		$html = Rest::parse('-boo/layout.tpl', $data, 'CLEAR');
		return Ans::html($html);
	}, function ($type, $name){
		$r = Boo::clear($name);
		$data = array('result' => $r);
		$html = Rest::parse('-boo/layout.tpl', $data, 'CLEARED');
		return Ans::html($html);
} ], 'test', function(){
	Boo::cache("test", function(){
		return 'Проверка';
	});
	$data = array();
	$html = Rest::parse('-boo/layout.tpl', $data, 'TEST');
	return Ans::html($html);
});
