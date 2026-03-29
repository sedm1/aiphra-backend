<?php

namespace API\Method;

use Exception;
use Selector\Field;
use Selector\Selector;
use Selector\Types\Operator;

/**
 * Реализация API метода на основе модели.
 */
abstract class AbstractWithModel extends AbstractMethod {

    /**
     * Модель данных, которую будет использовать API метод.
     *
     * @var class-string<\Models\AbstractModel>
     */
    public const string MODEL = '';

    /**
     * Сгенерировать Selector на основе указанных параметров API метода.
     */
    protected function genSelector(bool $forEdit = true): Selector {
        if (!static::MODEL) {
            throw new Exception('Class extended AbstractWithModel require const: MODEL');
        }

        $selector = new Selector(static::MODEL);
        $selector->setByRequest(requiresFilter: $forEdit, apiMethod: $this);

        if (property_exists($this, 'fields') && isset($this->fields)) {
            $selector->setFieldsSelect($this->fields);
        }
        if (property_exists($this, 'filters') && isset($this->filters)) {
            $selector->setFieldsFilter($this->filters);
        }
        if (property_exists($this, 'id') && isset($this->id)) {
            $selector->addFieldFilter(Field::genFilterData('id', Operator::Equals, [$this->id]));
        }
        if (property_exists($this, 'orders') && isset($this->orders)) {
            $selector->setFieldsOrder($this->orders);
        }
        if (property_exists($this, 'limit') && isset($this->limit)) {
            $selector->setLimit($this->limit);
        }
        if (property_exists($this, 'offset') && isset($this->offset)) {
            $selector->setOffset($this->offset);
        }
        if (property_exists($this, 'fetch_style') && isset($this->fetch_style)) {
            $selector->setFetchStyle($this->fetch_style);
        }

        if ($forEdit) {
            $canEdit = false;
            if (property_exists($this, 'filters') && ($this->filters ?? [])) {
                $canEdit = true;
            }
            if (property_exists($this, 'id') && ($this->id ?? 0)) {
                $canEdit = true;
            }

            if (!$canEdit) {
                throw new Exception('filters', ERROR_CODE_REQUEST_REQUIRED);
            }
        }

        return $selector;
    }
}
