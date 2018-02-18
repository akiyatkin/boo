<?php

use infrajs\access\Access;
use infrajs\nostore\Nostore;
use akiyatkin\boo\Face;
use akiyatkin\boo\Cache;
/*
if (isset($_GET['-boo'])) {
	Nostore::on();
	header('X-Robots-Tag: none');
	Access::test(true);
	$root = $_GET['-boo'];
	if ($root) { //Чтобы сбросить весь кэш - root
		Face::remove($root);
	}
}
Cache::$cwd = getcwd();
register_shutdown_function(function(){
    chdir(Cache::$cwd);
    if (Cache::$proccess) { //В обычном режиме кэш не создаётся а только используется, вот если было создание тогда сравниваем
        $items = Cache::initSave();

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
*/