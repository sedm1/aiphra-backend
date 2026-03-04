package com.aiphra.backend.config;

import lombok.Getter;
import lombok.Setter;
import org.springframework.boot.context.properties.ConfigurationProperties;

/**
 * Типизированные настройки приложения из environment/application.yml.
 */
@ConfigurationProperties(prefix = "app")
@Getter
@Setter
public class AppProperties {
    private String secret = "";
    private final Cors cors = new Cors();
    private final RateLimit rateLimit = new RateLimit();
    private final Api api = new Api();

    /**
     * Настройки CORS.
     */
    @Getter
    @Setter
    public static class Cors {
        private String allowedOrigins = "";
    }

    /**
     * Настройки глобального rate-limit.
     */
    @Getter
    @Setter
    public static class RateLimit {
        private boolean enabled = true;
        private int requestsPerSecond = 2;
    }

    /**
     * Настройки версионирования API.
     */
    @Getter
    @Setter
    public static class Api {
        private int defaultVersion = 1;
        private String supportedVersions = "1";
    }
}
