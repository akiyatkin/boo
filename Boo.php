<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\once\Once;
use infrajs\nostore\Nostore;

class Boo {
	public static $conf = array(
		'cachedir' => '!boo/'
	);
	public static function is_dir($dir) {
		$dir = Path::toutf($dir);
		return is_dir($dir);
	}
	public static function is_file($dir) {
		$dir = Path::toutf($dir);
		return is_file($dir);
	}
	public static function filemtime($file) {
		$file = Path::resolve($file);
		return filemtime($file);
	}
	public static function scandir($dir, $call)
	{
		$dir = Path::theme($dir);
		if (!$dir) return array();
		$files = scandir($dir);
		$list = array();
		foreach ($files as $file) {
			if ($file[0]=='.') continue;
			$file = Path::toutf($file);
			$r = $call($file, $dir);
			if (!is_null($r) && !$r) continue;
			$list[] = $file;
		}
		return $list;
	}
	public static function file_put_json($file, $data) {
		$data = Load::json_encode($data);
		$file = Path::resolve($file);
		return file_put_contents($file, $data);
	}
	public static function file_get_json($file) {
		$file = Path::theme($file);
		if (!$file) return;
		$data = file_get_contents($file);
		$data = Load::json_decode($data);
		return $data;
	}
	public static function unlink($file, $data) {
		$file = Path::resolve($file);
		if (!$file) return true;
		return unlink($file);
	}
	public static function cache($name, $fn, $args = array(), $re = false)
	{
		return Once::exec('Boo::exec'.$name, function ($args, $r, $hash) use ($name, $fn, $re) {
			$dir = Boo::$conf['cachedir'].$name;
			Path::mkdir($dir);
			$file = $dir.'/'.$hash.'.json';
			$data = Boo::file_get_json($file);
			if ($data) return $data['result'];
			else $data = array();

			$is = Nostore::check( function () use (&$data, $fn, $args, $re) { //Проверка был ли запрет кэша
				$data['result'] = call_user_func_array($fn, array_merge($args, array($re)));
			});

			if (!$is && !$re) {
				$data['time'] = time();
				Boo::file_put_json($file, $data);
			} else {
				Boo::unlink($file);
			}
			return $data['result'];
		}, array($args), $re);
	}
	public static function clear($name, $args = false) {
		$dir = Boo::$conf['cachedir'].$name.'/';
		if ($args) {
			$hash = Once::clear($name, $args);
			$file = $dir.$hash.'.json';
			$r = Boo::unlink($file);
		} else {
			$r = Path::fullrmdir($dir, true);
		}
		return $r;
	}
}