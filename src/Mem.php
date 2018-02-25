<?php
namespace akiyatkin\boo;
use infrajs\path\Path;
use infrajs\load\Load;
use infrajs\nostore\Nostore;
use infrajs\each\Each;
use infrajs\hash\Hash;
use infrajs\sequence\Sequence;
use League\Flysystem\Adapter\Local;
use infrajs\mem\Mem as M;

class Mem extends Cache
{
    public static $type = 'Mem';
    public static function saveResult($item) {
        $strdata = json_encode($item);
		M::set('boo-' . $item['id'], $strdata);
    }
	public static function removeResult($item){
		M::delete('boo-' . $item['id']);
	}
    public static function loadResult($item) {
		$strdata = M::get('boo-' . $item['id']);
		$data = json_decode($strdata,true);
		return $data;
    }
}