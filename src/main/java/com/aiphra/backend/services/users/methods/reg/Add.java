package com.aiphra.backend.services.users.methods.reg;

import com.aiphra.backend.api.method.AbstractMethod;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.repositories.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.config.ConfigurableBeanFactory;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

@Component
@Scope(ConfigurableBeanFactory.SCOPE_PROTOTYPE)
@RequiredArgsConstructor
public class Add extends AbstractMethod<AddRequest> {
    private final UserRepository userRepository;

    @Override
    protected Object exec(AddRequest request) {
        String email = request.normalizedEmail();

        if (userRepository.existsByEmail(email)) {
            throw new ApiException("User already exist", ErrorCodes.AUTH);
        }

        return 1;
    }
}
