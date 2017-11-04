<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\access\Access;

class Face {
	public static function list() {
		$list = Boo::scandir(Boo::$conf['cachedir'], function (&$row, $dir) {
			$name = $row;
			if (Boo::is_file($dir.$name)) return false;
			$data = Boo::file_get_json($dir.$name);
			$row = array(
				'name' => $name,
				'count' => 0,
				'time' => Boo::filemtime($dir.$name)
			);
			Boo::scandir(Boo::$conf['cachedir'].$name.'/', function ($cache) use ($name, &$row, $dir) {
				$row['count']++;
				$data = Boo::file_get_json($dir.$name.'/'.$cache);
				if ($data['time'] > $row['time']) $row['time'] = $data['time'];
			});
		});
		$data = array();
		$data['list'] = $list;
		$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');
		return Ans::html($html);
	}
}