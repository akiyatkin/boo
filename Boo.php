<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\once\Once;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\sequence\Sequence;

register_shutdown_function(function(){
	chdir(Boo::$cwd);
	if (Boo::$proccess || isset($_GET['-boo'])) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сравниваем
		$items = Boo::initSave();
		if (isset($_GET['-boo'])) {
			var_dump(Boo::$proccess);
			echo 'На этой странице <pre>';
			print_r(Boo::$items);
			echo 'Всё <pre>';
			print_r($items);
		}
		
	}
});
class Boo {
	public static $cwd = false;// Рабочая директория может подменяться в подписке конца работы php и нужно её сохранить.
	public static $conf = array(
		'cachedir' => '!boo/'
	);
	/*public static function removeRec(&$items, $id, $parents = array()) {
		//Удаляем детей которые уже были в списке.
		$parents[] = $id;
		foreach ($items[$id]['childs'] as $k => $cid) {
			if(!in_array($cid, $parents)) Boo::removeRec($items, $cid, $parents);
			else unset($items[$id]['childs'][$k]);
		}
	}*/
	public static function initSave() {
		$src = Boo::$conf['cachedir'].'tree.json';
		$items = Boo::file_get_json($src);
		if (!$items) {
			$items = Boo::$items;
		} else {
			foreach (Boo::$items as $id => $v) {
				if (!isset($items[$id])) {
					$items[$id] = Boo::$items[$id];
				} else {
					if(isset(Boo::$items[$id]['src'])) {
						$items[$id]['timer'] = Boo::$items[$id]['timer'];
						$items[$id]['time'] = Boo::$items[$id]['time'];
						$items[$id]['src'] = Boo::$items[$id]['src'];
					}
					$items[$id]['parents'] = array_values(array_unique(array_merge($items[$id]['parents'], Boo::$items[$id]['parents'])));
					if (isset($items[$id]['src'])) continue;
					$items[$id]['childs'] = array_values(array_unique(array_merge($items[$id]['childs'], Boo::$items[$id]['childs'])));
				}
			}
		}
		Boo::file_put_json($src, $items);
		return $items;
	}
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
		$src = Path::resolve($file);
		if (!is_file($src)) return array();
		$data = Load::loadJSON($file);
		//$data = file_get_contents($file);
		//$data = Load::json_decode($data);
		return $data;
	}
	public static function unlink($file) {
		$file = Path::resolve($file);
		if (!$file) return true;
		if (!is_file($file)) return true;
		return unlink($file);
	}
	public static $boo = false;
	public static function isre($name) {
		if (!is_array(Boo::$boo)) {
			if (isset($_GET['-boo'])) Boo::$boo = explode(',',$_GET['-boo']);
			else return false;	
		}
		return in_array($name, Boo::$boo);
	}
	public static $re = false; //Глобальный refresh

	public static $item = false;
	public static $proccess = false;
	public static $items = array();
	public static $parents = array();
	public static function setTitle($title) {
		Boo::$item['title'] = $title;
	}
	public static function &createItem($id, $title) {
		$item = array();
		$item['id'] = $id;
		$item['parents'] = array();
		$item['counter'] = 0;
		$item['title'] = $title;
		$item['childs'] = array();
		return $item;
	}
	/*public static function inParents($item, $parent, $items, $checked = array()) {
		$checked[$parent['id']] = true;
		if ($item['id'] == $parent['id']) return true;
		foreach ($parent['parents'] as $id) {
			if (isset($checked[$id])) return true;
			if (Boo::inParents($item, $items[$id], $items, $checked)) return true;
		}
	}*/
	public static function &start($id, $title = false) {
		$name = Boo::split($id, $title);
		list($id, $title) = $name;

		
		if (!isset(Boo::$items[$id])) {
			Boo::$items[$id] = &Boo::createItem($id, $title);
		}
		Boo::$items[$id]['counter']++;


		$path = Sequence::short(Boo::$parents);
		if (!in_array($path, Boo::$items[$id]['parents'])) {
			Boo::$items[$id]['parents'][] = $path;
		}
		if (Boo::$item && !in_array($id, Boo::$item['childs'])) {
			Boo::$item['childs'][] = $id;
		}

		/*if (Boo::$item['id'] != $id) {
			foreach (Boo::$parents as $pid) {
				if (isset(Boo::$items[$pid]['timer'])) {
					if (!in_array($pid, Boo::$items[$id]['parents'])) {
						Boo::$items[$id]['parents'][] = $pid;
					}
				}
			}
			if (!in_array($id, Boo::$item['childs'])) {
				Boo::$item['childs'][] = $id;
			}
		}*/

		Boo::$parents[] = $id;
		Boo::$item = &Boo::$items[$id];
		
		return Boo::$items[$id];
	}
	public static function end() {
		if (sizeof(Boo::$parents) < 1) {
			throw 'Неопределённый Boo::end';
		}
		array_pop(Boo::$parents);
		if (sizeof(Boo::$parents) > 0) {
			Boo::$item = &Boo::$items[Boo::$parents[sizeof(Boo::$parents)-1]];
		}
	}
	public static function split($name, $title = false) {
		if (is_array($name)) {
			$id = $name[0];
			$title = $name[1];
		} else {
			$id = $name;
			if (!$title) $title = $name;
		}
		$id = Path::encode($id);
		return array($id, $title);
	}
	public static function getSrc() {
		$src = preg_replace("/^\/+/", "", $_SERVER['REQUEST_URI']);
		return $src;
	}
	public static function &cache($name, $fn, $args = array())
	{
		list($id, $title) = Boo::split($name);
		
		if (sizeof($args) == 0) {
			$item = &Boo::start($id, $title, true);
		} else {
			$item = &Boo::start($id, $title);
			$hash = Hash::make($args);
			$idddd = $id.'-'.$hash;
			if (is_string($args[0]) || is_number($args[0])) {
				$title = $args[0];	
			} else  {
				$title = $hash;
			}
			$item = &Boo::start($idddd, $title, true);	
		}

		$data = &Once::exec('boo-cache-'.$id, function &($args, $r, $hash) use ($fn, $id, &$item, &$proccess) {
			
			$dir = Boo::$conf['cachedir'].$id;
			Path::mkdir($dir);
			$file = $dir.'/'.$hash.'.json';
			$data = Boo::file_get_json($file);

			$re = Boo::$re ? true : Boo::isre($id);
			if ($data && !$re) return $data;
			
			
			$data = array();
			$data['args'] = $args;
			$src = Boo::getSrc();
			
			$item['src'] = $src;
			$item['file'] = $file;
			$item['time'] = time();
			$item['timer'] = microtime(true);

			//$data['boo'] .= '-boo='.implode(',', Boo::$parents);
			Boo::$proccess = true;

			$is = Nostore::check( function () use (&$data, $fn, $args, $re) { //Проверка был ли запрет кэша
				//$orig = Boo::$re; //Все кэши внутри сбрасываются. Родительский кэш нужно указать явно в -boo и всё обновится
				//Boo::$re = true;
				$data['result'] = call_user_func_array($fn, array_merge($args, array($re)));
				//Boo::$re = $orig;
			});
			$item['timer'] = round(microtime(true) - $item['time'], 4);
			$data['item'] = $item;
			if ($is) {
				if ($re) Boo::unlink($file);
			} else {
				Boo::file_put_json($file, $data); //С re мы всё равно сохраняем кэш
			}

			return $data;
		}, array($args));

		$path = Sequence::short(Boo::$parents);

		if (!in_array($path, $data['item']['parents'])) {
			
			//$data['item']['src'][] = Boo::getSrc();
			//Boo::$item['src'] = array_values(array_unique(array_merge($data['item']['src'])));
			//$data['item']['src'] = Boo::$item['src'];

			$data['item']['parents'] = array_values(array_unique(array_merge($data['item']['parents'], Boo::$item['parents'])));
			Boo::$item['parents'] = $data['item']['parents']; //На случай если будет перезапись

			
			$hash = Hash::make($args);//Нужно пересохранить результат, что бы мы всегда знали пути обращения к нему
			$dir = Boo::$conf['cachedir'].$id;
			$file = $dir.'/'.$hash.'.json';
			Boo::file_put_json($file, $data); //Нужно пересохранить файл чтобы повторно не было срабатываний для этого родителя
			Boo::$proccess = true;//Перезаписать всю структуру
					
		}

		
		foreach ($data['item'] as $k => $v) {
			if (in_array($k,['parents'])) continue;
			$item[$k] = $v;
		}

		if (sizeof($args) > 0) {
			Boo::end();
		}
		Boo::end();

		return $data['result'];
	}
	/*public static function refresh($name = false, $args = false) {
		$dir = Boo::$conf['cachedir'];
		if (!$name) {
			Boo::$re = true; //Глобальный refresh
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
		} else {
			Boo::scandir($dir, function ($name) use ($dir) {
				$file = $dir.$name;
				$data = Boo::file_get_json($file);
				Load::loadTEXT($data['src'].$data['boo']);
			});
		}
		return true;
	}*/
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
Boo::$cwd = getcwd();