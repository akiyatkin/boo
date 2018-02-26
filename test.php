<?php
namespace akiyatkin\boo;

use infrajs\access\Access;

Access::test(true);

function &test () {
	$r = &Cache::exec('Тест', function () {
		echo 1;
		usleep(0.5*1000000);
		return array('test');
	}, array(), ['akiyatkin\boo\Cache','getModifiedTime'], array());
	return $r;
}



$r = &test();
$r['wow'] = true;

$rr = &test();
	
print_r($rr);
exit;

/*Cache::exec('Проверка2', function() {
	usleep(0.5 * 1000000);
	test();
});
*/
/*Cache::exec('Проверка',function() {
	usleep(0.5*1000000);
	test();
	Cache::exec('ПроверкаS1',function() {
		usleep(0.5*1000000);
	}, array('ТестS1'));
});*/
/*
Cache::exec('Проверка', function() {
	usleep(0.5*1000000);
	Cache::exec(function() {
		usleep(0.5*1000000);
		Cache::exec(function() {
			usleep(0.5*1000000);
		}, array('ТестS1'));
	},array('ТестУслуги'));
	Cache::exec(function() {
		usleep(0.5*1000000);
		Cache::exec(function() {
			usleep(0.5*1000000);
		}, array('ТестS2'));
		Cache::exec(function() {
			usleep(0.5*1000000);
		}, array('ТестS3'));
	},array('ТестБлог'));
});*/

/*echo '<pre>';
echo '<hr>';
print_r(Once::$items);*/