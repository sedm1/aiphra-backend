package com.aiphra.backend.services.users.methods.reg;

import com.aiphra.backend.api.method.AbstractMethod;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.models.User;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.config.ConfigurableBeanFactory;
import org.springframework.context.annotation.Scope;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

/**
 * Метод первичной регистрации пользователя.
 *
 * Создает ссылку/код на подтверждение регистрации.
 */
@Component
@Scope(ConfigurableBeanFactory.SCOPE_PROTOTYPE)
@RequiredArgsConstructor
public class Add extends AbstractMethod<AddRequest> {
    private final JdbcTemplate jdbcTemplate;

    /**
     * Выполнение метода регистрации.
     */
    @Override
    protected Object exec(AddRequest request) {
        String email = request.normalizedEmail();

        Integer exists = jdbcTemplate.query(
                "SELECT 1 FROM " + User.TABLE + " WHERE email = ? LIMIT 1",
                rs -> rs.next() ? 1 : null,
                email
        );
        if (exists != null) {
            throw new ApiException("User already exist", ErrorCodes.AUTH);
        }

        return 1;
    }
}