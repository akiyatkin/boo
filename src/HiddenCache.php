<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\sequence\Sequence;
use League\Flysystem\Adapter\Local;
use infrajs\mem\Mem;

//HiddenCache обновляется при любом ?-boo
class HiddenCache extends MemCache
{
    public static $type = 'HiddenCache';
    public static $admin = false;
    public static function prepareItem(&$item) {
    	$cond = Once::createCond(['akiyatkin\\boo\\Once','getBooTime']);
    	$item['exec']['conds'][] = $cond['hash'];
	}
}