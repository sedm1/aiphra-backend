<?php

namespace Models;

/**
 * Базовый класс модели
 *
 * Содержит информацию о таблицах и их полях
 */
abstract class AbstractModel {

    /**
     * Основная таблица
     *
     * Переопределить при реализации
     */
    public const string T = '';

    /**
     * Доступные поля модели
     *
     * Переопределить при реализации
     */
    public const array AVAILABLE_FIELD_NAMES = [];
}