<?php

class Literal extends Base
{
    protected $_preSeparator = '';
    protected $_postSeparator = '';
    protected $_allowedClasses = array(
    		'Comparison',
    		'Func',
    		'Andx',
    		'Orx',
    		'Field'
    );
}
