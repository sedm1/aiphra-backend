package com.aiphra.backend.api.method;

import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;

/**
 * Базовый класс бизнес-метода.
 * Каждый метод должен наследоваться
 * от общего абстрактного метода с единой точкой вызова.
 */
public abstract class AbstractMethod<TRequest> {
    private boolean called = false;

    /**
     * Единая точка вызова метода.
     */
    public final Object call(TRequest request) {
        if (called) {
            throw new ApiException("call() allowed only once", ErrorCodes.REQUEST_DATA);
        }
        called = true;

        if (request == null) {
            throw new ApiException("Request data is required", ErrorCodes.REQUEST_REQUIRED);
        }

        check(request);
        return exec(request);
    }

    public final boolean isCalled() {
        return called;
    }

    /**
     * Дополнительные проверки запроса.
     */
    protected void check(TRequest request) {
    }

    /**
     * Основная бизнес-логика метода.
     */
    protected abstract Object exec(TRequest request);
}