<?php
namespace akiyatkin\boo;

use infrajs\rest\Rest;
use infrajs\ans\Ans;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\access\Access;
use infrajs\config\Config;
use akiyatkin\fs\FS;

Access::debug(true);
Config::get('timezone');

return Rest::get( function () {
		Face::index();
	}, 'check',function() {
		$src = Once::srcTree();
		$items = FS::file_get_json($src);

		$items = array_values($items);
		foreach($items as $item) {
			if (isset($item['fncheck'])) {

			} else {
				Once::removeResult($item);
			}
		}

		echo '<pre>';
		print_r($items);

	}, function($root, $action = '', $deep = false) {
		if ($action == 'refresh') {
			Face::refresh($root, $deep);
		}
		if ($action == 'remove') {
			Face::remove($root, $deep);
		}

		Face::index($root, $action);
	}
);


