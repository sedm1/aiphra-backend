package com.aiphra.backend.services.system.mods;

import com.aiphra.backend.config.AppProperties;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.services.system.types.Action;
import jakarta.annotation.PostConstruct;
import lombok.RequiredArgsConstructor;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.time.ZoneOffset;
import java.time.ZonedDateTime;
import java.time.format.DateTimeFormatter;
import java.util.HexFormat;

/**
 * Модуль системных подтверждающих действий.
 */
@Component
@RequiredArgsConstructor
public class Actions {
    public static final String TABLE = "aiphra.actions";

    private static final DateTimeFormatter EXPIRES_FORMAT = DateTimeFormatter.ofPattern("yyyyMMddHHmmss");
    private static final SecureRandom SECURE_RANDOM = new SecureRandom();

    private final JdbcTemplate jdbcTemplate;
    private final AppProperties properties;

    private String appSecret;

    @PostConstruct
    void initSecret() {
        appSecret = properties.getSecret() == null ? "" : properties.getSecret();
    }

    /**
     * Добавить событие.
     *
     * @return Код подтверждения
     */
    public String add(String email, Action action) {
        String code = createCode(email, action);
        String codeHash = hmacSha256Hex(code, appSecret);

        jdbcTemplate.update(
                """
                INSERT INTO aiphra.actions (email, code, action)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE code = VALUES(code), action = VALUES(action)
                """,
                email, codeHash, action.name()
        );

        return code;
    }

    private String createCode(String email, Action action) {
        String expiresAt = ZonedDateTime.now(ZoneOffset.UTC).plusMinutes(10).format(EXPIRES_FORMAT);
        byte[] nonceBytes = new byte[16];
        SECURE_RANDOM.nextBytes(nonceBytes);
        String nonce = HexFormat.of().formatHex(nonceBytes);
        String signature = hmacSha256Hex(email + "|" + action.name() + "|" + expiresAt + "|" + nonce, appSecret);
        return expiresAt + "." + nonce + "." + signature;
    }

    private String hmacSha256Hex(String value, String key) {
        try {
            Mac mac = Mac.getInstance("HmacSHA256");
            mac.init(new SecretKeySpec(key.getBytes(StandardCharsets.UTF_8), "HmacSHA256"));
            return HexFormat.of().formatHex(mac.doFinal(value.getBytes(StandardCharsets.UTF_8)));
        } catch (Exception ex) {
            throw new ApiException("Cannot generate action code", ErrorCodes.INTERNAL_SERVER_ERROR);
        }
    }
}