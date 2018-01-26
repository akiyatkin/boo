<?php

use infrajs\access\Access;
use infrajs\nostore\Nostore;
use akiyatkin\boo\Face;

if (isset($_GET['-boo'])) {
	Nostore::on();
	header('X-Robots-Tag: none');
	Access::test(true);
	$root = $_GET['-boo'];
	if ($root) { //Чтобы сбросить весь кэш - root
		Face::remove($root);
	}
}