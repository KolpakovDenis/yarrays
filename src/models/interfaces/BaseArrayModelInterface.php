<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 08.07.2018
 * Time: 17:53
 */

namespace Yoom\Arrays\Models\Interfaces;

/**
 * Interface BaseArrayModelInterface
 *
 * @package Yoom\Arrays\Models\Interfaces
 * @author Denis Kolpakov
 */
interface BaseArrayModelInterface
{
	const PROPERTY_TYPE             = 'type';
	const PROPERTY_CLASS            = 'class';
	const PROPERTY_INITIAL_FUNCTION = 'initial_function';
	const PROPERTY_REQUIRED         = 'required';
	const PROPERTY_LIMITS           = 'limits';
	const PROPERTY_DEFAULT          = 'default';

	const TYPE_INT     = 'int';
	const TYPE_NUMERIC = 'numeric';
	const TYPE_STRING  = 'string';
	const TYPE_BOOL    = 'bool';
	const TYPE_ARRAY   = 'array';
	const TYPE_OBJECT  = 'object';

	const LIMIT_MIN = 'min';
	const LIMIT_MAX = 'max';
}