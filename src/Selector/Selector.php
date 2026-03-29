<?php

namespace Selector;

use API\Method\AbstractMethod;
use DB\PDO\Mysql;
use Exception;
use Models\AbstractModel;
use Selector\Types\FetchStyle;
use Selector\Types\Operator;

/**
 * Simplified Selector with model-aware field mapping and automatic joins.
 */
class Selector {
    public const int MAX_COUNT_JOINS = 26;
    private const string MAIN_TABLE_ALIAS = 'm';

    /**
     * @var class-string<AbstractModel>|false
     */
    private string|false $modelClassName = false;

    /**
     * @var string[]
     */
    private array $availableFieldsNames = [];

    /**
     * @var string[]
     */
    private array $fieldsSelect = [];

    /**
     * @var array<int, array{name:string, operator:Operator, values:array}>
     */
    private array $fieldsFilter = [];

    /**
     * @var array<int, array{name:string, direction:string, orderValues:?array}>
     */
    private array $fieldsOrder = [];

    private int $limit = 0;
    private int $offset = 0;
    private FetchStyle $fetchStyle;

    /**
     * @param class-string<AbstractModel>|false $modelClassName
     * @param string[] $availableFieldsNames
     */
    public function __construct(string|false $modelClassName = false, array $availableFieldsNames = []) {
        $this->fetchStyle = FetchStyle::FetchAll;

        if ($modelClassName !== false) {
            if (!class_exists($modelClassName)) {
                throw new Exception(self::class . ": Unknown modelClassName '$modelClassName'");
            }
            if (!is_subclass_of($modelClassName, AbstractModel::class)) {
                throw new Exception(self::class . ": modelClassName '$modelClassName' must extend " . AbstractModel::class);
            }

            $this->modelClassName = $modelClassName;
            $this->availableFieldsNames = $modelClassName::AVAILABLE_FIELD_NAMES;
        } else {
            $this->availableFieldsNames = array_values(array_unique($availableFieldsNames));
        }
    }

    public function setByRequest(bool $requiresFilter = false, ?AbstractMethod $apiMethod = null): static {
        $selectorData = [
            'fields' => [],
            'filters' => [],
            'id' => null,
            'orders' => [],
            'limit' => null,
            'offset' => 0,
            'fetchStyle' => null,
            'fetch_style' => null,
        ];

        if ($apiMethod) {
            $selectorData = array_merge($selectorData, $apiMethod->getSelectorData());
        } else {
            if (function_exists('r_arr')) {
                $selectorData['fields'] = r_arr('fields', []) ?? [];
                $selectorData['filters'] = r_arr('filters', []) ?? [];
                $selectorData['orders'] = r_arr('orders', []) ?? [];
            }
            if (function_exists('r_int')) {
                $selectorData['id'] = r_int('id', null);
                $selectorData['limit'] = r_int('limit', null);
                $selectorData['offset'] = r_int('offset', 0) ?? 0;
            }
            if (function_exists('req')) {
                $selectorData['fetchStyle'] = req('fetchStyle', '');
                $selectorData['fetch_style'] = req('fetch_style', '');
            }
        }

        if (!empty($selectorData['fields'])) {
            $this->setFieldsSelect($selectorData['fields']);
        }
        if (!empty($selectorData['filters'])) {
            $this->setFieldsFilter($selectorData['filters']);
        }
        if (!empty($selectorData['orders'])) {
            $this->setFieldsOrder($selectorData['orders']);
        }
        if (isset($selectorData['limit']) && $selectorData['limit'] !== null) {
            $this->setLimit((int)$selectorData['limit']);
        }
        if (isset($selectorData['offset']) && $selectorData['offset'] !== null) {
            $this->setOffset((int)$selectorData['offset']);
        }

        if (!empty($selectorData['fetchStyle'])) {
            $this->setFetchStyle($selectorData['fetchStyle']);
        }
        if (!empty($selectorData['fetch_style'])) {
            $this->setFetchStyle($selectorData['fetch_style']);
        }

        if (isset($selectorData['id']) && $selectorData['id']) {
            $this->addFieldFilter(Field::genFilterData('id', Operator::Equals, [(int)$selectorData['id']]));
        }

        if ($requiresFilter && !$this->fieldsFilter) {
            throw new Exception('filters', ERROR_CODE_REQUEST_REQUIRED);
        }

        return $this;
    }

