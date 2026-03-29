<?php

namespace Selector\Types;

enum Operator: string {
    case Equals = 'EQUALS';
    case NotEquals = 'NOT_EQUALS';
    case In = 'IN';
    case NotIn = 'NOT_IN';
    case GreaterThan = 'GREATER_THAN';
    case GreaterThanEquals = 'GREATER_THAN_EQUALS';
    case LessThan = 'LESS_THAN';
    case LessThanEquals = 'LESS_THAN_EQUALS';
    case Between = 'BETWEEN';
    case StartsWith = 'STARTS_WITH';
    case Contains = 'CONTAINS';
    case ContainsFullText = 'CONTAINS_FULLTEXT';
    case DoesNotContain = 'DOES_NOT_CONTAIN';
    case Regexp = 'REGEXP';
    case NotRegexp = 'NOT_REGEXP';
    case IsNull = 'IS_NULL';
    case IsNotNull = 'IS_NOT_NULL';
}
