package com.aiphra.backend.services.users;

import com.aiphra.backend.dto.users.UserRegistrationRequest;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.models.User;
import com.aiphra.backend.repositories.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

@Service
@RequiredArgsConstructor
public class UserRegistrationService {
    private final UserRepository userRepository;

    public Long register(UserRegistrationRequest request) {
        String email = request.normalizedEmail();
        if (userRepository.existsByEmail(email)) {
            throw new ApiException("User already exists", ErrorCodes.AUTH);
        }

        User user = new User();
        user.setEmail(email);
        return userRepository.save(user).getId();
    }
}
