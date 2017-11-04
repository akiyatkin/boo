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
	public static function unlink($file) {
		$file = Path::resolve($file);
		if (!$file) return true;
		return unlink($file);
	}
	public static $re = false;
	public static function cache($name, $fn, $args = array())
	{
		return Once::exec('Boo::cache'.$name, function ($args, $r, $hash) use ($name, $fn) {
			$dir = Boo::$conf['cachedir'].$name;
			Path::mkdir($dir);
			$file = $dir.'/'.$hash.'.json';
			$data = Boo::file_get_json($file);
			if ($data && !Boo::$re) return $data['result'];
			
			if (!$data) {
				$data = array();
				$src = preg_replace("/^\/+/", "", $_SERVER['REQUEST_URI']);
				//$src = $src.(($_SERVER['QUERY_STRING'])?'&':'?').'re';
				$data['src'] = $src;
			}
			
			$data['time'] = time();

			$is = Nostore::check( function () use (&$data, $fn, $args) { //Проверка был ли запрет кэша
				$orig = Boo::$re;
				Boo::$re = false;
				$data['result'] = call_user_func_array($fn, array_merge($args, array(Boo::$re)));
				Boo::$re = $orig;

			});

			if ($is) {
				if (Boo::$re) Boo::unlink($file);
			} else {
				Boo::file_put_json($file, $data); //С re мы всё равно сохраняем кэш
			}
			return $data['result'];
		}, array($args));
	}
	public static function refresh($name, $args = false) {
		$dir = Boo::$conf['cachedir'];
		if (!$name) {
			Boo::scandir($dir, function ($file) use ($dir) {
				if (Boo::is_file($dir.$file)) return;
				Boo::refresh($file);
			});
			return;
		} else {
			$dir = $dir.$name.'/';
		}
		if ($args) {
			$hash = Once::clear($name, $args);
			$file = $dir.$hash.'.json';
			$data = Boo::file_get_json($file);
			Load::json($data['src']);
			//$r = Boo::unlink($file);
			return true;
		} else {
			//$r = Path::fullrmdir($dir, true);
			Boo::scandir($dir, function ($file) use ($dir) {
				$file = $dir.$file;
				$data = Boo::file_get_json($file);
				Boo::$re = true;
				Load::loadTEXT($data['src']);
				Boo::$re = false;
			});
			return true;
		}
	}
	public static function remove($name = false, $args = false) {
		$dir = Boo::$conf['cachedir'];
		if (!$name) {
			$r = Path::fullrmdir($dir);
			return $r;
		} else {
			$dir = $dir.$name.'/';
		}
		if ($args) {
			$hash = Once::clear($name, $args);
			$file = $dir.$hash.'.json';
			$r = Boo::unlink($file);
		} else {
			$r = Path::fullrmdir($dir, true);
		}
		return true;
	}
}