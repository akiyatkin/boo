<?php

use infrajs\access\Access;
use infrajs\nostore\Nostore;

if (isset($_GET['-boo'])) {
	Nostore::on();
	header('X-Robots-Tag: none');
	Access::test(true);
}