<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\once\Once;
use infrajs\access\Access;
use akiyatkin\fs\FS;
use infrajs\sequence\Sequence;
use infrajs\router\Router;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;


class Cache extends Once
{
	public static $cwd = false;
	public static $proccess = false;
	public static $conds = array();
	public static $conf = array(
		'cachedir' => '!boo/',
		'time' => 0
	);
	public static $admin = false;
	public static $condscounter = 0;
	public static function getCondTime($cond) {
		Cache::$condscounter++;
		//return call_user_func_array($cond['fn'], $cond['args']);   
		//$id = json_encode($cond, JSON_UNESCAPED_UNICODE);
		$id = print_r($cond, true);
		//if (is_array($cond['fn'])) $id = $cond['fn'][0].':'.$cond['fn'][1];
		if (isset(Cache::$conds[$id])) return Cache::$conds[$id];
		Cache::$conds[$id] = call_user_func_array($cond['fn'], $cond['args']);   
		return Cache::$conds[$id];
	}
	public static function setBooTime() {
		$sys = FS::file_get_json('!.infra.json');
		if (!isset($sys['boo'])) $sys['boo'] = array();
		$sys['boo']['time'] = time();
		Cache::$conf['time'] = $sys['boo']['time'];
		FS::file_put_json('!.infra.json', $sys);
	}
	public static function getBooTime() {
		return Cache::$conf['time'];
	}
	
	public static function getItemsSrc() {
		return Cache::$conf['cachedir'].'.items.json';
	}
	public static function setTitle($title, &$item = false)
	{
		if (!$item) $item = &Once::$item;
		$item['title'] = $title;
	}
	public static function &createItem($args = array(), $condfn = array(), $condargs = array(), $level = 0) {
		$level++;
		$item = &Once::createItem($args, $condfn, $condargs, $level);
		
		$title = [];
		$i = 0;
		while (isset($args[$i]) && (is_string($args[$i]) || is_integer($args[$i]))) {
			$title[] = $args[$i];
			$i++;
		}
		$title = implode(' ', $title);
		if (!$title) $title = $item['hash'];

		$item['cls'] = get_called_class();   
		$item['src'] = static::getSrc();
		$data = static::loadResult($item);
		
		if ($data) {
			$item['exec'] = $data['exec'];
		}
		return $item;
	}

	/**
	 * Адрес текущего GET запроса
	 *
	 * @return null|string|string[]
	 */
	public static function getSrc()
	{
		$src = preg_replace("/^\/+/", "", $_SERVER['REQUEST_URI']);
		$src = preg_replace("/\-boo=[^&]*&{0,1}/",'',$src);
		$src = preg_replace("/\-update=[^&]*&{0,1}/",'',$src);
		$src = preg_replace("/\-access=[^&]*&{0,1}/",'',$src);
		$src = preg_replace("/[\?&]$/",'',$src);
		if (!$src) {
			$src = '-boo/empty';
		}
		return $src;
	}

