<?php
namespace akiyatkin\boo;

class Hierarchy
{
    public static $parents = [];
    public static $root = null;
    public static $item = null;
    public static function &createItem($gid = null, $gtitle = null, $id = null, $title = null) {
        if (isset(Cache::$items[$id])) return Cache::$items[$id];
        $item = array();
        $item['id'] = $id;
        $item['group'] = array(
            'id' => $gid,
            'title' => $gtitle
        );

        //$item['parents'] = array();

        $item['title'] = $title;
        $item['childs'] = array();
        Cache::$items[$id] = &$item;
        return $item;
    }
    public static function hash($args = array()) {
        $hash = md5(json_encode($args),true);
        $callinfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $name = $callinfo['file'].':'.$callinfo['line'];
        $title = [];
        $i = 0;
        while (isset($args[$i]) && (is_string($args[$i]) || is_integer($args[$i]))) {
            $title[] = $args[$i];
            $i++;
        }
        $title = implode(' ', $title);
        if (!$title) $title = $hash;
        $id = $gid.'-'.$hash;
        return [$id, $gid, $hash, $title];
    }
}