package com.aiphra.backend.controller;

import com.aiphra.backend.services.users.methods.reg.Add;
import com.aiphra.backend.services.users.methods.reg.AddRequest;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.ObjectProvider;
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
    private final ObjectProvider<Add> addProvider;

    /**
     * Первичная регистрация пользователя.
     */
    @PostMapping(value = "/add/users/reg", consumes = MediaType.APPLICATION_JSON_VALUE, produces = MediaType.APPLICATION_JSON_VALUE)
    public Object register(@Valid @RequestBody AddRequest request) {
        return addProvider.getObject().call(request);
    }
}
