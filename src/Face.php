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
		if (isset($item['exec']['time'])) {
			$mytime = $item['exec']['time'];
		} else {
			$mytime = 0;
		}
		$childs = isset($item['rel'])? $item['rel']['childs']: $item['exec']['childs'];
		foreach ($childs as $cid) {
			$time = Face::getTime($items[$cid], $items);
			if (!$time) continue;
			if (!$mytime || $mytime < $time) $mytime = $time;
		}
		return $mytime;
	}
	public static function findAllChilds($item, $items, &$childs = array()) {
		$right = $item['id'];
		if (isset($childs[$item['id']])) return array();
		$childs[$item['id']] = true;
	
		foreach ($item['exec']['childs'] as $id) {
			Face::findAllChilds($items[$id], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function findAllMyChilds($list, $items, &$childs = array()) {
		
		foreach ($list as $id) {
			$childs[$id] = true;
			Face::findAllMyChilds($items[$id]['exec']['childs'], $items, $childs);
		}
		return array_keys($childs);

	}
	public static function search($path, $deep = false) {
		list($right, $item, $items, $path) = Face::init($path);
		if ($path == 'root') {
			return array_keys($items);
		} else {
			if (!$item) return array();
		}
		$childs = array();
		if (!empty($item['cls'])) {
			$childs[$item['id']] = true;
		}
		if ($deep) {
			Face::findAllMyChilds($item['rel']['childs'], $items, $childs);	
		} else if(empty($item['cls'])) { //Если группа
			foreach ($item['rel']['childs'] as $ch) {
				$childs[$ch] = true;
			}
		}
		$parents = array();
		foreach($childs as $cid => $v) {
			$parents += $items[$cid]['parents'];
		}
		$parents += $item['rel']['parents'];
		$childs = array_unique(array_merge($parents,array_keys($childs)));
		Once::setBooTime();
		return $childs;
	}
	
	public static function refresh($path, $deep) {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path, $deep);
		$srcs = array();
		
		foreach ($childs as $v) {
			$item = $items[$v];
			$srcs[] = $item['src'];
			Once::removeResult($item);
		}
		$srcs = array_values(array_unique($srcs));
		foreach ($srcs as $src) {
			if(!$src) $src = 'index.php';
			Load::loadTEXT($src);
		}
		Once::setBooTime();
		//Once::loadResult($item);
		Once::initSave();

		Once::$proccess = false;
		$src = Once::getItemsSrc();
		Load::unload($src);
		$src = Path::resolve($src);
		Face::$inited = false;
		//Once::clear('boo-face-init');
		//clearstatcache($src);
	}
	public static function remove($path, $deep = false) {
		list($right, $item, $items, $path) = Face::init($path);
		$childs = Face::search($path, $deep); 
		Once::setBooTime();
		foreach ($childs as $v) {
			Once::removeResult($items[$v]);
		}
	}
    public static function run(&$items, $root, $fn, $level = 1) {
        //Есть разница между первым упоминанием и вторым. Merge всегда к концу делается аналогично
        $right = Sequence::right($root);
        $item = &$items[$right[sizeof($right) - 1]];
        $fn($item, $root, $level);
        foreach ($item['exec']['childs'] as $id) {
            $newpath = Sequence::short(array_merge($right, [$id]));

            Face::run($items, $newpath, $fn, $level +1);
        }
    }
    public static function runGroups(&$items, $root, $fn, $level = 1) {
        //Есть разница между первым упоминанием и вторым. Merge всегда к концу делается аналогично
        $right = Sequence::right($root);
        $item = &$items[$right[sizeof($right) - 1]];
        $fn($item, $root, $level);

        foreach ($item['childgroups'] as $id) {
            $newpath = Sequence::short(array_merge($right, [$id]));

            Face::runGroups($items, $newpath, $fn, $level +1);
        }
    }
    public static $inited = [];
	public static function init($path = 'root') {
		if (Face::$inited) return Face::$inited;
		
		$src = Once::getItemsSrc();
		$items = FS::file_get_json($src);
		//if (!$items) $items = Once::$items;
		$groups = [];
		foreach ($items as $k => $item) {
			if (!isset($groups[$item['gid']])) {
				$groups[$item['gid']] = array(
					'id' => $item['gid'],
					'title' => $item['gtitle']
				);
				$groups[$item['gid']]['exec'] = array();
				$groups[$item['gid']]['isgroup'] = true;
				$groups[$item['gid']]['exec']['childs'] = array();
				$groups[$item['gid']]['childgroups'] = array();
			}
			$groups[$item['gid']]['exec']['childs'][] = $item['id'];
			foreach ($item['exec']['childs'] as $sub) {
				$groups[$item['gid']]['childgroups'][$items[$sub]['gid']] = true;
			}
		}
		
		foreach($groups as $k => $gr) {
			$groups[$k]['childgroups'] = array_values(array_unique(array_keys($groups[$k]['childgroups'])));
		}
		foreach ($items as $item) {
			Face::run($items, $item['id'], function(&$it, $path) use ($items) {
				if (empty($it['paths'])) $it['paths'] = array();
				if (empty($it['parents'])) $it['parents'] = array();
				if (empty($it['childgroups'])) $it['childgroups'] = array();

				$it['paths'][] = $path;
				$right = Sequence::right($path);
				array_pop($right);
				foreach ($right as $pid) {
					if (!in_array($pid, $it['parents'])) $it['parents'][] = $pid;
					
					$gid = $items[$pid]['gid'];
					if (!in_array($gid, $it['childgroups'])) $it['childgroups'][] = $gid;
				}
			});
		}
		
		foreach ($items as $k => $item) {
			$paths = $items[$k]['paths'];
			foreach ($paths as $i => $f) {
				foreach ($paths as $s) {
					if (strstr($s,$f) !== false && $f !== $s) {
						//Нашли совпадение можно удалить
						unset($paths[$i]);
						break;
					}
				}
			}
			$items[$k]['paths'] = array_values($paths);
		}
		
		foreach($groups as $k => $gr) {
			Face::runGroups($groups, $k, function(&$it, $path) use ($groups) {
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
					$it['parents'] = array_merge($it['parents'],$g['exec']['childs']);
				}

			});
		}


		if (!$path || $path == 'root') {
			$path = 'root';
			$right = [];
			$id = false;
			$item = false;
		} else {
			$right = Sequence::right($path);
			$id = $right[sizeof($right) - 1];

			if (isset($groups[$id])) {
				$item = $groups[$id];
				
			} else {
				$right = Sequence::right($path);
				$id = $right[sizeof($right) - 1];
				if (isset($items[$id])) {
					$item = $items[$id];
					$data = Once::loadResult($item);
					if ($data) {
						$item['exec'] = $data['exec'];
						$item['exec']['result'] = substr(Load::json_encode($item['exec']['result']),0,10000);
					}
				} else {
					$item = false;
				}
				
			}
		}
	

		if ($item) {
			if (empty($item['cls'])) {
				$item['paths'] = [];
				foreach ($item['exec']['childs'] as $cid) {
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
						$e = ($items[$rid]['gid'] == $ir[$i]);
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
			$item['rel']['childs'] = array_values(array_unique($childs));
			if (!empty($item['cls'])) {
				$item['rel']['parents'] = $item['parents'];
			} else {
				$item['rel']['parents'] = [];
				/*foreach($item['rel']['childs'] as $cid) {
					$item['rel']['parents'] = array_merge($item['rel']['parents'], $items[$cid]['parents']);
				}
				$item['rel']['parents'] = array_values(array_unique($item['rel']['parents']));*/
			}
		}
		
		Face::$inited = [$right, &$item, &$items, $path, $groups];
		return Face::$inited;
	}
	public static function getClearTimer($item, $items) {
		return $item['exec']['timer'];	
	}
	public static function index($path = 'root', $action = false) {

		$data = array();
		$data['layout'] = 'default';
		$data['path'] = $path;
		if ($action) $data[$action] = true;
		$data['msg'] = '';

		list($right, $item, $items, $path, $groups) = Face::init($path);

	
		$data['item'] = &$item;
		$data['right'] = $right;
		$data['groups'] = $groups;
		$data['items'] = $items;

		if ($path == 'root') { 
			$data['layout'] = 'root';
		}
		if (empty($item['cls'])) {
			$data['layout'] = 'group';
		}

		if ($path != 'root' && !$item) {
			$data['result'] = 0;
			if ($data['msg']) $data['msg'] .= '<br>';
			$data['msg'] .= 'Группа кэша не найдена';
		} else {
			$data['result'] = 1;
		}
		if (sizeof($data['right']) > 1) {
			$data['parent'] = $data['right'][sizeof($data['right']) - 2];
		}

		if ($path == 'root') { //Общее время всего кэша
			$data['exec'] = array();
			$data['pathsrc'] = '?-boo=root';
			$data['exec']['time'] = 0;
			$timer = 0;
			foreach ($items as $it) {
				$timer += $it['exec']['timer'];
				if (empty($it['exec']['time'])) continue;
				if ($it['parents']) continue;
				if ($data['exec']['time'] < $it['exec']['time']) $data['exec']['time'] = $it['exec']['time'];
			}

			/*$childs = Face::search($path, false);
			
			foreach ($childs as $cid) {
				$t = Face::getClearTimer($items[$cid], $items);

				//echo $t.' '.$items[$cid]['title'].'<br>';
				if ($t<0) {
					//echo '<pre>';
					//print_r($items[$cid]);
					//foreach() {
					//
					//}
					//exit;
				}
				$timer += $t;
			}
			//exit;*/
			$data['exec']['timerfr'] = $timer;
			$data['exec']['timerfr'] = round($data['exec']['timerfr'],2);
		} else if ($data['item']) {
			$time = Face::getTime($data['item'], $items);
			$data['item']['exec']['time'] = $time;

			$childs = Face::search($path, false); //не deep
			$timer = 0;

			foreach ($childs as $cid) { //Вместе с родителями
				$timer += Face::getClearTimer($items[$cid], $items);
			}

			$data['item']['exec']['timerch'] = $timer;


			$childs = Face::search($path, true);
			$timer = 0;
			foreach($childs as $cid) {
				$timer += Face::getClearTimer($items[$cid], $items);
			}
			
			$data['item']['exec']['timerfr'] = $timer;
			$data['item']['exec']['timerch'] = round($data['item']['exec']['timerch'],2);
			$data['item']['exec']['timerfr'] = round($data['item']['exec']['timerfr'],2);


			


			if (empty($data['item']['cls'])) {
				if ($path) $data['pathforgroup'] = $path.'.';
				else $data['pathforgroup'] = '';
				foreach ($data['item']['rel']['childs'] as $cid) {
					$src = $items[$cid]['src'];
					break;
				}
			} else {
				$data['item']['exec']['timer'] = round($data['item']['exec']['timer'],2);
				$src = $data['item']['src'];
				if (preg_match("/\?/", $src)) {
					$data['item']['pathsrc'] = $src.'&';
				} else {
					$data['item']['pathsrc'] = $src.'?';
				}
				$data['item']['pathsrc'] .= '-boo='.$data['path'];
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
		//echo '<pre>';
		//print_r($data);
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