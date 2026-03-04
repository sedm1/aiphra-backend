package com.aiphra.backend.services.users.methods.reg;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;

/**
 * DTO запроса для первичной регистрации пользователя.
 */
public record AddRequest(
        @NotBlank(message = "Email обязателен")
        @Email(message = "Введите корректный Email")
        String email
) {
    public String normalizedEmail() {
        return email.trim().toLowerCase();
    }
}
