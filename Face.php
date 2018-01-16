<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\access\Access;
use infrajs\sequence\Sequence;

class Face {
	public static function findAllParents($item, $items, &$parents = array()) {
		$right = $item['id'];
		if (isset($parents[$item['id']])) return;
		$parents[$item['id']] = true;

		foreach ($item['parents'] as $v) {
			$right .='.'.$v;
		}
		$right = array_values(array_unique(Sequence::right($right)));
		foreach ($right as $id) {
			if (!isset($items[$id]['time'])) {
				$parents[$id] = true;
			} else {
				Face::findAllParents($items[$id], $items, $parents);
			}
		}
		return array_keys($parents);

	}
	public static function getTime($item, $items, $path = '', &$childs = array()) {

		if (isset($item['src'])) return [$item['timer'], $item['time']];
		
		if (isset($childs[$path])) return [0,0];
		$childs[$path] = true;
		$mytime = 0;
		$mytimer = 0;
		foreach ($item['childs'] as $cid) {
			if (!in_array($path, $items[$cid]['parents'])) continue;
			if (!$path) $newpath = $cid;
			else $newpath = $path.'.'.$cid;
			list($timer, $time) = Face::getTime($items[$cid], $items, $newpath, $childs);
			if (!$time) continue;
			$mytimer += $timer;
			if (!$mytime || $mytime < $time) $mytime = $time;
		}
		return [$mytimer, $mytime];
	}
	public static function findAllChilds($item, $items, &$childs = array()) {
		$right = $item['id'];
		if (isset($childs[$item['id']])) return;
		$childs[$item['id']] = true;
	
		foreach ($item['childs'] as $id) {
			Face::findAllChilds($items[$id], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function findAllMyChilds($item, $path, $items, &$childs = array(), &$paths = array()) {
		$right = $item['id'];
		if (isset($paths[$path.'.'.$item['id']])) return;
		$childs[$item['id']] = true;
		$paths[$path.'.'.$item['id']] = true;

		
		foreach ($item['childs'] as $id) {
			if (!in_array($path, $items[$id]['parents'])) continue;
			if ($path) $newpath = $path.'.'.$id;
			else $newpath = $id;
			Face::findAllMyChilds($items[$id], $newpath	, $items, $childs, $paths);
		}
		return array_keys($childs);

	}
	public static function search($path, $option = 'one') {
		list($right, $item, $items, $path) = Face::init($path);
		if (!$item) return;
		$childs = array();
		if ($option == 'wide') {
			Face::findAllChilds($item, $items, $childs);					
		}
		if ($option == 'deep') {
			Face::findAllMyChilds($item, $path, $items, $childs);
		}
		if ($option == 'one') {
			$childs[$item['id']] = true;
		}
		$parents = array();
		foreach ($childs as $id => $v) {
			Face::findAllParents($items[$id], $items, $parents);
		}
		$childs = array_unique(array_merge(array_keys($parents),array_keys($childs)));
		return $childs;
	}
	public static function refresh($path, $option = 'one') {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path, $option);
		$srcs = array();
		foreach ($childs as $v) {
			$item = $items[$v];
			if (!isset($item['file'])) continue;
			Boo::unlink($item['file']);
			$srcs[] = $item['src'];
			
		}

		$srcs = array_values(array_unique($srcs));
		foreach ($srcs as $src) {
			Load::loadJSON($src);
		}
		Boo::initSave();
		Boo::$proccess = false;
		$src = Boo::$conf['cachedir'].'tree.json';
		Load::unload($src);
	}
	public static function remove($path, $option = 'one') {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path, $option);
		
		foreach ($childs as $v) {
			$it = $items[$v];
			if (isset($it['file'])) {
				Boo::unlink($it['file']);
			}
		}
	}
	public static function init($path) {
		$src = Boo::$conf['cachedir'].'tree.json';
		$items = Boo::file_get_json($src);
		$right = Sequence::right($path);
		if (!$right || $right[0] == 'root') {
			$path = '';
			$right = array();
			$item = Boo::createItem('root', '');
			foreach ($items as $id => $child) {
				if (in_array('', $child['parents'])) {
					$item['childs'][] = $id;
				}
			}
			$items['root'] = $item;
		} else {
			$id = $right[sizeof($right) - 1];

			if (isset($items[$id])) {
				$item = $items[$id];
			} else {
				$item = false;
			}
			
		}

		return [$right, $item, $items,$path];
	}
	public static function list($path = 'root', $msg = '') {

		$data = array();

		$data['path'] = $path;
		$data['msg'] = $msg;

		list($right, $item, $items, $path) = Face::init($path);

		$data['items'] = $items;
		$data['right'] = $right;

		if (!$item) {
			$data['result'] = 0;
			if ($data['msg']) $data['msg'] .= '<br>';
			$data['msg'] .= 'Группа кэша не найдена';
		} else {
			$data['result'] = 1;
			$data['item'] = $item;
		}
		
		
		if (isset($data['item'])) {
			list($timer, $time) = Face::getTime($data['item'], $items, $path);
			$data['item']['timer'] = $timer;
			$data['item']['time'] = $time;
			
			if (isset($data['item']['src'])) {
					
				$d = Boo::file_get_json($data['item']['file']);
				if (!$d) {
					if ($data['msg']) $data['msg'] .= '<br>';
					$data['msg'] .= 'Кэш удалён';
					$data['result'] = 0;
				} else {
					$data['item']['result'] = $d['result'];
					$data['item']['args'] = $d['args'];
				}
				
			}
			$data['item']['dependencies'] = array();

			foreach ($data['item']['childs'] as $k => $cid) {
				$child = $items[$cid];
				
				$child['right'] = $data['right'];
				$child['right'][] = $cid;
				$child['path'] = Sequence::short($child['right']);
				$punkt = array(
					'title' => $child['title'],
					'path' => $child['path']
				);
					
				
				
				if (!in_array($path,$child['parents'])) {
					
					$data['item']['dependencies'][$k] = $punkt;
					$data['item']['dependencies'][$k]['path'] = $cid;

					unset($data['item']['childs'][$k]);
					continue;
				} else {
					$data['item']['childs'][$k] = $punkt;
				}

				
				
			}
			foreach ($data['item']['parents'] as $k => $parpath) {
				$r = Sequence::right($parpath);
				$r[] = $item['id'];
				$parpath = Sequence::short($r);
				$active = ($path == $parpath);
				$data['item']['parents'][$k] = Face::makePath($parpath, $items, $active);
			}
		}
		
		
		

		$html = Rest::parse('-boo/layout.tpl', $data, 'LIST');

		Ans::html($html);
	}
	/*public static function form() {
		$url = Ans::REQ('url','string');
		if ($url) {
			$host = $_SERVER['HTTP_HOST'];
			$r = explode($host, $url);
			if (sizeof($r) == 2) {
				$url = $r[1];	
			}
			$url = preg_replace('/\/+/', '', $url);
			if(!$url) $url = 'check';

			$txt = Load::loadTEXT($url);
			echo $txt;
			exit;
		}
		$data = array();

		$html = Rest::parse('-boo/layout.tpl', $data, 'FORM');
		Ans::html($html);
	}*/
	public static function makePath($parpath, $items, $active = false) {
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
			if (isset($items[$pid])) {
				$par['title'] = $items[$pid]['title'];
			} else {
				$par['title'] = $pid;
			}
			$newright[$i] = $par;
		}
		return $newright;
	}
}