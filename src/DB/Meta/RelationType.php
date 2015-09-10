<?php
namespace Lite\DB\Meta;

/**
 * Created by PhpStorm.
 * User: sasumi
 * Date: 2015/9/10
 * Time: 22:27
 */

abstract class RelationType {
	const HAS_ONE = 1;
	const HAS_MANY = 2;
	const BELONGS_TO = 3;
	const MANY_TO_MANY = 4;
}