    public function setFieldsSelect(array $fieldsAliasesOrFieldsData): static {
        $this->fieldsSelect = [];
        foreach ($fieldsAliasesOrFieldsData as $fieldAliasOrFieldData) {
            $this->addFieldSelect($fieldAliasOrFieldData);
        }

        return $this;
    }

    public function addFieldSelect(array|string $fieldAliasOrFieldData, bool $prepend = false): static {
        $name = $this->normalizeFieldName($fieldAliasOrFieldData, 'fields');
        if (in_array($name, $this->fieldsSelect, true)) {
            return $this;
        }

        if ($prepend) {
            array_unshift($this->fieldsSelect, $name);
        } else {
            $this->fieldsSelect[] = $name;
        }

        return $this;
    }

    public function setFieldsFilter(array $fieldsData): static {
        $this->fieldsFilter = [];
        foreach ($fieldsData as $fieldData) {
            $this->addFieldFilter($fieldData);
        }

        return $this;
    }

    public function addFieldFilter(array $fieldData): static {
        $this->fieldsFilter[] = $this->normalizeFilterData($fieldData);

        return $this;
    }

    public function setFieldsOrder(array $fieldsAliasesOrFieldsData): static {
        $this->fieldsOrder = [];
        foreach ($fieldsAliasesOrFieldsData as $fieldData) {
            $this->addFieldOrder($fieldData);
        }

        return $this;
    }

    public function addFieldOrder(array|string $fieldAliasOrFieldData): static {
        $this->fieldsOrder[] = $this->normalizeOrderData($fieldAliasOrFieldData);

        return $this;
    }

    public function setLimit(?int $limit): static {
        $limit = $limit ?? 0;
        if ($limit < 0) {
            throw new Exception(self::class . ": 'limit' cannot be less than zero", ERROR_CODE_REQUEST_PAGING);
        }

        $this->limit = $limit;

        return $this;
    }

    public function setOffset(int $offset): static {
        if ($offset < 0) {
            throw new Exception(self::class . ": 'offset' cannot be less than zero", ERROR_CODE_REQUEST_PAGING);
        }

        $this->offset = $offset;

        return $this;
    }

    public function setFetchStyle(FetchStyle|string $fetchStyle): static {
        if (is_string($fetchStyle)) {
            $fetchStyleEnum = FetchStyle::tryFrom($fetchStyle);
            if (!$fetchStyleEnum) {
                throw new Exception("Unknown fetch style: $fetchStyle", ERROR_CODE_REQUEST_VALUE);
            }
            $fetchStyle = $fetchStyleEnum;
        }

        $this->fetchStyle = $fetchStyle;

        return $this;
    }

    public function getFetchStyle(): FetchStyle {
        return $this->fetchStyle;
    }

    /**
     * @return class-string<AbstractModel>|false
     */
    public function getModelClassName(): string|false {
        return $this->modelClassName;
    }

    /**
     * @return string[]
     */
    public function getFieldsSelect(): array {
        return $this->fieldsSelect;
    }

    /**
     * @return array<int, array{name:string, operator:Operator, values:array}>
     */
    public function getFieldsFilter(): array {
        return $this->fieldsFilter;
    }

