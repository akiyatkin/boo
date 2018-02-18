<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\once\Once;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\sequence\Sequence;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;


class Cache extends Hierarchy
{
	public static $conf = array(
		'cachedir' => '!boo/'
	);
	public static $onces = array();

	public static function initSave() {
		$src = Cache::$conf['cachedir'].'.tree.json';
		
		$items = Cache::file_get_json($src);

		if (!$items) {
			$items = Cache::$items;
		} else {
			foreach (Cache::$items as $id => $v) {
				if (!isset($items[$id])) {
					$items[$id] = Cache::$items[$id];
				} else {
					if(isset(Cache::$items[$id]['timer'])) {
						//Было обращение к кэшу и кэш переделывался
						$items[$id]['timer'] = Cache::$items[$id]['timer'];
						$items[$id]['time'] = Cache::$items[$id]['time'];
						$items[$id]['file'] = Cache::$items[$id]['file'];
						$items[$id]['src'] = Cache::$items[$id]['src'];
						$items[$id]['group'] = Cache::$items[$id]['group']; //Разработка изменено название
						$items[$id]['title'] = Cache::$items[$id]['title']; //Разработка изменено название
					}
					$items[$id]['childs'] = array_values(array_unique(array_merge(Cache::$items[$id]['childs'],$items[$id]['childs'])));
				}
			}
		}
		FS::file_put_json($src, $items);
		return $items;
	}

	public static $boo = false;
	public static function isre($name) {
		if (!is_array(Cache::$boo)) {
			if (isset($_GET['-boo'])) Cache::$boo = explode(',',$_GET['-boo']);
			else return false;	
		}
		return in_array($name, Cache::$boo);
	}
	public static $re = false; //Глобальный refresh
    public static $root = array(
	    'id' => false,
        'childs' => array()
    );
    public static $item = null;
	public static $proccess = false;
	public static $items = array();
	public static $parents = array();
	public static function setTitle($title) {
		Cache::$item['title'] = $title;
	}
	public static function getSrc() {
		$src = preg_replace("/^\/+/", "", $_SERVER['REQUEST_URI']);
		return $src;
	}
    public static function &start($gid, $gtitle, $id, $title) {
        $item = &Cache::createItem($gid, $gtitle, $id, $title);
        Cache::$item['childs'][] = $id;
        Cache::$parents[] = $id;
        Cache::$item = &$item;
        return $item;
    }
    public static function end() {
        array_pop(Cache::$parents);
        if (sizeof(Cache::$parents)) Cache::$item = &Cache::$items[Cache::$parents[sizeof(Cache::$parents) - 1]];
        else Cache::$item = &Cache::$root;
    }
    public static function &createOnce($gid, $hash) {
	    $id = $gid.'-'.$hash;
        if (isset(Cache::$onces[$id])) return Cache::$onces[$id];
        if (!isset(Cache::$onces[$id])) Cache::$onces[$id] = array();
        $once = array();
        $once['res'] = null;
        $once['id'] = $id;
        $once['childs'] = array();
        Cache::$onces[$id] = &$once;
        return $once;
    }
    Cache::$oparents = array();
    public static function &once($fn, $args = array()) {
        list($id, $gid, $hash, $title) = Cache::hash($args);
        $once = &Cache::createOnce($gid, $hash);
        Cache::$once['childs'][] = $id;
        Cache::$oparents[] = $id;
        Cache::$once = $once;

        $len = sizeof(Cache::$item['childs']);
        $r = &call_user_func_array($fn, $args);
        $childs = array_slice(Cache::$item['childs'],0,$len,true);

        Cache::$onces[$gid][$hash] = array(
            'res' => &$r,
            'childs' => $childs
        );
        } else { //Не будет выполнения. ЗАпись в childs надо сэмитировать
            Cache::$item['childs'] = array_splice(Cache::$onces[$gid][$hash]['childs'], 0, 0, Cache::$item['childs']);
        }
        return Cache::$onces[$gid][$hash]['res'];
    }
	public static function &cache($gtitle, $fn, $args = array())
	{
        list($id, $gid, $hash, $title) = Cache::hash($args);

		$parent = &Cache::$item;

		$item = &Cache::start($gid, $gtitle, $id, $title);
		if (!sizeof($args)) Cache::setTitle($gtitle);

		$data = &Cache::once(function &($args, $r, $hash) use ($id, &$parent, $fn, $gid, &$item, &$proccess) {
			
			$dir = Cache::$conf['cachedir'].$gid;
			Path::mkdir($dir);
			
		
			$file = $dir.'/'.$hash.'.json';
			$data = FS::file_get_json($file);

			if ($data) return $data; //На родителя не влияет
			
			
			$data = array();
			$data['hash'] = $hash;
			$data['args'] = $args;
			$src = Cache::getSrc();
			
			$item['src'] = $src;
			$item['file'] = $file;
			$item['time'] = time();
			$item['timer'] = microtime(true);

			//$data['boo'] .= '-boo='.implode(',', Cache::$parents);
			Cache::$proccess = true;

			$is = Nostore::check( function () use (&$data, $fn, $args) { //Проверка был ли запрет кэша
				//$orig = Cache::$re; //Все кэши внутри сбрасываются. Родительский кэш нужно указать явно в -boo и всё обновится
				//Cache::$re = true;
				$data['result'] = call_user_func_array($fn, $args);
				//Cache::$re = $orig;
			});
			$item['timer'] = round(microtime(true) - $item['timer'], 4);
			$data['item'] = $item;
			if ($parent) {
				$parent['timer'] = $parent['timer'] + $item['timer'];
			}
			if (!$is) {
				FS::file_put_json($file, $data); //С re мы всё равно сохраняем кэш
			}
			return $data;
		}, array($args));

		Cache::end();

		return $data['result'];
	}
}
Cache::$item = &Cache::$root;


