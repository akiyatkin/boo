<?php
namespace akiyatkin\boo;

use infrajs\access\Access;

Access::test(true);

function test(){
	Cache::exec('Тест', function() {
		usleep(0.5*1000000);
	});
}

Cache::exec('Проверка1', function() {
	usleep(0.5 * 1000000);
	test();
});
Cache::exec('Проверка2', function() {
	usleep(0.5 * 1000000);
	test();
});
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

echo '<pre>';
echo '<hr>';
print_r(Once::$items);