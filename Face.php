<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\access\Access;

class Face {
	public static function list($msg = '') {
		$size = 0;
		$time = 0;
		$count = 0;
		$list = Boo::scandir(Boo::$conf['cachedir'], function (&$row, $dir) use (&$size, &$time, &$count) {
			$name = $row;
			if (Boo::is_file($dir.$name)) return false;
			$data = Boo::file_get_json($dir.$name);
			$row = array(
				'name' => $name,
				'count' => 0,
				'sources' => array(),
				'size' => 0,
				'time' => Boo::filemtime($dir.$name)
			);
			Boo::scandir(Boo::$conf['cachedir'].$name.'/', function ($cache) use ($name, &$row, $dir) {
				$src = $dir.$name.'/'.$cache;
				$row['count']++;
				$row['size']+=filesize($src)/1000;
				$data = Boo::file_get_json($src);
				if ($data['time'] > $row['time']) $row['time'] = $data['time'];
				
				unset($data['result']);
				$row['sources'][] = $data;
			});
			if ($size < $row['size']) $size = $row['size'];
			if ($time < $row['time']) $time = $row['time'];
			$count += $row['count'];
		});
		$data = array();
		$data['result'] = 1;
		$data['msg'] = $msg;
		$data['list'] = $list;
		$data['time'] = $time;
		$data['size'] = $size;
		$data['count'] = $count;
		$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');

		Ans::html($html);
	}
}