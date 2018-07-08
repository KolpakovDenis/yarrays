<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 08.07.2018
 * Time: 17:53
 */

namespace Yoom\Arrays\Models;

use Yoom\Arrays\Models\Exceptions\BaseArrayModelException;
use Yoom\Arrays\Models\Exceptions\InvalidTypeException;
use Yoom\Arrays\Models\Exceptions\RequiredFieldMissedException;
use Yoom\Arrays\Models\Exceptions\UnsupportedFieldException;
use Yoom\Arrays\Models\Interfaces\BaseArrayModelInterface;
use Yoom\Interfaces\ToArray;
use Yoom\Interfaces\ToJson;

/**
 * Class BaseArrayModel
 *
 * @package Yoom\Arrays\Models
 * @author Denis Kolpakov
 */
abstract class BaseArrayModel implements BaseArrayModelInterface, ToArray, ToJson
{
	private const REQUIRED_PROPS = [
		self::PROPERTY_TYPE,
		self::PROPERTY_REQUIRED,
	];

	private const ONLY_OBJECT_PROPS = [
		self::PROPERTY_CLASS,
		self::PROPERTY_INITIAL_FUNCTION,
	];

	private const TYPES_WITH_LIMIT = [
		self::TYPE_INT,
		self::TYPE_NUMERIC,
	];

	protected $initFromJson = true;

	/**
	 * @var array
	 */
	protected $_storage = [];

	protected $_fields = [
		/**
		 * Example:
		 * 'id'       => [
		 *        self::PROPERTY_TYPE     => self::TYPE_INT,
		 *        self::PROPERTY_REQUIRED => true,
		 *        self::PROPERTY_LIMITS   => [
		 *            self::LIMIT_MIN => 0,
		 *            self::LIMIT_MAX => 1000,
		 *        ],
		 * ],
		 * 'settings' => [
		 *        self::PROPERTY_TYPE             => self::TYPE_OBJECT,
		 *        self::PROPERTY_CLASS            => 'Yoom\\User\\Settings',
		 *        self::PROPERTY_INITIAL_FUNCTION => ['Yoom\\User\\SettingsFactory', 'getFromJson'],
		 *        self::PROPERTY_REQUIRED         => true,
		 * ],
		 */
	];

	/**
	 * BaseArrayModel constructor.
	 *
	 * @param mixed|array $data
	 *
	 * @throws InvalidTypeException
	 * @throws RequiredFieldMissedException
	 * @throws UnsupportedFieldException
	 * @throws BaseArrayModelException
	 */
	public function __construct($data = [])
	{
		$this->checkFieldsDeclaration();

		if (is_string($data) && $this->initFromJson) {
			$data = json_decode($data, true);
		}

		if (!is_array($data)) {
			throw new InvalidTypeException("$data must be an array");
		}

		foreach ($this->_fields as $key => $props) {
			if (array_key_exists($key, $data)) {
				$this->_setFieldData($key, $data[$key]);
			}
		}

		$this->checkRequiredFields();
	}

	/**
	 * @param string $key
	 * @param $value
	 *
	 * @throws BaseArrayModelException
	 * @throws RequiredFieldMissedException
	 * @throws UnsupportedFieldException
	 */
	protected function setFieldData(string $key, $value): void {
		$this->_setFieldData($key, $value);
		$this->checkRequiredFields();
	}

