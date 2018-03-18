<?php
namespace akiyatkin\boo;

use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\catalog\Catalog;
use infrajs\once\Once;
use infrajs\excel\Xlsx;

Access::test(true);

function Repeat(&$counter) {
	return Once::func( function () use (&$counter) {
		$counter++;
		return 1;
	},[],['akiyatkin\\boo\\Cache','getBooTime']);
}

$counter = 0;
Repeat($counter);
$r = Repeat($counter);
assert($r === 1);
assert($counter === 1);


function badaboom(&$counter) {
	return BooCache::func( function () use (&$counter) {
		sleep(1);
		Repeat($counter);
		return 1;
	});
}

$r = badaboom($counter);

assert($r === 1);
assert($counter === 1);
assert(sizeof(Once::$items[Once::$lastid]['conds']) === 1);
assert(Once::$items[Once::$lastid]['timer'] >= 1);



//echo '<Pre>';
//print_r(Once::$items[Once::$lastid]);
echo '{ "result": 1 "msg":"Прохождение со второго выполнения"}';
/*
function boo(){
	return BooCache::func( function () {
		getPoss();
	});
}

function search() {
	return Cache::func( function () {
		boo();
	});
}

$t = microtime(true);
search();
echo (microtime(true) - $t).'<br>';
/*==
	--
		**
	--
	**
		&&
			%%
		&&	
		%%
			?
		%%
	**
==

/*Cache::exec('Тест 2', function () {
	sleep(1);
	Event::fire('event');
});

Event::handler('event', function(){
	sleep(1);
	test();
});*/



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