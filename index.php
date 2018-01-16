<?php
use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\config\Config;

Access::debug(true);
Config::get('timezone');

return Rest::get( function () {
		return Face::list();
	}, 'test2', function(){
		Boo::start(["boo-test","Test"]);
			Boo::start('wow');
				Boo::start('one');
				Boo::end();
			Boo::end();
			Boo::start('Folder4');
				Boo::start('one');
					Boo::cache('Cache2', function() {
						Boo::cache('Subcache2', function() {
							return 'Проверка Subcache';
						});
						return 'Проверка Cache';
					});
				Boo::end();
			Boo::end();
		Boo::end();
		Boo::start(["boo-test","Test"]);
			Boo::start('Folder3');
				Boo::start('one');
					Boo::cache('Cache', function() {
						Boo::cache('Subcache', function() {
							return 'Проверка Subcache';
						});
						return 'Проверка Cache';
					});
				Boo::end();
			Boo::end();
		Boo::end();
		return Face::list('root','Создана группа кэша <a href="/-boo/boo-test">Test</a>');

		
	//}, 'form', function () {
		//$html = Load::loadTEXT('-boo/index.php');
		//var_dump($html);
		//exit;
		//return Face::form();
	}, 'test', function(){
		
		Boo::start(["boo-test","Test"]);
			Boo::start('Folder1');
				Boo::cache('Cache', function() {
					Boo::cache('Subcache', function() {
						return 'Проверка Subcache';
					});
					return 'Проверка Cache';
				});
			Boo::end();


			Boo::start('Folder2');
				Boo::cache('Cache', function() {
					Boo::cache('Subcache', function() {
						return 'Проверка Subcache';
					});
					return 'Проверка Cache';
				});
			Boo::end();
		Boo::end();

		return Face::list('root','Создана группа кэша <a href="/-boo/boo-test">Test</a>');
	}, function($root, $action = '', $option = 'one') {
		$msg = '';
		if ($action == 'refresh') {
			$msg = 'Кэш '.$root.' обновлён';
			Face::refresh($root, $option);
		}
		if ($action == 'remove') {
			Face::remove($root, $option);
		}
		return Face::list($root, $msg);
	}
);


