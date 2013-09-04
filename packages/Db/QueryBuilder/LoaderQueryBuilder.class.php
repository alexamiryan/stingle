<?php
class LoaderQueryBuilder extends Loader{
	protected function includes(){
		stingleInclude ('Objects/QBPart.class.php');
		stingleInclude ('Objects/Expr/Base.php');
		stingleInclude ('Objects/Expr/Composite.php');
		stingleInclude ('Objects/Expr/Andx.php');
		stingleInclude ('Objects/Expr/Comparison.php');
		stingleInclude ('Objects/Expr/From.php');
		stingleInclude ('Objects/Expr/Func.php');
		stingleInclude ('Objects/Expr/GroupBy.php');
		stingleInclude ('Objects/Expr/Join.php');
		stingleInclude ('Objects/Expr/Literal.php');
		stingleInclude ('Objects/Expr/Math.php');
		stingleInclude ('Objects/Expr/OrderBy.php');
		stingleInclude ('Objects/Expr/Orx.php');
		stingleInclude ('Objects/Expr/Select.php');
		stingleInclude ('Objects/Expr/Field.php');
		stingleInclude ('Objects/Expr/Insert.php');
		stingleInclude ('Objects/Expr/Expr.php');
		stingleInclude ('Objects/Expr/Unionx.php');
		stingleInclude ('Managers/QueryBuilder.class.php');
		stingleInclude ('Managers/Filter.class.php');
		stingleInclude ('Managers/FilterMerger.class.php');
		stingleInclude ('Objects/MergeableFilter.class.php');
	}

}
