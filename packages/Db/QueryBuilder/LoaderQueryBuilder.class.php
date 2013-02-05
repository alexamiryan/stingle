<?php
class LoaderQueryBuilder extends Loader{
	protected function includes(){
		require_once ('Objects/QBPart.class.php');
		require_once ('Objects/Expr/Base.php');
		require_once ('Objects/Expr/Composite.php');
		require_once ('Objects/Expr/Andx.php');
		require_once ('Objects/Expr/Comparison.php');
		require_once ('Objects/Expr/From.php');
		require_once ('Objects/Expr/Func.php');
		require_once ('Objects/Expr/GroupBy.php');
		require_once ('Objects/Expr/Join.php');
		require_once ('Objects/Expr/Literal.php');
		require_once ('Objects/Expr/Math.php');
		require_once ('Objects/Expr/OrderBy.php');
		require_once ('Objects/Expr/Orx.php');
		require_once ('Objects/Expr/Select.php');
		require_once ('Objects/Expr/Field.php');
		require_once ('Objects/Expr/Insert.php');
		require_once ('Objects/Expr/Expr.php');
		require_once ('Objects/Expr/Unionx.php');
		require_once ('Managers/QueryBuilder.class.php');
		require_once ('Managers/Filter.class.php');
		require_once ('Managers/FilterMerger.class.php');
		require_once ('Objects/MergeableFilter.class.php');
	}

}
