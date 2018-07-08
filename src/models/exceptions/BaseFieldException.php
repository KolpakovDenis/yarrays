<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 08.07.2018
 * Time: 18:17
 */

namespace Yoom\Arrays\Models\Exceptions;


/**
 * Class BaseArrayModelException
 *
 * @package Yoom\Arrays\Models\Exceptions
 * @author Denis Kolpakov
 */
abstract class BaseFieldException extends BaseArrayModelException
{
	protected $_field = '';

	/**
	 * @return string
	 */
	public function getField(): string
	{
		return $this->_field;
	}

	/**
	 * @param string $field
	 *
	 * @return static
	 */
	public function setField(string $field): self
	{
		$this->_field = $field;

		return $this;
	}
}