	public static function setStartTime() {
		$sys = FS::file_get_json('!.infra.json');
		if (!isset($sys['boo'])) $sys['boo'] = array();
		$sys['boo']['starttime'] = time();
		$sys['boo']['time'] = $sys['boo']['starttime'];
		Cache::$conf['starttime'] = $sys['boo']['starttime'];
		Cache::$conf['time'] = $sys['boo']['time'];
		FS::file_put_json('!.infra.json', $sys);
	}
	public static function getStartTime() {
		return Cache::$conf['starttime'];
	}
	public static function isChange(&$item) {
		$r = static::_isChange($item);

		//Мы хотим оптимизировать, что бы время проверки условий записалось и больше условия не проверялись
		//Для этого при false нужно записать время и сохранить кэш. 
		//Но false для пользователя не значит что будет false для админа по этому время установить можно не всегда.
		if ($r || ($r === 0 && !Access::isTest())) {
			//0 - означает что false из после проверки условий, тогда и пользователь может сохранить
			if (!Cache::$proccess) Cache::$proccess = $item['id']; 
			//Нельзя что бы остался time старее AdminTime это будет всегда зпускать проверки
			//Надо один раз сохранить время после AdminTime и проверки запускаться для посетителя не будут
			$item['exec']['time'] = time();//Обновляем время, даже если выполнения далее не будет, что бы не запускать проверки
			$item['exec']['notloaded'] = true; //Ключ который заставит кэш сохранится повторно
		}
		return $r;
	}
	public static function _isChange(&$item) {
		if (empty($item['exec']['start'])) return true; //Кэша вообще нет ещё
		if (!empty($item['exec']['notloaded'])) return false; //Только что выполненный элемент

		
		$atime = Access::adminTime(); //Заходил Админ
		if (!Access::isTest()) { //Для обычного человека сравниваем время последнего доступа
			if ($atime <= $item['exec']['time']) return false; //И не запускаем проверки. 
			//Есть кэш и админ не заходил
		} else {
			/*if ($atime <= $item['exec']['time']) { //Проверки тестировщика запускаются только если кэш старый
				if (!Router::$main) {
					return false;// Не проверять актуальный кэш Проверять только в главном запросе    
				}
			}*/
		} 
		//Нужно волшебное условие для редактора, после которого проверка запустится

		//Bootime сбрасывает все безусловные кэши это жесть нам не надо менять.
		//Admintime запускает проверки для пользователя
		//Редактор всегда всё проверяет
		
		//die('asdf');
		//Горячий сброс кэша, когда тестировщик обновляет сайт, для пользователей продолжает показываться старый кэш.
		// -boo сбрасывает BooTime и AccessTime и запускает проверки для всех пользователей
		// -Once::setStartTime() сбрасывает StartTime и BooTime и кэш создаётся только для тестировщика и без проверок
		$atime = static::getStartTime();
		if ($atime > $item['exec']['time']) {
			return true; //Проверки Не важны, есть отметка что весь кэш устарел
		}
		

		
		if (!$item['condfn'] && !static::isAdmin($item)) {//Если кэш вручную не брасывается любое boo сбрасывает кэш без условий
			$atime = Cache::getBooTime();
			if ($atime > $item['exec']['time']) {
				return true; //Проверок нет, есть bootime - выполняем
			}
		}
 
		foreach ($item['exec']['conds'] as $cond) {
			$time = Cache::getCondTime($cond);
			if ($time >= $item['exec']['time']) {
				return true;
			}
		}   
		
		return 0;
	}
	/**
	 * Сохраняет результат в место постоянного хранения.
	 * Используется в расширяемых классах.
	 * В once сохранять ничего не надо.
	 * Сохранение должно вызываться в execfn
	 * @param $item
	 */
	public static function saveResult($item) {
		$dir = Cache::$conf['cachedir'].$item['gid'];
		$file = $dir.'/'.$item['hash'].'.json';
		FS::mkdir($dir);
		FS::file_put_json($file, $item);
	}
	public static function removeResult($item){
		$dir = Cache::$conf['cachedir'].$item['gid'];
		$file = $dir.'/'.$item['hash'].'.json';
		FS::unlink($file);
	}
	public static function loadResult($item) {
		$dir = Cache::$conf['cachedir'].$item['gid'];
		$file = $dir.'/'.$item['hash'].'.json';
		$data = FS::file_get_json($file);
		return $data;
	}
	public static function getDurationTime($strtotime) {
		/*
			-1 month
			-1 day
			-1 week
			last Monday
			last month
			last day
			last week
			last friday
		*/
		return strtotime($strtotime);
	}
	/**
	* Кэш до следующей авторизации админа
	**/
	//public static function getAccessTime() {
	//    return Access::adminTime();
	//}
	public static function getModifiedTime($src) {
		$src = Path::theme($src);
		if (!$src) return 0;
		return filemtime($src);
	}
	public static function execfn(&$item, $fn)
	{
		$item['exec']['nostore'] = Nostore::check(function () use (&$item, $fn) { //Проверка был ли запрет кэша
			$item['exec']['result'] = call_user_func_array($fn, $item['args']);
		});
		if ($item['exec']['nostore']) {
			unset($item['notloaded']);
		}
	}

