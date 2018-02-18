<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;

class FS
{
    public static function is_dir($dir) {
        $dir = Path::toutf($dir);
        return is_dir($dir);
    }
    public static function is_file($dir) {
        $dir = Path::toutf($dir);
        return is_file($dir);
    }
    public static function filemtime($file) {
        $file = Path::resolve($file);
        return filemtime($file);
    }
    public static function scandir($dir, $call)
    {
        $dir = Path::theme($dir);
        if (!$dir) return array();
        $files = scandir($dir);
        $list = array();
        foreach ($files as $file) {
            if ($file[0]=='.') continue;
            $file = Path::toutf($file);
            $r = $call($file, $dir);
            if (!is_null($r) && !$r) continue;
            $list[] = $file;
        }
        return $list;
    }
    public static function file_put_json($file, $data) {
        $data = Load::json_encode($data);
        $file = Path::resolve($file);
        return file_put_contents($file, $data);
    }
    public static function file_get_json($file) {
        $src = Path::resolve($file);
        if (!is_file($src)) return array();
        //$data = Load::loadJSON($file); Нельзя использовать после вывода контента из-за проверки заголовков
        $data = file_get_contents($src);
        $data = Load::json_decode($data);
        return $data;
    }
    public static function filesize($file) {
        $file = Path::resolve($file);
        if (!$file) return 0;
        if (!is_file($file)) return 0;
        return filesize($file);
    }
    public static function unlink($file) {
        $file = Path::resolve($file);
        if (!$file) return true;
        if (!is_file($file)) return true;
        return unlink($file);
    }
}