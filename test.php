<?php
namespace akiyatkin\boo;

use infrajs\access\Access;
use infrajs\event\Event;
use infrajs\catalog\Catalog;

Access::test(true);


Catalog::cache( function () {
	echo 'asdf';

	$data = Catalog::init();	
});


//fire Запоминает Once::$item и временно восстанавливает его при последующих подписках
/*function test() {
	Cache::exec('test', function () {
		sleep(1);
	}, array(), ['akiyatkin\boo\Cache', 'getDurationTime'], array('last week'));
}
function done($a){
	Cache::exec('done', function () {
		test();
	}, array($a));
}

Cache::exec('Апельсин', function () {
	sleep(1);
	done(1);
	done(2);
	done(3);
	done(4);
});
test();
Cache::exec('Апельсин', function () {
	sleep(1);
	Event::fire('wow');
});

Event::handler('wow', function(){
	sleep(1);
	test();
});

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