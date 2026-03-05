package com.aiphra.backend.web;

/**
 * Единый формат JSON-ошибки.
 */
public record ErrorResponse(boolean error, String message) {
}
