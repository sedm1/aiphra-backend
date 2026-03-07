package com.aiphra.backend.dto;

/**
 * Единый формат JSON-ошибки.
 */
public record ErrorResponse(boolean error, String message) {
}
