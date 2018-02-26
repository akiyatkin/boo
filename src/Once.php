<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\nostore\Nostore;
use akiyatkin\fs\FS;
use infrajs\access\Access;

class Once
{
	public static $type = 'Once';
	public static $cwd = false;
	public static $parents = [];
	public static $childs = array();
	public static $item = false;
	public static $items = array();
	public static $conf = array(
		'cachedir' => '!boo/'
	);
	public static $proccess = false;
	public static $conds = array();
	public static function &getCond($cond, $condargs) {
		$r = null;
		if (!$cond) return $r;
		$hash = Path::encode(json_encode($cond,JSON_UNESCAPED_UNICODE).':'.json_encode($condargs,JSON_UNESCAPED_UNICODE));
		if (empty(Once::$conds[$hash])) Once::$conds[$hash] = array(
			'fn' => $cond,
			'hash' => $hash,
			'args' => $condargs
		);
		return Once::$conds[$hash];
	}
	public static function &createItem($gtitle = false, $args = array(), $cond = array(), $condargs = array(), $level = 0)
	{
		$level++;
		list($gid, $id, $title, $hash, $fn) = static::hash($args, $level);
		if (isset(Once::$items[$id])) return Once::$items[$id];
		$item = array();
		$item['type'] = static::$type;
		$item['cls'] = static::$type;
		$item['cond'] = $cond;
		$item['condargs'] = $condargs;
		$item['fn'] = $fn;
		
		$item['args'] = $args;
		$item['id'] = $id;
		$item['hash'] = $hash;
		
		$item['gid'] = $gid;
		if (!$gtitle) $gtitle = $fn;
		else $item['gid'] = Path::encode($gtitle.'-'.$item['gid']);
		$item['gtitle'] = $gtitle;
		$item['src'] = static::getSrc();
		if (sizeof($args) == 0) {
			$item['title'] = $gtitle;
		} else {
			$item['title'] = $title;
		}
		
		$item['childs'] = array();


		$data = static::loadResult($item);
		if (!$data) {
			Once::initExecConds($item);
		} else {
			$item['exec'] = $data['exec'];
			foreach($item['exec']['conds'] as $k=>$c) {
				$item['exec']['conds'][$k] = &Once::getCond($c['fn'], $c['args'], $c['hash']);
			}
		}

		Once::$items[$id] = &$item;
		return $item;
	}
	public static function initExecConds(&$item){
		if(empty($item['exec'])) $item['exec'] = array();
		$item['exec']['conds'] = array();
		$cond = &Once::getCond($item['cond'], $item['condargs']);
		if ($cond) $item['exec']['conds'][$cond['hash']] = &$cond;	
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

		//$fn = 'fn';
		//$gid = 'gid';


		$title = [];
		$i = 0;
		while (isset($args[$i]) && (is_string($args[$i]) || is_integer($args[$i]))) {
			$title[] = $args[$i];
			$i++;
		}
		$title = implode(' ', $title);
		if (!$title) $title = $hash;



		//$id = $gid.'-'.$hash;
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
		if (empty($item['exec']['ready']) || Once::isChange($item)) {
			$item['exec']['ready'] = true;
			return false;
		}

		static::end();
		return true;
	}
	public static function once($fn, $args = array(), $cond = array(), $condargs = array(), $level = 0){
		$gtitle = false;
		$level++;
		return Once::exec($gtitle, $fn, $args, $cond, $condargs, $level);
	}
	public static function &amponce($fn, $args = array(), $cond = array(), $condargs = array(), $level = 0){
		$gtitle = false;
		$level++;
		return Once::ampexec($gtitle, $fn, $args, $cond, $condargs, $level);
	}
	public static function &ampexec($gtitle, $fn, $args = array(), $cond = array(), $condargs = array(), $level = 0){
		$level++;
		static::exec($gtitle, $fn, $args, $cond, $condargs, $level, $items);
		return $items[0]['exec']['result'];
	}
	public static function isChange(&$item) {
		if ($item['type'] == 'Once') return false;
		$atime = Access::adminTime();
		if ($atime <= $item['exec']['time'] && !Access::isDebug()) return false;
		foreach($item['exec']['conds'] as &$cond) {
			if (!isset($cond['time'])) {
				$cond['time'] = call_user_func_array($cond['fn'], $cond['args']);
			}
			if ($cond['time'] > $item['exec']['time']) return true;
		}
		return false;
	}
	public static function &start($gtitle = false, $args = array(), $cond = array(), $condargs = array(), $level = 0)
	{
		$level++;
		$item = &static::createItem($gtitle, $args, $cond, $condargs, $level);
		
		if (Once::$item) {
			Once::$item['childs'][] = $item['id'];
		} else {
			Once::$childs[] = $item['id'];
		}

		

		Once::$parents[] = $item['id'];
		Once::$item = &$item;
		return $item;
	}
	public static function debug(){
		echo 'Cache::debug'.'<br>'."\n";
		echo 'path: '.implode(', ',Once::$parents).'<br>'."\n";
		echo 'childs: '.implode(', ',Once::$item['childs']).'<br>'."\n";
	}
	public static function lastId () {
		echo Once::$lastid;
	}
    public static function &exec($gtitle, $fn, $args = array(), $cond = array(), $condargs = array(), $level = 0, &$origitem = array())
    {	
    	$level = $level++;
        $item = &static::start($gtitle, $args, $cond, $condargs, $level);
        $origitem[0] = &$item;
        $execute = empty($item['exec']['ready']) || Once::isChange($item);
        if ($execute) {
            $item['exec']['ready'] = true;
            Once::initExecConds($item);

            $item['exec']['result'] = null;
            $item['exec']['timer'] = microtime(true);
			$r = static::execfn($item, $fn);
			$item['exec']['timer'] = microtime(true) - $item['exec']['timer'];
			if ($r) {
				static::$proccess = true;
				static::saveResult($item);
			}
        }
		static::end();

		if (Once::$item) {
			//Кэш не выполнялся из-за старого условия. После изменения условия он всё равно не выполяется. Должен меняться id.
			foreach ($item['exec']['conds'] as $h => $conds) {
				Once::$item['exec']['conds'][$h] = &$item['exec']['conds'][$h];
			}
		}

		if (!$execute) {
			if (Once::$item) Once::$item['exec']['timer'] -= $item['exec']['timer'];
		}

        return $item['exec']['result'];
    }
    public static function execfn(&$item, $fn)
    {
        $item['exec']['result'] = call_user_func_array($fn, $item['args']);
        return true;
    }
    public static function loadResult($item) {
		if (empty($item['type'])) return;
		if ($item['type'] == 'Once') return;
		return call_user_func_array('akiyatkin\\boo\\'.$item['type'].'::loadResult', array($item));
    }
	public static function removeResult($item) {
		if (empty($item['type'])) return;
		if ($item['type'] == 'Once') return;
		call_user_func_array('akiyatkin\\boo\\'.$item['type'].'::removeResult', array($item));
	}

    /**
     * Сохраняет результат в место постоянного хранения.
     * Используется в расширяемых классах.
     * В once сохранять ничего не надо.
     * Сохранение должно вызываться в execfn
     * @param $item
     */
    public static function saveResult($item) {

    }
	public static function getTreeSrc() {
		return Once::$conf['cachedir'].'.tree.json';
	}

    public static function initSave() {
    	$src = Once::getTreeSrc();
		$items = FS::file_get_json($src);

		foreach (Once::$items as $id => $v) {
			if ($v['type'] == 'Once') continue;
			foreach($v['childs'] as $k=>$cid) {
				if (Once::$items[$cid]['type'] != 'Once') continue;
				unset($v['childs'][$k]);
			}
			unset($v['exec']['result']); //Удалили результат, что бы файл был не таким большим, но статистку выполнения оставили ['exec']['time']
			
			$v['childs'] = array_values(array_unique($v['childs']));
			$items[$id] = $v;
		}
		FS::file_put_json($src, $items);

        return $items;
    }
}

Once::$cwd = getcwd();
register_shutdown_function(function(){
    chdir(Once::$cwd);
    if (Once::$proccess) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сравниваем
        $items = Once::initSave();
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