<?php
class LoaderMysqlPager extends Loader{
	protected function includes(){
		require_once ('Objects/MysqlPager.class.php');
	}
}
