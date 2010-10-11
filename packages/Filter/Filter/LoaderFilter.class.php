<?
class LoaderFilter extends Loader{
	protected function includes(){
		require_once ('Filter.class.php');
		require_once ('Filterable.class.php');
	}
}
?>