<?php

namespace Models;

use DB\PDO\AbstractDB;
use Selector\Selector;

/**
 * Base model metadata used by Selector.
 */
abstract class AbstractModel {

    /**
     * Main table.
     */
    public const string T = '';

    /**
     * All fields available for selector usage.
     */
    public const array AVAILABLE_FIELD_NAMES = [];

    /**
     * Default fields selected when request does not provide `fields`.
     */
    public const array DEFAULT_FIELD_NAMES = [];

    /**
     * @return list<string>
     */
    public static function getDefaultsFields(): array {
        return static::DEFAULT_FIELD_NAMES;
    }

    public static function getMainTableName(): string {
        return static::T;
    }

    public static function getOriginalTableName(string $fieldName): string {
        return static::getMainTableName();
    }

    /**
     * @param string $action select|filter|order
     */
    public static function getOriginalFieldName(string $fieldName, ?string $action = null): string {
        return $fieldName;
    }

    /**
     * Join map where key is left side field/expression and value is right side field.
     *
     * Example:
     * ['id' => 'user_id'] generates ON (`right_alias`.`user_id` = `left_alias`.`id`)
     *
     * @return array<string, string>
     */
    public static function getTableJoinersByFieldName(string $fieldName): array {
        return [];
    }

    public static function prepareSelector(Selector $selector): void {}

    public static function prepareDbh(Selector $selector, AbstractDB $dbh): void {}
}
