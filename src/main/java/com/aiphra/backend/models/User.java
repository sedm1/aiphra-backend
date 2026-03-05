package com.aiphra.backend.models;

import lombok.AccessLevel;
import lombok.NoArgsConstructor;

/**
 * Модель пользователя (метаданные таблицы).
 */
@NoArgsConstructor(access = AccessLevel.PRIVATE)
public final class User {
    public static final String TABLE = "aiphra.users";
}