	/**
	 * @description Проверка правильности заполнения описания полей модели
	 *
	 * @return void
	 *
	 * @throws BaseArrayModelException
	 */
	protected function checkFieldsDeclaration(): void
	{
		foreach ($this->_fields as $key => $props) {
			foreach (self::REQUIRED_PROPS as $propertyKey) {
				if (!array_key_exists($propertyKey, $props)) {
					throw new BaseArrayModelException("Empty required property {$propertyKey} in {$key}");
				}
			}

			if ($props[self::PROPERTY_TYPE] === self::TYPE_OBJECT) {
				if (empty($props[self::PROPERTY_CLASS])) {
					throw new BaseArrayModelException("Object must have class property. Check props of {$key}");
				}

				if (!class_exists($props[self::PROPERTY_CLASS])) {
					throw new BaseArrayModelException(
						"Class " . $props[self::PROPERTY_CLASS] . " does not exist. Check props of {$key}"
					);

				}

				if (
					!empty($props[self::PROPERTY_INITIAL_FUNCTION]) &&
					!is_callable($props[self::PROPERTY_INITIAL_FUNCTION])
				) {
					throw new BaseArrayModelException("Initial function must be callable. Check props of {$key}");
				}

			} else {
				foreach (self::ONLY_OBJECT_PROPS as $propertyKey) {
					if (array_key_exists($propertyKey, $props)) {
						throw new BaseArrayModelException(
							"Only object has {$propertyKey} property. Check props of {$key}"
						);
					}
				}
			}

			if (
				!empty($props[self::PROPERTY_LIMITS]) &&
				!in_array($props[self::PROPERTY_TYPE], self::TYPES_WITH_LIMIT)
			) {
				throw new BaseArrayModelException(
					"Limits is not available for " . $props[self::PROPERTY_TYPE] . "Check props of {$key}"
				);
			}
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 *
	 * @throws BaseArrayModelException
	 * @throws UnsupportedFieldException
	 */
	private function _setFieldData(string $key, $value): void
	{
		if (empty($this->_fields[$key])) {
			throw (new UnsupportedFieldException())->setField($key);
		}

		if (empty($this->_fields[$key])) {
			throw new BaseArrayModelException("Field {$key} not found");
		}

		$props = $this->_fields[$key];
		$type = $props[self::PROPERTY_TYPE];

		if (!$this->isFieldNotEmpty($value, $type)) {
			$value = $this->initDefaultValue($key);
		}

		if ($this->isFieldNotEmpty($value, $key)) {
			$value = $this->checkTypeAndConvert($key, $value);
			$this->_storage[$key] = $value;
		}
	}

	/**
	 * @param mixed $value
	 * @param string|null $type
	 *
	 * @return bool
	 */
	protected function isFieldNotEmpty($value, $type = null): bool
	{
		$result = false;

		if ($type === self::TYPE_INT || self::TYPE_NUMERIC) {
			$result = $value === 0;
		} elseif ($type === self::TYPE_BOOL) {
			$result = $value === false;
		}

		return $result || !empty($value);
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 * @throws UnsupportedFieldException
	 */
	protected function initDefaultValue(string $key)
	{
		if (empty($this->_fields[$key])) {
			throw (new UnsupportedFieldException())->setField($key);
		}

		$result = null;

		if (array_key_exists(self::PROPERTY_DEFAULT, $this->_fields[$key])) {
			$value = $this->_fields[$key][self::PROPERTY_DEFAULT];
			$type = $this->_fields[$key][self::PROPERTY_TYPE];

			if ($this->isFieldNotEmpty($value, $type)) {
				$result = $value;
			}
		}

		return $result;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return mixed
	 * @throws InvalidTypeException
	 * @throws UnsupportedFieldException
	 */
	protected function checkTypeAndConvert(string $key, $value)
	{
		if (empty($this->_fields[$key])) {
			throw (new UnsupportedFieldException())->setField($key);
		}

		$type = $this->_fields[$key][self::PROPERTY_TYPE];

		switch ($type) {
			case self::TYPE_INT:
				$result = (int)$value;
				$isValid = filter_var($value, FILTER_VALIDATE_INT);
				break;
			case self::TYPE_NUMERIC:
				$result = (float)$value;
				$isValid = is_numeric($value);
				break;
			case self::TYPE_STRING:
				$result = $value;
				$isValid = is_string($value);
				break;
			case self::TYPE_BOOL:
				$result = (bool)$value;
				$isValid = $value === true || $value === false;
				break;
			case self::TYPE_ARRAY:
				$result = $value;
				$isValid = is_array($value);
				break;
			case self::TYPE_OBJECT:
				$result = $value;
				$isValid = $value instanceof $this->_fields[$key][self::PROPERTY_CLASS];
				if (!$isValid && !empty($this->_fields[$key][self::PROPERTY_INITIAL_FUNCTION])) {
					$result = call_user_func($this->_fields[$key][self::PROPERTY_INITIAL_FUNCTION], $value);

					$isValid = $result instanceof $this->_fields[$key][self::PROPERTY_CLASS];
				}
				break;
			default:
				throw (new InvalidTypeException("Unsupported type {$type}"))
					->setField($key);
				break;
		}

		if (!$isValid) {
			throw (new InvalidTypeException("Wrong type for {$key}. {$value} has been given"))
				->setField($key);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->_storage as $key => $value) {
			$result[$key] = $value instanceof ToArray ? $value->toArray() : $value;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function toJson(): string
	{
		$result = $this->toArray();

		return json_encode($result);
	}

	/**
	 * @throws RequiredFieldMissedException
	 */
	protected function checkRequiredFields()
	{
		foreach ($this->_fields as $key => $props) {
			if ($props[self::PROPERTY_REQUIRED] === true) {
				$value = array_key_exists($key, $this->_storage) ? $this->_storage[$key] : null;
				$type = $props[self::PROPERTY_TYPE];
				if (!$this->isFieldNotEmpty($value, $type)) {
					throw (new RequiredFieldMissedException())->setField($key);
				}
			}
		}
	}
}