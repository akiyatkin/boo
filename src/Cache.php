<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\access\Access;
use akiyatkin\fs\FS;
use infrajs\sequence\Sequence;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;


class Cache extends Once
{
    public static $type = 'Cache';
    public static $admin = true;
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
    public static function getAccessTime() {
        return Access::adminTime();
    }
    public static function getModifiedTime($conds = array()) {
        if (!sizeof($conds)) return 0;//Если нет conds кэш навсегда и develop не поможет
        $time = 0;
        for ($i = 0, $l = sizeof($conds); $i < $l; $i++) {
            $mark = $conds[$i];
            $mark = Path::theme($mark);
            if (!$mark) continue;
            $m = filemtime($mark);
            if ($m > $time) {
                $time = $m;
            }

            if (!is_dir($mark)) {
                continue;
            }
            foreach (glob($mark.'*.*') as $filename) {
                $m = filemtime($filename);
                if ($m > $time) {
                    $time = $m;
                }
            }
        }
        return $time;
    }
    public static function execfn(&$item, $fn)
    {
		$item['exec']['time'] = time();

        $item['exec']['nostore'] = Nostore::check(function () use (&$item, $fn) { //Проверка был ли запрет кэша
            $item['exec']['result'] = call_user_func_array($fn, $item['args']);
        });
        return !$item['exec']['nostore'];
    }
}


