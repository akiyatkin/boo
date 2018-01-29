<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\once\Once;
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

		//if (isset($item['src'])) return [$item['timer'], $item['time']];
		
		//if (isset($childs[$path])) return [0,0];
		//$childs[$path] = true;
		if (isset($item['timer'])) {
			$mytime = $item['time'];
			$mytimer = $item['timer'];
			$mysize = Boo::filesize($item['file']);
		} else {
			$mytime = 0;
			$mytimer = 0;
			$mysize = 0;
		}

		foreach ($item['childs'] as $cid) {
			//if (!in_array($path, $items[$cid]['parents'])) continue;
			//if (!$path) $newpath = $cid;
			//else $newpath = $path.'.'.$cid;
			list($timer, $time, $size) = Face::getTime($items[$cid], $items);
			if (!$time) continue;
			$mytimer += $timer;
			$mysize += $size;
			if (!$mytime || $mytime < $time) $mytime = $time;
		}
		return [$mytimer, $mytime, $mysize];
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
	public static function findAllMyChilds($list, $items, &$childs = array()) {
		
		foreach ($list as $id) {
			$childs[$id] = true;
			Face::findAllMyChilds($items[$id]['childs'], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function search($path) {
		list($right, $item, $items, $path) = Face::init($path);
		if ($path == 'root') {
			return array_keys($items);
		} else {
			if (!$item) return array();
		}
		$childs = array();
		if (isset($item['src'])) {
			$childs[$item['id']] = true;
		}
		Face::findAllMyChilds($item['rel']['childs'], $items, $childs);
		$parents = $item['rel']['parents'];
		$childs = array_unique(array_merge($parents,array_keys($childs)));
		return $childs;
	}
	public static function refresh($path) {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path);
		$srcs = array();
		foreach ($childs as $v) {
			$item = $items[$v];
			Boo::unlink($item['file']);
			$srcs[] = $item['src'];	
		}

		$srcs = array_values(array_unique($srcs));
		foreach ($srcs as $src) {
			if(!$src) $src = 'index.php';
			Load::loadTEXT($src);
		}

		Boo::initSave();

		Boo::$proccess = false;
		$src = Boo::$conf['cachedir'].'.tree.json';
		Load::unload($src);
		$src = Path::resolve($src);
		Once::clear('boo-face-init');
		clearstatcache($src);
	}
	public static function remove($path) {
		list($right, $item, $items, $path) = Face::init($path);

		$childs = Face::search($path);
		
		foreach ($childs as $v) {
			$it = $items[$v];
			Boo::unlink($it['file']);
		}
	}
	//root - Группа1 - Кэш1 - Группа2 - Кэш2
	//root - Группа3 - Кэш1


	public static function checkParents($item, &$items, $path = '') {
		
	
		if ($path && !in_array($path, $items[$item['id']]['parents'])) {
			$items[$item['id']]['parents'][] = $path;
		}
		//$newpath = $newpath? $item['id']: $newpath.'.'.$item['id'];
		foreach ($item['childs'] as $cid) {

			if ($item['parents']) {

				foreach ($item['parents'] as $newpath) {

					$newpath = $newpath? $newpath.'.'.$item['id']: $item['id'];
					
					
					Face::checkParents($items[$cid], $items, $newpath);
				}
			} else {

				$newpath = $path;
				Face::checkParents($items[$cid], $items, $newpath);
			}
		}
		

		/*foreach ($item['childs'] as $cid) {
			if (!in_array($path, $items[$cid]['parents'])) {
				//$items[$cid]['parents'][] = $path;
			}
			foreach ($items[$cid]['parents'] as $newpath) {
				//$newpath = $newpath? $item['id']: $newpath.'.'.$item['id'];
				Face::checkParents($items[$cid], $items, $newpath);
			}
		}*/
		
	}
	public static function getParents($cid, $parents, $items, $add = [], $take = true) {
		$list = $parents[$cid];
		$res = array();
		array_unshift($add, $cid);

		$take = isset($items[$cid]['src']) || $take;
		

		if ($list) {
			
			foreach ($list as $pid) {

				$r = Face::getParents($pid, $parents, $items, $add, $take);
				$res = array_merge($r, $res);
				
			}
			
		} else {
			if ($take) {
				$res = [Sequence::short($add)];
			} else {
				$res = [];
			}
		}
		
		return $res;
	}
	public static function init($path = 'root') {
		return Once::exec('boo-face-init', function () use ($path){
			$src = Boo::$conf['cachedir'].'.tree.json';
			$items = Boo::file_get_json($src);
			if (!$items) $items = Boo::$items;
			
			$groups = [];
			foreach ($items as $item) {
				if (!isset($groups[$item['group']['id']])) {
					$groups[$item['group']['id']] = $item['group'];
					$groups[$item['group']['id']]['type'] = 'group';
					$groups[$item['group']['id']]['childs'] = array();
					$groups[$item['group']['id']]['childgroups'] = array();
				}
				$groups[$item['group']['id']]['childs'][] = $item['id'];
				foreach ($item['childs'] as $sub) {
					$groups[$item['group']['id']]['childgroups'][$items[$sub]['group']['id']] = true;
				}
			}
			foreach($groups as $k => $gr) {
				$groups[$k]['childgroups'] = array_values(array_unique(array_keys($groups[$k]['childgroups'])));
			}

			foreach ($items as $item) {
				Boo::run($items, $item['id'], function(&$it, $path) use ($items) {
					if(empty($it['paths'])) $it['paths'] = array();
					if(empty($it['parents'])) $it['parents'] = array();
					if(empty($it['childgroups'])) $it['childgroups'] = array();

					$it['paths'][] = $path;
					$right = Sequence::right($path);
					if (sizeof($right) < 2) return;
					$pid = $right[sizeof($right) - 2];
					if (!in_array($pid, $it['parents'])) $it['parents'][] = $pid;
					$gid = $items[$pid]['group']['id'];
					if (!in_array($gid, $it['childgroups'])) $it['childgroups'][] = $gid;
				});
			}
			foreach ($items as $k => $item) {
				$paths = $items[$k]['paths'];
				foreach ($paths as $i => $f) {
					$save = true;
					foreach ($paths as $s) {
						if (strstr($s,$f) !== false && $f !== $s) {
							//Нашли совпадение можно удалить
							unset($paths[$i]);
							break;
						}
					}
				}
				$items[$k]['paths'] = $paths;
			}
		

			foreach($groups as $k => $gr) {
				Boo::runGroups($groups, $k, function(&$it, $path) use ($groups) {
					//if (empty($it['paths'])) $it['paths'] = array();
					if (empty($it['parentgroups'])) $it['parentgroups'] = array();
					if (empty($it['parents'])) $it['parents'] = array();

					//$it['paths'][] = $path;
					$right = Sequence::right($path);
					if (sizeof($right) < 2) return;
					$pid = $right[sizeof($right) - 2];
					if (!in_array($pid, $it['parentgroups'])) {
						$it['parentgroups'][] = $pid;
						$g = $groups[$pid];
						$it['parents'] = array_merge($it['parents'],$g['childs']);
					}

				});
			}


			if (!$path || $path == 'root') {
				$path = 'root';
				$right = [];
				$id = false;
				$item = false;
			} else {
				/*
					$path некий путь. Ищется
					На странице показывается
					
					path - способы обращения к текущему адресу и группы и кэша
					childs - зависимый кэш
					groups - вложенные группы

					parents - родители кэш
					parentgroups - родители группы

					
					dependencies - зависимый кэш не совпадающий с текущим path




				*/
				$right = Sequence::right($path);
				$id = $right[sizeof($right) - 1];
				if (isset($groups[$id])) {
					$item = $groups[$id];
					
				} else {
					$right = Sequence::right($path);
					$id = $right[sizeof($right) - 1];
					if (isset($items[$id])) {
						$item = $items[$id];
					} else {
						$item = false;
					}
					
				}
			}
			if ($item) {
				if (empty($item['src'])) {
					$item['paths'] = [];
					foreach ($item['childs'] as $cid) {
						$item['paths'] = array_merge($item['paths'], $items[$cid]['paths']);
					}
					$item['paths'] = array_values(array_unique($item['paths']));
				}
				$item['rel'] = array();
				$item['rel']['path'] = $path;
				$item['rel']['paths'] = [];

				
				
				

				$childs = array();
				foreach($item['paths'] as $p) {

					//Надо сравнить $p и $path
					$right = Sequence::right($path);
					$pr = Sequence::right($p);
					
					$ir = array_reverse($right);//Обязательный путь состоящий из групп и кэшей
					$pr = array_reverse($pr); //Путь, который может отличаться, но должен содержать всебе ir

					$i = 0;
					$e = false;

				
					foreach ($pr as $k => $rid) {
						if (isset($groups[$ir[$i]])) {
							$e = ($items[$rid]['group']['id'] == $ir[$i]);
						} else {
							$e = ($ir[$i] == $rid);
						}
						if ($e) {
							$e = false;
							$i++;
							if (empty($ir[$i])) {
								$childs[] = $pr[0];
								$item['rel']['paths'][] = $p;
								break;//найден последний элемент в ir есть pr
							}
						}
					}
				}
				//echo '<pre>';
				//print_r($item['childgroups']);
				//exit;
				$item['rel']['childs'] = array_values(array_unique($childs));
				if (isset($item['src'])) {
					$item['rel']['parents'] = $item['parents'];

				} else {
					
					$item['rel']['parents'] = [];
					foreach($item['rel']['childs'] as $cid) {
						$item['rel']['parents'] = array_merge($item['rel']['parents'], $items[$cid]['parents']);
					}
					$item['rel']['parents'] = array_values(array_unique($item['rel']['parents']));
				}
			}
			return [$right, &$item, &$items, $path, $groups];
		});
		
	}
	
	public static function list($path = 'root', $action = false) {

		$data = array();
		$data['layout'] = 'default';
		$data['path'] = $path;
		if ($action) $data[$action] = true;
		$data['msg'] = '';

		list($right, $item, $items, $path, $groups) = Face::init($path);

		$data['item'] = $item;
		$data['right'] = $right;
		$data['groups'] = $groups;
		$data['items'] = $items;
		
		if ($path == 'root') { 
			$data['layout'] = 'root';
		}
		if ($item['type'] == 'group') {
			$data['layout'] = 'group';
		}
		

		if ($path == 'root') { //Общее время всего кэша
			$data['time'] = 0;
			$data['timer'] = 0;
			$data['size'] = 0;
			foreach ($items as $it) {
				if ($data['time'] < $it['time']) $data['time'] = $it['time'];
				$data['timer'] += $it['timer'];
				$data['size'] += Boo::filesize($it['file']);
			}
			$data['size'] = round($data['size']/1000000,3);
		}
		
		if ($path != 'root' && !$item) {
			$data['result'] = 0;
			if ($data['msg']) $data['msg'] .= '<br>';
			$data['msg'] .= 'Группа кэша не найдена';
		} else {
			$data['result'] = 1;
			$data['item'] = $item;
		}
		
		if (sizeof($data['right']) > 1) {
			$data['parent'] = $data['right'][sizeof($data['right']) - 2];
		}
		if ($data['item']) {
			list($timer, $time, $size) = Face::getTime($data['item'], $items);

			$data['item']['timer'] = $timer;
			$data['item']['time'] = $time;
			$data['item']['size'] = round($size/1000000,3);
			
			if (isset($data['item']['src'])) {
					
				$d = Boo::file_get_json($data['item']['file']);
				if (!$d) {
					$data['remove'] = true;
				} else {
					$data['item']['result'] = $d['result'];
					$data['item']['args'] = $d['args'];
				}
				
			} else {
				if ($path) $data['pathforgroup'] = $path.'.';
				else $data['pathforgroup'] = '';
			}
			
			foreach ($data['item']['rel']['paths'] as $k => $parpath) {
				$active = ($path == $parpath);
				$data['item']['rel']['paths'][$k] = Face::makePath($parpath, $data, $active);

			}
			//$data['item']['childgroups'] = Face::makePath($data['item']['childgroups'], $data);
			$data['right'] = Face::makePath($data['path'], $data);

			foreach ($data['item']['paths'] as $k => $parpath) {
				$active = ($path == $parpath);
				$data['item']['paths'][$k] = Face::makePath($parpath, $data, $active);
			}
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