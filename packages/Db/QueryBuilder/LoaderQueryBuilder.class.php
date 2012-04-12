<?
class LoaderQueryBuilder extends Loader{
	protected function includes(){
		require_once ('Expr/Base.php');
		require_once ('Expr/Composite.php');
		require_once ('Expr/Andx.php');
		require_once ('Expr/Comparison.php');
		require_once ('Expr/From.php');
		require_once ('Expr/Func.php');
		require_once ('Expr/GroupBy.php');
		require_once ('Expr/Join.php');
		require_once ('Expr/Literal.php');
		require_once ('Expr/Math.php');
		require_once ('Expr/OrderBy.php');
		require_once ('Expr/Orx.php');
		require_once ('Expr/Select.php');
		require_once ('Expr/Field.php');
		require_once ('Expr/Expr.php');
		require_once ('QueryBuilder.class.php');
		require_once ('Filter.class.php');
	}

}
?>