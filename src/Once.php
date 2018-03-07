<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\nostore\Nostore;
use akiyatkin\fs\FS;
use infrajs\access\Access;
use infrajs\config\Config;

class Once
{
	public static $type = 'Once';
	public static $cwd = false;
	public static $admin = false;
	public static $parents = [];
	public static $childs = array();
	public static $item = false;
	public static $re = false; //Метка о сбросе скрытого кэша
	public static $items = array();
	public static $conf = array(
		'cachedir' => '!boo/',
		'time' => 0
	);
	public static $proccess = false;
	public static $conds = array();
	public static function getCondTime($cond) {
		$id = json_encode($cond, JSON_UNESCAPED_UNICODE);
		if (isset(Once::$conds[$id])) return Once::$conds[$id];
		Once::$conds[$id] = call_user_func_array($cond['fn'], $cond['args']);	
		return Once::$conds[$id];
	}
	public static function &createItem($gtitle = false, $args = array(), $condfn = array(), $condargs = array(), $level = 0)
	{
		$level++;
		list($gid, $id, $title, $hash, $fn) = static::hash($args, $level);
		if (isset(Once::$items[$id])) return Once::$items[$id];
		$item = array();
		
		
		if ($gtitle) {
			$item['gtitle'] = $gtitle;
			$item['gid'] = Path::encode($gtitle.'-'.$gid);
		} else if (!$gtitle) {
			$gtitle = $fn;
			$item['gtitle'] = $gtitle;
			$item['gid'] = $gid;
		}	
		if (sizeof($args) == 0) {
			$item['title'] = $gtitle;
		} else {
			$item['title'] = $title;
		}
		$item['id'] = $id;
		$item['cls'] = get_called_class();
		$item['condfn'] = $condfn;
		$item['condargs'] = $condargs;
		$item['fn'] = $fn;
		$item['args'] = $args;
		
		$item['hash'] = $hash;

		$item['src'] = static::getSrc();
		
		
    		
    	


		$data = static::loadResult($item);
		
		if (!$data) {
			$item['exec'] = array();
			$item['exec']['conds'] = array();
			$item['exec']['childs'] = array();
			$item['exec']['timer'] = 0;

			if ($condfn) {
	    		$cond = array('fn' => $condfn, 'args' => $condargs);
	    	} else if(!static::$admin) {//Обновление по последнему boo только для кэшей у которых нет своего условия
	    		$cond = array('fn' => ['akiyatkin\\boo\\Once','getBooTime'], 'args' => array());
	    	} else {
	    		$cond = false;
	    	}
	    	if ($cond) $item['exec']['conds'][] = $cond;

		} else {
			$item['exec'] = $data['exec'];
			$item['exec']['timer'] = 0;
		}

		
		Once::$items[$id] = &$item;
		return $item;
	}
	public static function setBooTime() {
		$sys = FS::file_get_json('!.infra.json');
		if (!isset($sys['boo'])) $sys['boo'] = array();
		$sys['boo']['time'] = time();
		Once::$conf['time'] = $sys['boo']['time'];
		FS::file_put_json('!.infra.json', $sys);
	}
	public static function getBooTime() {
        return Once::$conf['time'];
    }
	public static function getItem(){
		return static::$item;
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


	/**
	 * На заданное количество шагов назад определяем файл и сроку вызов, по которым формируем $gid
	 * На основе $args формируем $hash который вместе с $gid формирует $id индетифицирующий место вызова с такими аргуентами
	 * Простые данные в $args формируют заголовок $title
	 * Возвращается массив данных, ожидается конструкция с list
	 *
	 * @param array $args
	 * @param int $level
	 * @return array
	 */
	public static function hash($args = array(), $level = 0)
	{
		$hash = Path::encode(json_encode($args,JSON_UNESCAPED_UNICODE));

		$callinfos = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 3);
		$fn = Path::encode($callinfos[$level+2]['function']);
		$callinfo = $callinfos[$level+1];
		$path = $callinfo['file'];
		$src = realpath('.');
		$path = str_replace($src . DIRECTORY_SEPARATOR, '', $path);
		$gid = Path::encode($path) . '-' . $callinfo['line'] . '-' . $fn;


		$title = [];
		$i = 0;
		while (isset($args[$i]) && (is_string($args[$i]) || is_integer($args[$i]))) {
			$title[] = $args[$i];
			$i++;
		}
		$title = implode(' ', $title);
		if (!$title) $title = $hash;

		$id = md5(static::$type . '-' . $gid . '-' . $hash);
		return [$gid, $id, $title, $hash, $fn];
	}

