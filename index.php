<?php
use infrajs\rest\Rest;
use akiyatkin\boo\Boo;
use akiyatkin\boo\Face;
use infrajs\ans\Ans;
use infrajs\access\Access;

Access::test(true);

Rest::get( function () {
		
	}, 'refresh', [ function () {
			Boo::refresh();	
		}, function ($type, $name){
			Boo::refresh($name);
	}], 'remove', [ function () {
			Boo::remove();
		}, function ($type, $name) {
			Boo::remove($name);
	} ], 'test', function(){
		Boo::cache("test", function () {
			return 'Проверка';
		});
	}
);

return Face::list();