    /**
     * @return array<int, array{name:string, direction:string, orderValues:?array}>
     */
    public function getFieldsOrder(): array {
        return $this->fieldsOrder;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function getOffset(): int {
        return $this->offset;
    }

    public function fieldExists(?string $fieldName = null): bool {
        if ($fieldName === null) {
            return (bool)$this->availableFieldsNames;
        }

        if (!$this->availableFieldsNames) {
            return true;
        }

        return in_array($fieldName, $this->availableFieldsNames, true);
    }

    public function dbhSelect(): Mysql {
        $this->prepareSelectorByModel();

        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('select');
        $mainTableName = $this->getMainTableName();
        $mainAlias = self::MAIN_TABLE_ALIAS;

        $dbh = dbh();
        $dbh->sel($this->genSQLFieldsFromMap($tableAliasByTableName, $mainAlias));
        $dbh->from($mainTableName, $mainAlias)->appendQuery($joinSql ? ' ' . $joinSql . ' ' : '');

        [$w] = $this->genSQLWhereHavingFromMap('select', $tableAliasByTableName, $mainAlias);
        if ($w) {
            $dbh->w($w);
        }

        $o = $this->genSQLOrderFromMap($tableAliasByTableName, $mainAlias);
        if ($o) {
            $dbh->o($o);
        }

        $l = $this->genSQLLimit();
        if ($l) {
            $dbh->l($l);
        }

        $this->prepareDbhByModel($dbh);

        return $dbh;
    }

    public function dbhUpdate(string|array $set, bool $ignore = false, bool $duplicateUpdate = false): Mysql {
        $this->prepareSelectorByModel();

        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('update');
        $mainTableName = $this->getMainTableName();
        $mainAlias = self::MAIN_TABLE_ALIAS;

        $dbh = dbh()->update($mainTableName, $mainAlias)->appendQuery($joinSql ? ' ' . $joinSql . ' ' : '');

        // Current Mysql::update() does not support IGNORE. Keep signature compatible and ignore the flag.
        if ($ignore) {
            // noop
        }

        if ($joinSql && is_array($set)) {
            $setPrepared = [];
            foreach ($set as $name => $value) {
                if (strpos((string)$name, '.') !== false) {
                    $setPrepared[$name] = $value;
                    continue;
                }

                $setPrepared[$mainAlias . '.' . $name] = $value;
            }
            $set = $setPrepared;
        }

        $dbh->set($set, $duplicateUpdate);

        [$w] = $this->genSQLWhereHavingFromMap('update', $tableAliasByTableName, $mainAlias);
        if ($w) {
            $dbh->w($w);
        }

        $this->prepareDbhByModel($dbh);

        return $dbh;
    }

    public function dbhDelete(): Mysql {
        $this->prepareSelectorByModel();

        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('delete');
        $mainTableName = $this->getMainTableName();
        $mainAlias = self::MAIN_TABLE_ALIAS;

        $dbh = dbh()->del($mainTableName, $mainAlias)->appendQuery($joinSql ? ' ' . $joinSql . ' ' : '');

        [$w] = $this->genSQLWhereHavingFromMap('delete', $tableAliasByTableName, $mainAlias);
        if ($w) {
            $dbh->w($w);
        }

        $this->prepareDbhByModel($dbh);

        return $dbh;
    }

    public function execFetch(bool $setResultMetadata = false, bool $setResultTotal = false): mixed {
        if ($this->fetchStyle === FetchStyle::Selector) {
            return $this;
        }

        $dbh = $this->dbhSelect();
        $fetchStyle = $this->fetchStyle->getDbhFetchStyle();

        $res = match ($this->fetchStyle) {
            FetchStyle::Fetch, FetchStyle::FetchColumn => $dbh->fetch($fetchStyle),
            default => $dbh->fetchAll($fetchStyle),
        };

        if ($setResultMetadata) {
            core()->info['result_meta'] = $dbh->getMeta();
        }
        if ($setResultTotal) {
            core()->info['result_total'] = $this->countTotal();
        }

        return $res;
    }

    /**
     * @return array{0:string,1:string}
     */
    public function genSQLWhereHaving(bool $onlyMainTable = false): array {
        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('select');
        if ($joinSql) {
            // no-op, only map is needed
        }

        return $this->genSQLWhereHavingFromMap('select', $tableAliasByTableName, self::MAIN_TABLE_ALIAS, $onlyMainTable);
    }

    public function genSQLFields(): string {
        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('select');
        if ($joinSql) {
            // no-op, only map is needed
        }

        return $this->genSQLFieldsFromMap($tableAliasByTableName, self::MAIN_TABLE_ALIAS);
    }

    public function genSQLOrder(): string {
        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('select');
        if ($joinSql) {
            // no-op, only map is needed
        }

        return $this->genSQLOrderFromMap($tableAliasByTableName, self::MAIN_TABLE_ALIAS);
    }

    public function genSQLLimit(): string {
        if ($this->limit <= 0) {
            return '';
        }

        if ($this->offset > 0) {
            return $this->offset . ', ' . $this->limit;
        }

        return (string)$this->limit;
    }

    /**
     * @return string[]
     */
    private function resolveFieldsSelect(): array {
        if (!$this->fieldsSelect) {
            throw new Exception('fields', ERROR_CODE_REQUEST_REQUIRED);
        }

        return $this->fieldsSelect;
    }

    private function getMainTableName(): string {
        if (!$this->modelClassName) {
            throw new Exception(self::class . ': modelClassName is required');
        }

        $tableName = $this->modelClassName::getMainTableName();
        if (!$tableName) {
            throw new Exception(self::class . ': model table name is empty');
        }

        return $tableName;
    }

    private function normalizeFieldName(array|string $fieldAliasOrFieldData, string $errorScope): string {
        if (is_array($fieldAliasOrFieldData)) {
            $name = $fieldAliasOrFieldData['name'] ?? $fieldAliasOrFieldData['alias'] ?? null;
        } else {
            $name = $fieldAliasOrFieldData;
        }

        if (!is_string($name) || $name === '') {
            throw new Exception("Invalid field name in '$errorScope'");
        }

        if (!$this->fieldExists($name)) {
            throw new Exception("Field '$name' is not available in model");
        }

        return $name;
    }

    /**
     * @return array{name:string, operator:Operator, values:array}
     */
    private function normalizeFilterData(array $fieldData): array {
        $name = $fieldData['name'] ?? $fieldData['alias'] ?? null;
        if (!is_string($name) || $name === '') {
            throw new Exception("Request_error_required: filters[n].name", ERROR_CODE_REQUEST_FILTER);
        }
        if (!$this->fieldExists($name)) {
            throw new Exception("Field '$name' is not available in model", ERROR_CODE_REQUEST_FILTER);
        }

        $rawValues = $fieldData['values'] ?? [];
        if (!is_array($rawValues)) {
            throw new Exception("Request_error_type: filters[n].values", ERROR_CODE_REQUEST_FILTER);
        }

        $rawOperator = $fieldData['operator'] ?? null;
        $operator = null;
        if ($rawOperator instanceof Operator) {
            $operator = $rawOperator;
        } elseif (is_string($rawOperator) && $rawOperator !== '') {
            $operator = Operator::tryFrom($rawOperator);
        }

        if (!$operator) {
            $operator = $rawValues ? Operator::Equals : Operator::IsNotNull;
        }

        if (!in_array($operator, [Operator::IsNull, Operator::IsNotNull], true) && !$rawValues) {
            $rawValues = [''];
        }

        return [
            'name' => $name,
            'operator' => $operator,
            'values' => $rawValues,
        ];
    }

    /**
     * @return array{name:string, direction:string, orderValues:?array}
     */
    private function normalizeOrderData(array|string $fieldAliasOrFieldData): array {
        if (is_string($fieldAliasOrFieldData)) {
            $name = $fieldAliasOrFieldData;
            $direction = 'ASC';
            $orderValues = null;
        } else {
            $name = $fieldAliasOrFieldData['name'] ?? $fieldAliasOrFieldData['alias'] ?? null;
            $direction = strtoupper((string)($fieldAliasOrFieldData['direction'] ?? 'ASC'));
            $orderValues = $fieldAliasOrFieldData['orderValues'] ?? null;
        }

        if (!is_string($name) || $name === '') {
            throw new Exception("Request_error_required: orders[n].name", ERROR_CODE_REQUEST_ORDER);
        }
        if (!$this->fieldExists($name)) {
            throw new Exception("Field '$name' is not available in model", ERROR_CODE_REQUEST_ORDER);
        }

        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new Exception("Request_error_value: orders[n].direction", ERROR_CODE_REQUEST_ORDER);
        }

        if ($orderValues !== null && !is_array($orderValues)) {
            throw new Exception("Request_error_type: orders[n].orderValues", ERROR_CODE_REQUEST_ORDER);
        }

        return [
            'name' => $name,
            'direction' => $direction,
            'orderValues' => $orderValues,
        ];
    }

