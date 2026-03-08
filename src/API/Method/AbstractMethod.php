<?php

namespace API\Method;

use Exception;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

abstract class AbstractMethod {

    private bool $_prepared = false;
    private bool $_isCalled = false;

    private ?ReflectionClass $_reflect = null;
    private ?array $_reflectProperties = null;

    private function getReflect(): ReflectionClass {
        if (!$this->_reflect) {
            $this->_reflect = new ReflectionClass($this);
        }

        return $this->_reflect;
    }

    /**
     * @return ReflectionProperty[]
     */
    private function getReflectProperties(): array {
        if ($this->_reflectProperties === null) {
            $this->_reflectProperties = $this->getReflect()->getProperties(ReflectionProperty::IS_PUBLIC);
        }

        return $this->_reflectProperties;
    }

    public function setFrom(AbstractMethod $method): void {
        if ($this->_prepared) {
            throw new Exception('Method params already prepared, setFrom() can use just once');
        }

        foreach ($this->getReflectProperties() as $property) {
            $name = $property->name;

            if (isset($method->$name) || array_key_exists($name, get_object_vars($method))) {
                $this->$name = $method->$name;
                continue;
            }

            $this->$name = $this->getDefault($property);
        }

        $this->_prepared = true;
    }

    public function setRawData(array $rowData): void {
        if ($this->_prepared) {
            throw new Exception('Method params already prepared, setRawData() can use just once');
        }

        $this->setFromRawData($rowData);
    }

    private function setFromRawData(array $rowData): void {
        foreach ($this->getReflectProperties() as $property) {
            $name = $property->name;
            $hasKey = array_key_exists($name, $rowData);
            $val = $hasKey ? $rowData[$name] : null;

            $this->setFromRawValue($name, $val, $property, $hasKey);
        }

        $this->_prepared = true;
    }

    private function setFromRawValue(string $name, mixed $val, ReflectionProperty $property, bool $hasKey): void {
        if (!$hasKey) {
            $defaultValue = $this->getDefault($property);
            if ($property->hasDefaultValue() || $defaultValue !== null) {
                $this->$name = $defaultValue;
                return;
            }

            $type = $property->getType();
            if ($type && $type->allowsNull()) {
                $this->$name = null;
                return;
            }

            throw new Exception("Missing param: $name");
        }

        $this->$name = $this->prepareRawValue($name, $val, $property);
    }

    private function prepareRawValue(string $name, mixed $val, ReflectionProperty $property): mixed {
        $type = $property->getType();
        if (!$type) {
            return $val;
        }

        if ($type instanceof ReflectionUnionType) {
            throw new Exception("Union types not supported for '$name'");
        }

        $typeName = $type->getName();

        switch ($typeName) {
            case 'string':
                if (!is_string($val)) throw new Exception("Invalid type for '$name'");
                return $val;
            case 'int':
                if (!is_numeric($val)) throw new Exception("Invalid type for '$name'");
                return (int) $val;
            case 'float':
                if (!is_numeric($val)) throw new Exception("Invalid type for '$name'");
                return (float) $val;
            case 'bool':
                if ($val === '0' || $val === 0) return false;
                if ($val === '1' || $val === 1) return true;
                if ($val === 'false') return false;
                if ($val === 'true') return true;
                if (!is_bool($val)) throw new Exception("Invalid type for '$name'");
                return (bool) $val;
            case 'array':
                if (!is_array($val)) throw new Exception("Invalid type for '$name'");
                return $val;
            case 'mixed':
                return $val;
            default:
                if (!class_exists($typeName) && !enum_exists($typeName)) {
                    throw new Exception("Unsupported type '$typeName' for '$name'");
                }

                if ($val instanceof $typeName) {
                    return $val;
                }

                if (enum_exists($typeName)) {
                    if (!is_string($val) && !is_int($val)) throw new Exception("Invalid type for '$name'");

                    $enumValue = $typeName::tryFrom($val);
                    if ($enumValue === null) throw new Exception("Invalid value for '$name'");

                    return $enumValue;
                }

                if (is_subclass_of($typeName, \API\Types\AbstractString::class)) {
                    if (!is_string($val)) throw new Exception("Invalid type for '$name'");
                    return new $typeName($val);
                }

                if (is_subclass_of($typeName, \API\Types\AbstractTypedArray::class)) {
                    if (!is_array($val)) throw new Exception("Invalid type for '$name'");
                    return new $typeName($val);
                }

                if (is_subclass_of($typeName, \API\Types\AbstractObject::class)) {
                    if (!is_array($val)) throw new Exception("Invalid type for '$name'");
                    return new $typeName(...$val);
                }

                throw new Exception("Unsupported type '$typeName' for '$name'");
        }
    }

    private function getDefault(ReflectionProperty $property): mixed {
        if ($property->hasDefaultValue()) {
            return $property->getDefaultValue();
        }

        return null;
    }

    protected function check(): void {}

    public function call(): mixed {
        if ($this->_isCalled) {
            throw new Exception('call() allowed only once, use clone before call()');
        }

        $this->_isCalled = true;

        if (!$this->_prepared) {
            $this->prepare();
        }

        $this->check();

        return $this->exec();
    }

    private function prepare(): void {
        $rawData = [];
        $vars = get_object_vars($this);

        foreach ($this->getReflectProperties() as $property) {
            $name = $property->name;
            if (array_key_exists($name, $vars)) {
                $rawData[$name] = $vars[$name];
                unset($this->$name);
            }
        }

        $this->setRawData($rawData);
    }

    abstract protected function exec(): mixed;
}
