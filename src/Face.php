<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use infrajs\ans\Ans;
use infrajs\load\Load;
use akiyatkin\fs\FS;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\sequence\Sequence;

class Face {
	public static function findAllParents($item, $items, &$parents = array()) {
		$right = [];
		foreach ($item['paths'] as $v) {
			$right[] = $v;
		}
		$right = implode('.',$right);
		$right = array_values(array_unique(Sequence::right($right)));
		foreach ($right as $id) {
			$parents[$id] = true;
		}
		return array_keys($parents);
	}
	public static function getTime($item, $items) {
		if (isset($item['time'])) {
			$mytime = $item['time'];
		} else {
			$mytime = 0;
		}
		if (isset($item['childs'])) {
			$childs = $item['childs'];
			foreach ($childs as $cid) {
				$time = Face::getTime($items[$cid], $items);
				if (!$time) continue;
				if (!$mytime || $mytime < $time) $mytime = $time;
			}
		}
		return $mytime;
	}
	public static function findAllChilds($item, $items, &$childs = array()) {
		$right = $item['id'];
		if (isset($childs[$item['id']])) return array();
		$childs[$item['id']] = true;
	
		foreach ($item['childs'] as $id) {
			Face::findAllChilds($items[$id], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function findAllMyChilds($list, $items, &$childs = array()) {
		
		foreach ($list as $id) {
			$childs[$id] = true;
			Face::findAllMyChilds($items[$id]['childs'], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function search($path, $deep = false) {
		list($item, $items, $groups) = Face::init($path);
		if ($path == 'root') {
			return array_keys($items);
		}
		if (!$item) return array();
		
		if (empty($item['isgroup'])) {
			return array($item['id']);
		} else {
			return $item['childs'];
		}
	}
	
	public static function refresh($path, $deep) {
		list($item, $items, $groups) = Face::init($path);
		$childs = Face::search($path, $deep);
		$srcs = array();
		foreach ($childs as $v) {
			$item = $items[$v];
			$srcs[] = $item['src'] ? $item['src'] : '-boo/empty.php';
			$item['cls']::removeResult($item);
		}
		$srcs = array_values(array_unique($srcs));
		foreach ($srcs as $src) {
			Load::loadTEXT($src);//Там что-то выполнилось
		}
		Cache::setBooTime();

		Cache::initSave();//Там что-то записалось

		Cache::$proccess = false;
		$src = Cache::getItemsSrc();
		Load::unload($src);
		$src = Path::resolve($src);
		Face::$inited = false;
		//Once::clear('boo-face-init');
		//clearstatcache($src);
	}
	public static function remove($path, $deep = false) {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path, $deep); 
		Cache::setBooTime(); //Обновляе кэши без условий если запускаются провеки
		foreach ($childs as $v) {
			$items[$v]['cls']::removeResult($items[$v]);
		}
	}
    public static $inited = [];
	public static function init($path = 'root') {
		if (Face::$inited) return Face::$inited;
		
		$src = Cache::getItemsSrc();
		$items = FS::file_get_json($src);

		$groups = [];
		foreach ($items as $k => $item) {
			if (!isset($groups[$item['gid']])) {
				$groups[$item['gid']] = array(
					'id' => $item['gid'],
					'title' => $item['gtitle']
				);
				$groups[$item['gid']]['isgroup'] = true;
				$groups[$item['gid']]['childs'] = array();
			}
			$groups[$item['gid']]['childs'][] = $item['id'];
		}

		if (!$path || $path == 'root') {
			$path = 'root';
			$id = false;
			$item = false;
		} else {
			$id = $path;
			if (isset($groups[$id])) {
				$item = $groups[$id];
			} else {
				if (isset($items[$id])) {
					$item = $items[$id];
					$data = $item['cls']::loadResult($item);
					if ($data) {
						$item['args'] = $data['args'];
						$item['timer'] = $data['timer'];
						$item['result'] = substr(Load::json_encode($data['result']),0,10000);
					}
				} else {
					$item = false;
				}
				
			}
		}
		
		Face::$inited = [&$item, &$items, &$groups];
		return Face::$inited;
	}
	public static function getClearTimer($item, $items) {
		return $item['timer'];	
	}
	public static function index($path = 'root', $action = false) {

		$data = array();
		$data['layout'] = 'default';
		$data['path'] = $path;
		if ($action) $data[$action] = true;
		$data['msg'] = '';

		list($item, $items, $groups) = Face::init($path);
		echo '<pre>';
		print_r($groups);
		exit;



		$data['item'] = &$item;
		$data['groups'] = $groups;
		$data['items'] = $items;

		if ($path == 'root') { 
			$data['layout'] = 'root';
		} else if (!empty($item['isgroup'])) {
			$data['layout'] = 'group';
		}

		if ($path != 'root' && !$item) {
			$data['result'] = 0;
			if ($data['msg']) $data['msg'] .= '<br>';
			$data['msg'] .= 'Группа кэша не найдена';
		} else {
			$data['result'] = 1;
		}

		if ($path == 'root') { //Общее время всего кэша
			$data['pathsrc'] = '?-boo=root';
			$data['time'] = 0;
			$timer = 0;
			foreach ($items as $it) {
				$timer += $it['timer'];
				if (empty($it['time'])) continue;
				if ($data['time'] < $it['time']) $data['time'] = $it['time'];
			}
			$data['timerch'] = $timer;
			$data['timerch'] = round($data['timerch'],2);
		} else if ($data['item']) {
			$time = Face::getTime($data['item'], $items);
			$data['item']['time'] = $time;

			$childs = Face::search($path);
			$timer = 0;
			foreach ($childs as $cid) { //Вместе с родителями
				$timer += Face::getClearTimer($items[$cid], $items);
			}
			$data['item']['timerch'] = $timer;
			$data['item']['timerch'] = round($data['item']['timerch'],2);


			if (!empty($data['item']['isgroup'])) {
				$data['item']['timer'] = 0;
				foreach ($data['item']['childs'] as $cid) {
					$src = $items[$cid]['src'];
					break;
				}
			} else {
				echo '<pre>';
				print_r($data['item']);
				$data['item']['timer'] = round($data['item']['timer'],2);
				$src = $data['item']['src'];
			}


			if (preg_match("/\?/", $src)) {
				$data['item']['pathsrc'] = $src.'&';
			} else {
				$data['item']['pathsrc'] = $src.'?';
			}
			$data['item']['pathsrc'] .= '-boo='.$data['path'];
		}
		
		$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');
		
		Ans::html($html);
	}

	public static function makePath($parpath, $data, $active = false) {
		$parright = Sequence::right($parpath);
		//$parpath =  Sequence::short($parright);
		$r = array();

		
		
		$newright = array();
		foreach ($parright as $i => $pid) {
			$par = array();
			$r[] = $pid;
			$par['right'] = $r;
			$par['active'] = $active;
			$par['path'] = Sequence::short($r);
			$par['id'] = $pid;
			if (isset($data['items'][$pid])) {
				$par['uptitle'] = 'Кэш - '.$data['items'][$pid]['title'];
				$par['title'] = $data['items'][$pid]['title'];
			} else if (isset($data['groups'][$pid])) {
				$par['uptitle'] = 'Группа кэша - '.$data['groups'][$pid]['title'];
				$par['title'] = $data['groups'][$pid]['title'];
			} else {
				$par['uptitle'] = 'Ошибка 404';
				$par['title'] = $pid;
			}
			$newright[$i] = $par;
		}
		return $newright;
	}
}