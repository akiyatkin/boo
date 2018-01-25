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
	}, 'test', function(){
		
		
			

		Boo::cache('Тест', function() {
			usleep(0.5*1000000);
			Boo::cache('ТестДокументы', function() {
				usleep(0.5*1000000);
			}, array('ТестS1'));
			Boo::cache('ТестДокументы', function() {
				usleep(0.5*1000000);
			}, array('ТестS2'));
		});

		Boo::cache('ТестСвязи', function() {
			usleep(0.5*1000000);
			Boo::cache('ТестПапки', function() {
				usleep(0.5*1000000);
				Boo::cache('ТестДокументы', function() {
					usleep(0.5*1000000);
				}, array('ТестS1'));
			},array('ТестУслуги'));
			Boo::cache('ТестПапки', function() {
				usleep(0.5*1000000);
				Boo::cache('ТестДокументы', function() {
					usleep(0.5*1000000);
				}, array('ТестS2'));
				Boo::cache('ТестДокументы', function() {
					usleep(0.5*1000000);
				}, array('ТестS3'));
			},array('ТестБлог'));
		});
		echo 'Создана группа кэша <a href="/-boo/Тест">Тест</a>';
	}, function($root, $action = '') {
		$msg = '';
		if ($action == 'refresh') {
			//$msg = 'Кэш '.$root.' обновлён';
			Face::refresh($root);
		}
		if ($action == 'remove') {
			Face::remove($root);
		}
		return Face::list($root, $action);
	}
);


