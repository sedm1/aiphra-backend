package com.aiphra.backend.dto.users;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import java.util.Locale;

public record UserRegistrationRequest(
        @NotBlank(message = "Email is required")
        @Email(message = "Please provide a valid email")
        String email
) {
    public String normalizedEmail() {
        return email.trim().toLowerCase(Locale.ROOT);
    }
}