    /**
     * @param string $action select|update|delete
     * @param array<string, string> $tableAliasByTableName
     * @return array{0:string,1:string}
     */
    private function genSQLWhereHavingFromMap(
        string $action,
        array $tableAliasByTableName,
        string $mainAlias,
        bool $onlyMainTable = false,
    ): array {
        $where = $this->genWhereSql($action, $tableAliasByTableName, $mainAlias, $onlyMainTable);

        return [$where, ''];
    }

    /**
     * @param array<string, string> $tableAliasByTableName
     */
    private function genSQLFieldsFromMap(array $tableAliasByTableName, string $mainAlias): string {
        $fields = $this->resolveFieldsSelect();
        if ($fields === ['*']) {
            return '*';
        }

        $parts = [];
        foreach ($fields as $fieldName) {
            $fieldSql = $this->resolveFieldSql($fieldName, 'select', $tableAliasByTableName, $mainAlias);
            $parts[] = $fieldSql . ' AS ' . $this->quoteIdentifier($fieldName);
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<string, string> $tableAliasByTableName
     */
    private function genSQLOrderFromMap(array $tableAliasByTableName, string $mainAlias): string {
        if (!$this->fieldsOrder) {
            return '';
        }

        $parts = [];
        foreach ($this->fieldsOrder as $fieldOrder) {
            $nameSql = $this->resolveFieldSql($fieldOrder['name'], 'order', $tableAliasByTableName, $mainAlias);
            $direction = $fieldOrder['direction'];

            if (!empty($fieldOrder['orderValues'])) {
                $orderValues = array_map(
                    fn(mixed $value): string => dbh()->prepare_val($value),
                    $fieldOrder['orderValues'],
                );
                $parts[] = "FIELD($nameSql, " . implode(',', $orderValues) . ") $direction";
                continue;
            }

            $parts[] = "$nameSql $direction";
        }

        return implode(', ', $parts);
    }

    /**
     * @param string $action select|update|delete
     * @param array<string, string> $tableAliasByTableName
     */
    private function genWhereSql(
        string $action,
        array $tableAliasByTableName,
        string $mainAlias,
        bool $onlyMainTable = false,
    ): string {
        if (!$this->fieldsFilter) {
            return '';
        }

        $parts = [];
        foreach ($this->fieldsFilter as $fieldFilter) {
            $fieldName = $fieldFilter['name'];
            if ($onlyMainTable && $this->modelClassName) {
                $fieldTableName = $this->resolveFieldTableName($fieldName);
                if ($fieldTableName !== $this->getMainTableName()) {
                    continue;
                }
            }

            $nameSql = $this->resolveFieldSql($fieldName, $action, $tableAliasByTableName, $mainAlias);
            $operator = $fieldFilter['operator'];
            $values = $fieldFilter['values'];

            $parts[] = $this->genFilterSql($nameSql, $operator, $values);
        }

        return implode(' AND ', $parts);
    }

    private function genFilterSql(string $nameSql, Operator $operator, array $values): string {
        $preparedValues = array_map(static fn(mixed $value): string => dbh()->prepare_val($value), $values);

        return match ($operator) {
            Operator::Equals => "$nameSql = " . ($preparedValues[0] ?? "''"),
            Operator::NotEquals => "$nameSql != " . ($preparedValues[0] ?? "''"),
            Operator::In => $preparedValues ? "$nameSql IN (" . implode(',', $preparedValues) . ')' : '0',
            Operator::NotIn => $preparedValues ? "$nameSql NOT IN (" . implode(',', $preparedValues) . ')' : '1',
            Operator::GreaterThan => "$nameSql > " . ($preparedValues[0] ?? '0'),
            Operator::GreaterThanEquals => "$nameSql >= " . ($preparedValues[0] ?? '0'),
            Operator::LessThan => "$nameSql < " . ($preparedValues[0] ?? '0'),
            Operator::LessThanEquals => "$nameSql <= " . ($preparedValues[0] ?? '0'),
            Operator::StartsWith => "$nameSql LIKE " . dbh()->prepare_val(($values[0] ?? '') . '%'),
            Operator::Contains, Operator::ContainsFullText => "$nameSql LIKE " . dbh()->prepare_val('%' . ($values[0] ?? '') . '%'),
            Operator::DoesNotContain => "$nameSql NOT LIKE " . dbh()->prepare_val('%' . ($values[0] ?? '') . '%'),
            Operator::Regexp => "$nameSql REGEXP " . ($preparedValues[0] ?? "''"),
            Operator::NotRegexp => "$nameSql NOT REGEXP " . ($preparedValues[0] ?? "''"),
            Operator::IsNull => "$nameSql IS NULL",
            Operator::IsNotNull => "$nameSql IS NOT NULL",
            Operator::Between => $this->genBetweenSql($nameSql, $preparedValues),
        };
    }

    /**
     * @param string[] $preparedValues
     */
    private function genBetweenSql(string $nameSql, array $preparedValues): string {
        if (!$preparedValues) {
            return '0';
        }

        $parts = [];
        for ($i = 0; $i < count($preparedValues); $i += 2) {
            $from = $preparedValues[$i];
            $to = $preparedValues[$i + 1] ?? $from;
            $parts[] = "($nameSql BETWEEN $from AND $to)";
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /**
     * @param string $action select|update|delete
     * @return array{0:string,1:array<string, string>}
     */
    private function genSQLJoin(string $action): array {
        $tableAliasByTableName = [];

        if (!$this->modelClassName) {
            return ['', $tableAliasByTableName];
        }

        $mainTableName = $this->getMainTableName();
        $mainAlias = self::MAIN_TABLE_ALIAS;
        $tableAliasByTableName[$mainTableName] = $mainAlias;

        $fieldsAll = $this->resolveFieldsForJoin($action);
        $joinByTableAlias = $this->genJoinByTableAlias($fieldsAll, $mainAlias, $tableAliasByTableName);

        if (count($joinByTableAlias) > self::MAX_COUNT_JOINS) {
            throw new Exception(
                self::class . ': exceeding the maximum joins (' . count($joinByTableAlias) . '/' . self::MAX_COUNT_JOINS . ')',
            );
        }

        $joinsSQL = [];
        foreach ($joinByTableAlias as $join) {
            $joinsSQL[] = $join->genSQLJoin();
        }

        return [implode(' ', $joinsSQL), $tableAliasByTableName];
    }

    /**
     * @param string[] $fields
     * @param array<string, string> $tableAliasByTableName
     * @return array<string, Join>
     */
    private function genJoinByTableAlias(array $fields, string $leftTableAlias, array &$tableAliasByTableName): array {
        $joinByTableAlias = [];

        if (!$this->modelClassName) {
            return $joinByTableAlias;
        }

        $mainTableName = $this->getMainTableName();

        foreach ($fields as $fieldName) {
            $fieldTableName = $this->resolveFieldTableName($fieldName);
            if (!$fieldTableName || $fieldTableName === $mainTableName) {
                continue;
            }

            $joiners = $this->modelClassName::getTableJoinersByFieldName($fieldName);
            if (!$joiners) {
                throw new Exception(self::class . ": field '$fieldName' requires joiners");
            }

            if (!isset($tableAliasByTableName[$fieldTableName])) {
                $tableAliasByTableName[$fieldTableName] = 'j' . count($tableAliasByTableName);
            }
            $tableAlias = $tableAliasByTableName[$fieldTableName];

            if (!isset($joinByTableAlias[$tableAlias])) {
                $joinByTableAlias[$tableAlias] = new Join($leftTableAlias, $fieldTableName, $tableAlias, $joiners);
                continue;
            }

            if (!$joinByTableAlias[$tableAlias]->checkJoinersEquals($joiners)) {
                throw new Exception(
                    self::class . ": conflicting joiners for table '$fieldTableName' (alias '$tableAlias')",
                );
            }
        }

        return $joinByTableAlias;
    }

    /**
     * @param string $action select|update|delete
     * @return string[]
     */
    private function resolveFieldsForJoin(string $action): array {
        $fields = [];
        switch ($action) {
            case 'select':
                $fields = array_merge(
                    $this->resolveFieldsSelect(),
                    array_column($this->fieldsFilter, 'name'),
                    array_column($this->fieldsOrder, 'name'),
                );
                break;
            case 'update':
            case 'delete':
                $fields = array_column($this->fieldsFilter, 'name');
                break;
        }

        $fields = array_filter($fields, static fn(mixed $field): bool => is_string($field) && $field !== '*');

        return array_values(array_unique($fields));
    }

    /**
     * @param string $action select|filter|order|update|delete
     * @param array<string, string> $tableAliasByTableName
     */
    private function resolveFieldSql(string $fieldName, string $action, array $tableAliasByTableName, string $mainAlias): string {
        if ($fieldName === '*') {
            return '*';
        }

        if (!$this->modelClassName) {
            if ($this->isSimpleIdentifierPath($fieldName)) {
                return $this->quoteField($fieldName);
            }

            return $fieldName;
        }

        $fieldTableName = $this->resolveFieldTableName($fieldName);
        $fieldTableAlias = $tableAliasByTableName[$fieldTableName] ?? null;
        if ($fieldTableAlias === null && $fieldTableName === $this->getMainTableName()) {
            $fieldTableAlias = $mainAlias;
        }
        if ($fieldTableAlias === null) {
            throw new Exception(self::class . ": table alias for field '$fieldName' is not resolved");
        }

        $originalFieldName = $this->modelClassName::getOriginalFieldName($fieldName, $action) ?: $fieldName;

        if ($this->isSimpleIdentifier($originalFieldName)) {
            return $this->quoteIdentifier($fieldTableAlias) . '.' . $this->quoteIdentifier($originalFieldName);
        }

        if ($this->isSimpleIdentifierPath($originalFieldName)) {
            return $this->quoteField($originalFieldName);
        }

        return $originalFieldName;
    }

    private function resolveFieldTableName(string $fieldName): string {
        if (!$this->modelClassName) {
            return '';
        }

        $tableName = $this->modelClassName::getOriginalTableName($fieldName);
        if (!$tableName) {
            $tableName = $this->getMainTableName();
        }

        return $tableName;
    }

    private function quoteField(string $name): string {
        return '`' . str_replace(['`', '.'], ['', '`.`'], $name) . '`';
    }

    private function quoteIdentifier(string $name): string {
        return '`' . str_replace('`', '', $name) . '`';
    }

    private function isSimpleIdentifier(string $name): bool {
        return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    private function isSimpleIdentifierPath(string $name): bool {
        return (bool)preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $name);
    }

    private function prepareSelectorByModel(): void {
        if (!$this->modelClassName) {
            return;
        }

        $this->modelClassName::prepareSelector($this);
    }

    private function prepareDbhByModel(Mysql $dbh): void {
        if (!$this->modelClassName) {
            return;
        }

        $this->modelClassName::prepareDbh($this, $dbh);
    }

    private function countTotal(): int {
        $this->prepareSelectorByModel();

        [$joinSql, $tableAliasByTableName] = $this->genSQLJoin('select');
        $mainTableName = $this->getMainTableName();
        $mainAlias = self::MAIN_TABLE_ALIAS;

        $dbh = dbh()->sel('COUNT(*)')->from($mainTableName, $mainAlias)->appendQuery($joinSql ? ' ' . $joinSql . ' ' : '');

        [$w] = $this->genSQLWhereHavingFromMap('select', $tableAliasByTableName, $mainAlias);
        if ($w) {
            $dbh->w($w);
        }

        $this->prepareDbhByModel($dbh);

        return (int)$dbh->fetchColumn();
    }
}
