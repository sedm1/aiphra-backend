package com.aiphra.backend.controller;

import com.aiphra.backend.dto.users.UserRegistrationRequest;
import com.aiphra.backend.services.users.UserRegistrationService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RestController;

/**
 * Контроллер регистрации пользователей.
 */
@RestController
@RequiredArgsConstructor
public class UsersRegController {
    private final UserRegistrationService userRegistrationService;

    /**
     * Первичная регистрация пользователя.
     */
    @PostMapping(value = "/add/users/reg", consumes = MediaType.APPLICATION_JSON_VALUE, produces = MediaType.APPLICATION_JSON_VALUE)
    public Long register(@Valid @RequestBody UserRegistrationRequest request) {
        return userRegistrationService.register(request);
    }
}
