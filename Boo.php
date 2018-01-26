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
	if (Boo::$proccess) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сравниваем
		$items = Boo::initSave();

		$error = error_get_last();
		if (isset($error)) {
			//Обработка вечного таймаута, когда скрипт вылетает по времени и не успевает обработать отдельный кэш.
			if($error['type'] == E_ERROR
				|| $error['type'] == E_PARSE
				|| $error['type'] == E_COMPILE_ERROR
				|| $error['type'] == E_CORE_ERROR) {
				echo '<div>Boo: '.sizeof($items).'</div>';
			} 
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
	public static function run(&$items, $root, $fn, $level = 1) {
		//Есть разница между первым упоминанием и вторым. Merge всегда к концу делается аналогично
		$right = Sequence::right($root);
		$item = &$items[$right[sizeof($right) - 1]];
		$fn($item, $root, $level);
		foreach ($item['childs'] as $id) {
			$newpath = Sequence::short(array_merge($right, [$id]));
			
			Boo::run($items, $newpath, $fn, $level +1);
		}
	}
	public static function runGroups(&$items, $root, $fn, $level = 1) {
		//Есть разница между первым упоминанием и вторым. Merge всегда к концу делается аналогично
		$right = Sequence::right($root);
		$item = &$items[$right[sizeof($right) - 1]];
		$fn($item, $root, $level);
		
		foreach ($item['childgroups'] as $id) {
			$newpath = Sequence::short(array_merge($right, [$id]));
			
			Boo::runGroups($items, $newpath, $fn, $level +1);
		}
	}
	public static function id($path) {
		$r = Sequence::right($path);
		return $r[sizeof($r) - 1];
	}
	public static function initSave() {
		$src = Boo::$conf['cachedir'].'.tree.json';
		$items = Boo::file_get_json($src);
		if (!$items) {
			$items = Boo::$items;
		} else {
			foreach (Boo::$items as $id => $v) {
				if (!isset($items[$id])) {
					$items[$id] = Boo::$items[$id];
				} else {
					if(isset(Boo::$items[$id]['timer'])) {
						//Было обращение к кэшу и кэш переделывался
						$items[$id]['timer'] = Boo::$items[$id]['timer'];
						$items[$id]['time'] = Boo::$items[$id]['time'];
						$items[$id]['src'] = Boo::$items[$id]['src'];
						$items[$id]['group'] = Boo::$items[$id]['group']; //Разработка изменено название
						$items[$id]['title'] = Boo::$items[$id]['title']; //Разработка изменено название
					}
					
					
					//$items[$id]['parents'] = array_values(array_unique(array_merge($items[$id]['parents'], Boo::$items[$id]['parents'])));
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
		//$data = Load::loadJSON($file); Нельзя использовать после вывода контента из-за проверки заголовков
		$data = file_get_contents($src);
		$data = Load::json_decode($data);
		return $data;
	}
	public static function filesize($file) {
		$file = Path::resolve($file);
		if (!$file) return 0;
		if (!is_file($file)) return 0;
		return filesize($file);
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
	public static function &createItem($gid = null, $gtitle = null, $id = null, $title = null) {
		$item = array();
		$item['id'] = $id;
		$item['type'] = 'item';
		$item['group'] = array(
			'id' => $gid,
			'title' => $gtitle
		);
		
		//$item['parents'] = array();
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
	public static function &start($gid, $gtitle, $id, $title) {
		
		if (!isset(Boo::$items[$id])) {
			Boo::$items[$id] = &Boo::createItem($gid, $gtitle, $id, $title);
		}
		Boo::$items[$id]['counter']++;

		Boo::$parents[] = $id; //Для выхода
		$newpath = Sequence::short(Boo::$parents);

		if (Boo::$item && !in_array($newpath, Boo::$item['childs'])) {
			Boo::$item['childs'][] = $id;
		}

		Boo::$item = &Boo::$items[$id];
		
		return Boo::$items[$id];
	}
	public static function end() {
		array_pop(Boo::$parents);
		$r = null;
		if (sizeof(Boo::$parents)) Boo::$item = &Boo::$items[Boo::$parents[sizeof(Boo::$parents) - 1]];
		else Boo::$item = &$r;
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
		list($gid, $gtitle) = Boo::split($name);
		
		

		

		$hash = Hash::make($args);
		$id = $gid.'-'.$hash;

		$title = [];
		$i = 0;
		while (isset($args[$i]) && (is_string($args[$i]) || is_integer($args[$i]))) {
			$title[] = $args[$i];
			$i++;
		}
		$title = implode(' ', $title);

		if (!$title) $title = $hash;
		$parent = &Boo::$item;
		$item = &Boo::start($gid, $gtitle, $id, $title);	
		if (!sizeof($args)) Boo::setTitle($gtitle);
		
		
		$data = &Once::exec('boo-cache-'.$gid, function &($args, $r, $hash) use (&$parent, $fn, $gid, &$item, &$proccess) {
			
			$dir = Boo::$conf['cachedir'].$gid;
			Path::mkdir($dir);
			$file = $dir.'/'.$hash.'.json';
			$data = Boo::file_get_json($file);

			if ($data) return $data; //На родителя не влияет
			
			
			$data = array();
			$data['args'] = $args;
			$src = Boo::getSrc();
			
			$item['src'] = $src;
			$item['file'] = $file;
			$item['time'] = time();
			$item['timer'] = microtime(true);

			//$data['boo'] .= '-boo='.implode(',', Boo::$parents);
			Boo::$proccess = true;

			$is = Nostore::check( function () use (&$data, $fn, $args) { //Проверка был ли запрет кэша
				//$orig = Boo::$re; //Все кэши внутри сбрасываются. Родительский кэш нужно указать явно в -boo и всё обновится
				//Boo::$re = true;
				$data['result'] = call_user_func_array($fn, $args);
				//Boo::$re = $orig;
			});
			$item['timer'] = round(microtime(true) - $item['timer'], 4);
			$data['item'] = $item;
			if ($parent) {
				$parent['timer'] = $parent['timer'] + $item['timer'];
			}
			if (!$is) {
				Boo::file_put_json($file, $data); //С re мы всё равно сохраняем кэш
			}
			return $data;
		}, array($args));
		
		//$path = Sequence::short(Boo::$parents);
		//if (!in_array($path, $data['item']['childs'])) {
			/*
				У каждого кэша есть только один основной родитель! Не Массив.
				А childs много и может быть много родителей у которых кэш указан в childs хотя у самого кэша указан только один родитель

			*/
			//$data['item']['parents'] = array_values(array_unique(array_merge($data['item']['parents'], Boo::$item['parents'])));
			//Boo::$item['parents'] = $data['item']['parents']; //На случай если будет перезапись и tree.json ещё нет

			
			//$hash = Hash::make($args);//Нужно пересохранить результат, что бы мы всегда знали пути обращения к нему
			//$dir = Boo::$conf['cachedir'].$id;
			//$file = $dir.'/'.$hash.'.json';
			//Boo::file_put_json($file, $data); //Нужно пересохранить файл чтобы повторно не было срабатываний для этого родителя
			//Boo::$proccess = true;//Перезаписать всю структуру
					
		//}

		
		//foreach ($data['item'] as $k => $v) {
		//	if (in_array($k,['parents'])) continue;
		//	$item[$k] = $v;
		//}

		
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
	//public static $root = ['root','Кэш'];
}
Boo::$cwd = getcwd();
//Boo::start(Boo::$root);
//Boo::$item = &Boo::createItem('root','Кэш');
//Boo::$item['parents'] = array();
//Boo::$items['root'] = &Boo::$item;

