package com.aiphra.backend.error;

import lombok.Getter;

/**
 * Исключение прикладного уровня API с кодом ошибки.
 */
@Getter
public class ApiException extends RuntimeException {
    private final int code;

    public ApiException(String message, int code) {
        super(message);
        this.code = code;
    }
}