	public static function setTitle($title)
	{
		Once::$item['title'] = $title;
	}

	
	public static $lastid = false;
	public static function end()
	{
		Once::$lastid = array_pop(Once::$parents);
		if (sizeof(Once::$parents)) {
			Once::$item = &Once::$items[Once::$parents[sizeof(Once::$parents) - 1]];
		} else {
			$r = null;
			Once::$item = &$r;
		}
	}

	final public static function omit($gtitle, $args = array(), $level = 0)
	{
		$level++;
		$item = &static::start($gtitle, $args, $cond, $condargs, $level);
		if (empty($item['exec']['start']) || Once::isChange($item)) {
			$item['exec']['start'] = true;
			return false;
		}

		static::end();
		return true;
	}
	
	/*public static function &amponce($fn, $args = array(), $cond = array(), $condargs = array(), $level = 0){
		$gtitle = false;
		$level++;
		return Once::ampexec($gtitle, $fn, $args, $cond, $condargs, $level);
	}*/
	/*public static function &ampexec($gtitle, $fn, $args = array(), $cond = array(), $condargs = array(), $level = 0){
		$level++;
		static::exec($gtitle, $fn, $args, $cond, $condargs, $level, $items);
		return $items[0]['exec']['result'];
	}*/
	public static function isChange(&$item) {
		if (!Once::isSave($item)) return false;
		$atime = Access::adminTime();
		$uptime = Once::getBooTime();
		if ($uptime > $atime) $atime = $uptime;
		if ($atime <= $item['exec']['time'] && !Access::isDebug()) return false;
		foreach ($item['exec']['conds'] as $cond) {
			$time = Once::getCondTime($cond);
			if ($time >= $item['exec']['time']) return true;
		}
		return false;
	}

	public static function &start($gtitle = false, $args = array(), $cond = array(), $condargs = array(), $level = 0)
	{
		$level++;
		$item = &static::createItem($gtitle, $args, $cond, $condargs, $level);		
		Once::$item['exec']['childs'][] = $item['id'];
		Once::$parents[] = $item['id'];
		Once::$item = &$item;
		return $item;
	}
	public static function clear($id) {
		unset(Once::$items[$id]['exec']['start']);
	}
	public static function &once($fn, $args = array(), $condfn = array(), $condargs = array(), $level = 0){
		$gtitle = false;
		$level++;
		return Once::exec($gtitle, $fn, $args, $condfn, $condargs, $level);
	}
	public static function &func($fn, $args = array(), $condfn = array(), $condargs = array(), $level = 0){
		$gtitle = false;
		$level++;
		return static::exec($gtitle, $fn, $args, $condfn, $condargs, $level);
	}
    public static function &exec($gtitle, $fn, $args = array(), $condfn = array(), $condargs = array(), $level = 0, &$origitem = array())
    {	
    	$level = $level++;
        $item = &static::start($gtitle, $args, $condfn, $condargs, $level);
        $origitem[0] = &$item;
        $execute = empty($item['exec']['start']) || Once::isChange($item);
        
        if ($execute) {
            $item['exec']['start'] = true;
            $item['exec']['notloaded'] = true;
            $t = microtime(true);
			$r = static::execfn($item, $fn);
			$t = microtime(true) - $t;
			$item['exec']['timer'] += $t;
			$item['exec']['end'] = true;
			if ($r) {
				if (Once::isSave($item)) Once::$proccess = true;
			}
        }
		static::end();

		$parents = array_reverse(Once::$parents); //От последнего вызова
		foreach ($parents as $pid) {
			foreach ($item['exec']['conds'] as $cond) {
				if (!in_array($cond, Once::$items[$pid]['exec']['conds'])) Once::$items[$pid]['exec']['conds'][] = $cond;
			}
			if (!isset(Once::$items[$pid]['exec']['end'])) break; //Дальше этот родитель передаст сам, когда завериштся
		}
		//if (Once::$proccess) { //Если вообще хоть кто-то выполнялся иначе был кэш с сохранёнными conds
			
		//}
		if ($execute) {
			Once::$item['exec']['timer'] -= $t;
		}
        return $item['exec']['result'];
    }
    /**
	* Имитирует выполнение $call в рамках указанного $item
	**/
	public static function resume(&$parents, $call) {
		$old = &Once::$item;
		$oldparents = Once::$parents;

		Once::$item = &Once::$items[$parents[sizeof($parents)-1]];
		Once::$parents = $parents;

		$t = microtime(true);
		//Мы не знаем какая часть его собственная, а какая относится к его детям
		//Надо что бы дети ничего не корректировали или считать детей
		//Дети могу вычитать, а тут всё прибавить
		$r = $call();
		$t = microtime(true) - $t;

		Once::$item['exec']['timer'] += $t;
	
		
		//Once::saveResult(Once::$item);
		Once::$item = &$old;
		Once::$parents = $oldparents;

		return $r;
	}
    public static function execfn(&$item, $fn)
    {
        $item['exec']['result'] = call_user_func_array($fn, $item['args']);
        return true;
    }
    public static function loadResult($item) {
		if ($item['cls'] == 'akiyatkin\\boo\\Once') return;
		return call_user_func_array($item['cls'].'::loadResult', array($item));
    }
	public static function removeResult($item) {
		if ($item['cls'] == 'akiyatkin\\boo\\Once') return;
		call_user_func_array($item['cls'].'::removeResult', array($item));
	}

