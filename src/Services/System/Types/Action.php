<?php

namespace Services\System\Types;

/**
 * Действие, которому требуется подтверждение
 */
enum Action: string {

    /**
     * Подтверждение email
     */
    case Email = 'email';

}