	public static function isAdmin($item) {
		if (!isset($item['cls'])) return false;
		return $item['cls']::$admin;
	}
	public static function isSave($item) {
		if (!isset($item['cls'])) return false;
		return true;
	}
	
	public static function initSave() {
		$admins = array();
		foreach (Once::$items as $id => &$v) {
			if ( !empty($v['exec']['notloaded']) && Cache::isAdmin($v) ) $admins[$id] = &$v;
		}
	   
		foreach ($admins as $id => &$v) {
			$rems = array();
			$v['exec']['childs'] = array_values(array_unique($v['exec']['childs']));
			for ($k = 0; $k < sizeof($v['exec']['childs']); $k++ ) {
				$cid = $v['exec']['childs'][$k];
				if (Cache::isAdmin(Once::$items[$cid])) continue; //Если Admin оставляем
				$rems[] = $cid;
				array_splice($v['exec']['childs'], $k, 1, Once::$items[$cid]['exec']['childs']);
				$k--;
			}
			$rems = array_unique($rems); //Убранные childs
			foreach ($rems as $cid) {
				Once::$items[$id]['exec']['timer'] += Once::$items[$cid]['exec']['timer'];
			}
			$v['exec']['childs'] = array_values(array_unique($v['exec']['childs']));
		}

		if ($admins) {
			$src = Cache::getItemsSrc();
			$items = FS::file_get_json($src);
			foreach($admins as $id => $it) {
				unset($it['exec']['result']);
				$items[$id] = $it;
			}
			FS::file_put_json($src, $items);
		}
		$count = 1;
		$timer = 0;
		foreach (Once::$items as $id => &$v) {
			if (!Cache::isSave($v)) continue;
			if (!empty($v['exec']['notloaded'])) { //Выполнено сейчас и не загрруженное не используется
				unset($v['exec']['notloaded']); //notloaded появляется при выполнении. Сохраняем всегда без него
				$v['exec']['childs'] = array_values(array_unique($v['exec']['childs']));
				//echo $v['gtitle'].':'.$v['title'].'<br>';
				$count++;
				$timer += $v['exec']['timer'];
				$v['cls']::saveResult($v);
			}
		}
		/*if (Router::$main) {
			echo 'Cache saving: '.$count.', '.$timer.' c. items - '.sizeof(Once::$items).', conds - '.sizeof(Cache::$conds).'</div>';    
		}*/
	}
	public static function init () {
		Cache::$cwd = getcwd();
		register_shutdown_function( function () {
			chdir(Cache::$cwd);
			$save = false;
			if (Cache::$proccess) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сохраняем
				$error = error_get_last();
				
				//E_WARNING - неотправленное письмо mail при неправильно настроенном сервере
				if (is_null($error) || ($error['type'] != E_ERROR
						&& $error['type'] != E_PARSE
						&& $error['type'] != E_COMPILE_ERROR)) {
					$save = true;
				}
			}
			if (Router::$main) {
				//echo '<div style="font-size:10px; padding:5px; text-align:right">';
			}
			if ($save) {
				Cache::initSave();
			} else {
				/*if (Router::$main) {
					echo 'Cache usage: items - '.sizeof(Once::$items).', conds - '.sizeof(Cache::$conds);
				}*/
			}
			/*if (Router::$main) {
				echo '</div>';
				echo '<br>Количество обращений к выполненнию проверки: '.Cache::$condscounter;
				echo '<br>Количество разных выполненных проверок: '.sizeof(Cache::$conds);
				echo '<br>Количество кэш-элементов: '.sizeof(Once::$items);
				echo '<pre>';
				print_r(Cache::$conds);
			}*/
		});
	}
}
Cache::init();