    /**
     * Сохраняет результат в место постоянного хранения.
     * Используется в расширяемых классах.
     * В once сохранять ничего не надо.
     * Сохранение должно вызываться в execfn
     * @param $item
     */
    public static function isAdmin($item) {
		return $item['cls']::$admin;
    }
    public static function isSave($item) {
    	if ($item['cls'] == 'akiyatkin\\boo\\Once') return false;
    	return true;
    }
    public static function saveResult($item) {
    	if (!Once::isSave($item)) return;
		return call_user_func_array($item['cls'].'::saveResult', array($item));
    }
	public static function getItemsSrc() {
		return Once::$conf['cachedir'].'.items.json';
	}
    public static function initSave() {
    	
    	$src = Once::getItemsSrc();
		$items = FS::file_get_json($src);

		foreach (Once::$items as $id => $v) {	
			for ($k = 0; $k < sizeof($v['exec']['childs']); $k++ ) {
				$cid = $v['exec']['childs'][$k];
				if (!isset(Once::$items[$cid]) || Once::isAdmin(Once::$items[$cid])) continue;
				array_splice($v['exec']['childs'], $k, 1, Once::$items[$cid]['exec']['childs']);
				$k--;
			}
			$v['exec']['childs'] = array_values(array_unique($v['exec']['childs']));
			if (!Once::isSave($v)) continue;
			if (!empty($v['exec']['notloaded'])) { //Выполнено сейчас и не загрруженное не используется
				unset($v['exec']['notloaded']); //end появляется при выполнении. Сохраняем всегда без него
				Once::saveResult($v);
			}
			if (!Once::isAdmin($v)) continue;
			unset($v['exec']['result']); //Удалили результат, что бы файл был не таким большим, но статистку выполнения оставили ['exec']['time']
			$items[$id] = $v; //То что уже было записано нам пофиг. 
		}
		FS::file_put_json($src, $items);
        return $items;
    }
    public static function init () {
		Once::$item = &Once::createItem('Корень');
		Once::$parents[] = Once::$item['id'];
		Once::$item['exec']['start'] = true;
		Once::$item['exec']['timer'] = 0;
		$t = microtime(true);
		Once::$cwd = getcwd();
		register_shutdown_function( function () use ($t){
			Once::$item['exec']['end'] = true;
			Once::$item['exec']['timer'] += microtime(true) - $t;
		    chdir(Once::$cwd);
		    
		    if (Once::$proccess) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сохраняем
		        $error = error_get_last();
		        
		        //E_WARNING - неотправленное письмо mail при неправильно настроенном сервере
				if (is_null($error) || ($error['type'] != E_ERROR
		                && $error['type'] != E_PARSE
		                && $error['type'] != E_COMPILE_ERROR)) {
		            $items = Once::initSave();
		        }
		    }
		});
    }
}

Once::init();
