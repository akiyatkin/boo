<?php

use infrajs\access\Access;
use infrajs\nostore\Nostore;
use akiyatkin\boo\Face;
use akiyatkin\boo\Cache;
use akiyatkin\boo\Once;

if (isset($_GET['-boo'])) {
	Nostore::on();
	header('X-Robots-Tag: none');
	Access::test(true);
	$root = $_GET['-boo'];
	Once::$re = true;
	if ($root) { //Чтобы сбросить весь кэш - root
		Face::remove($root);
	}
}