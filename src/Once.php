<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\nostore\Nostore;
use akiyatkin\fs\FS;

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

	public static function &createItem($args, $gtitle = null)
	{
		list($gid, $id, $title, $hash, $fn) = static::hash($args, 4);
		if (isset(Once::$items[$id])) return Once::$items[$id];
		$item = array();
		$item['type'] = static::$type;
		$item['cls'] = static::$type;
		$item['fn'] = $fn;
		$item['args'] = $args;
		$item['id'] = $id;
		$item['hash'] = $hash;
		$item['exec'] = array();
		$item['gid'] = $gid;
		if (!$gtitle) $gtitle = $fn;
		$item['gtitle'] = $gtitle;
		$item['src'] = static::getSrc();
		if (sizeof($args) == 0) {
			$item['title'] = $gtitle;
		} else {
			$item['title'] = $title;
		}

		$item['childs'] = array();
		static::checkResult($item);
		Once::$items[$id] = &$item;
		return $item;
	}
	public static function checkResult(&$item) {
		$data = static::loadResult($item);
		if (!$data) return;
		$item['exec'] = $data['exec'];
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
	public static function hash($args = array(), $level = 1)
	{
		$hash = Path::encode(json_encode($args,JSON_UNESCAPED_UNICODE));

		$callinfos = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 1);
		$callinfo = $callinfos[$level - 1];
		$fn = Path::encode($callinfos[$level]['function']);
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

	public static function &start($args, $gtitle = null)
	{
		$item = &static::createItem($args, $gtitle);
		if (!Once::$item) {
			Once::$childs[] = $item['id'];
		} else {
			Once::$item['childs'][] = $item['id'];
		}
		Once::$parents[] = $item['id'];
		Once::$item = &$item;
		return $item;
	}

	public static function end()
	{
		array_pop(Once::$parents);
		if (sizeof(Once::$parents)) {
			Once::$item = &Once::$items[Once::$parents[sizeof(Once::$parents) - 1]];
		} else {
			$r = null;
			Once::$item = &$r;
		}
	}

	final public static function omit($gtitle, $args = array())
	{
		$item = &static::start($args, $gtitle);
		if (!$item['exec']) {
			$item['exec']['ready'] = true;
			return false;
		}

		static::end();
		return true;
	}
    public static function exec($gtitle, $fn = array(), $args = array())
    {
    	if (!is_string($gtitle)) { //Перегрузка функции
    		$args = $fn;
    		$fn = $gtitle;
    		$gtitle = false;
		}
        $item = &static::start($args, $gtitle);
        if (!$item['exec']) {
            $item['exec']['result'] = null;
            $item['exec']['timer'] = microtime(true);
			$r = static::execfn($item, $fn);
			$item['exec']['timer'] = microtime(true) - $item['exec']['timer'];
			if ($r) {
				static::$proccess = true;
				static::saveResult($item);
			}
			static::end();
        } else {
			static::end();
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
			unset($v['exec']['result']);
			if (!isset($items[$id])) {
				$v['childs'] = array_values(array_unique($v['childs']));
			} else {
				$v['childs'] = array_values(array_unique(array_merge($v['childs'],$items[$id]['childs'])));
			}
			$items[$id] = $v;
		}
		FS::file_put_json($src, $items);

        return $items;
    }


    public static $re = false;
    public static function isre($id) {
        if (Once::$re === true) {
            return true;
        } else {
            if (!is_array(Once::$re)) {
                if (isset($_GET['-boo'])) Once::$re = explode(',',$_GET['-boo']);
                else return false;
            }
        }
        return in_array($id, Once::$re);
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