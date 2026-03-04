package com.aiphra.backend.api.version;

import com.aiphra.backend.config.AppProperties;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import jakarta.annotation.PostConstruct;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Component;

import java.util.Arrays;
import java.util.Set;
import java.util.stream.Collectors;

/**
 * Централизованное разрешение версии API.
 *
 * Правила:
 * - если версия в URL не передана, берется defaultVersion;
 * - версия должна входить в supportedVersions.
 */
@Component
@RequiredArgsConstructor
public class ApiVersionResolver {
    public static final String REQUEST_ATTRIBUTE = "aiphra.api.version";

    private final AppProperties properties;

    private int defaultVersion;
    private Set<Integer> supportedVersions = Set.of(1);

    @PostConstruct
    void init() {
        defaultVersion = properties.getApi().getDefaultVersion();
        if (defaultVersion < 1) {
            throw new IllegalStateException("app.api.default-version must be >= 1");
        }

        String raw = properties.getApi().getSupportedVersions();
        Set<Integer> parsed = parseVersions(raw);

        if (parsed.isEmpty()) {
            supportedVersions = Set.of(defaultVersion);
            return;
        }

        supportedVersions = Set.copyOf(parsed);
        if (!supportedVersions.contains(defaultVersion)) {
            throw new IllegalStateException("app.api.default-version must be listed in app.api.supported-versions");
        }
    }

    /**
     * Разрешить версию из URL с учетом конфигурации.
     */
    public int resolve(Integer requestedVersion) {
        int resolved = requestedVersion == null ? defaultVersion : requestedVersion;
        if (!supportedVersions.contains(resolved)) {
            throw new ApiException("API version v" + resolved + " is not supported", ErrorCodes.NOT_FOUND);
        }
        return resolved;
    }

    private Set<Integer> parseVersions(String raw) {
        if (raw == null || raw.isBlank()) {
            return Set.of();
        }

        return Arrays.stream(raw.trim().split("[,\\s]+"))
                .filter(s -> !s.isBlank())
                .map(this::parseVersion)
                .collect(Collectors.toSet());
    }

    private int parseVersion(String value) {
        try {
            int parsed = Integer.parseInt(value);
            if (parsed < 1) {
                throw new IllegalArgumentException("API versions must be >= 1");
            }
            return parsed;
        } catch (NumberFormatException ex) {
            throw new IllegalStateException("Invalid API version in app.api.supported-versions: " + value, ex);
        }
    }
}
