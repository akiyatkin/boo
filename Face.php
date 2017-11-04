<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\access\Access;

class Face {
	public static function list() {
		$list = Boo::scandir(Boo::$conf['cachedir'], function (&$file, $dir) {
			if (Boo::is_file($dir.$file)) return false;
			$data = Boo::file_get_json($dir.$file);
			$file = array(
				'name' => $file,
				'time' => Boo::filemtime($dir.$file)
			);
		});
		$data = array();
		$data['list'] = $list;
		$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');
		return Ans::html($html);
	}
}