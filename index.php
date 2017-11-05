<?php
use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\access\Access;
use infrajs\config\Config;

Access::debug(true);
Config::get('timezone');

$msg = Rest::get( function () {
		
	}, 'refresh', [ function () {
			Boo::refresh();	
			return 'Весь кэш обновлён';
		}, function ($type, $name){
			Boo::refresh($name);
			return 'Кэш обновлён';
	}], 'remove', [ function () {
			Boo::remove();
			return 'Весь кэш удалён';
		}, function ($type, $name) {
			Boo::remove($name);
			return 'Кэш удалён';
	} ], 'test', function(){
		Boo::cache("test", function () {
			return 'Проверка';
		});
	}
);

return Face::list($msg);
