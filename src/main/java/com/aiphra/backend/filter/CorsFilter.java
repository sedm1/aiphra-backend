package com.aiphra.backend.filter;

import com.aiphra.backend.config.AppProperties;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.web.ErrorResponse;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.annotation.PostConstruct;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import org.springframework.core.Ordered;
import org.springframework.core.annotation.Order;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.net.URI;
import java.util.Arrays;
import java.util.Set;
import java.util.stream.Collectors;

/**
 * Глобальный CORS-фильтр.
 */
@Component
@Order(Ordered.HIGHEST_PRECEDENCE)
@RequiredArgsConstructor
public class CorsFilter extends OncePerRequestFilter {
    private final ObjectMapper objectMapper;
    private final AppProperties properties;

    private Set<String> configuredOrigins = Set.of();

    @PostConstruct
    void initConfiguredOrigins() {
        configuredOrigins = Arrays.stream(properties.getCors().getAllowedOrigins().trim().split("[,\\s]+"))
                .filter(s -> !s.isBlank())
                .collect(Collectors.toUnmodifiableSet());
    }

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain filterChain)
            throws ServletException, IOException {
        String origin = request.getHeader("Origin");
        String allowedOrigin = allowedOrigin(origin);

        if (origin != null && !origin.isBlank()) {
            response.setHeader("Vary", "Origin");
        }

        if (allowedOrigin != null) {
            response.setHeader("Access-Control-Allow-Origin", allowedOrigin);
            response.setHeader("Access-Control-Allow-Credentials", "true");
            response.setHeader("Access-Control-Expose-Headers",
                    "X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, Retry-After");
        }

        if (!"OPTIONS".equalsIgnoreCase(request.getMethod())) {
            filterChain.doFilter(request, response);
            return;
        }

        if (origin != null && !origin.isBlank() && allowedOrigin == null) {
            writeJson(response, ErrorCodes.FORBIDDEN, new ErrorResponse(true, "CORS origin is not allowed"));
            return;
        }

        String requestHeaders = request.getHeader("Access-Control-Request-Headers");
        if (requestHeaders == null || requestHeaders.isBlank()) {
            requestHeaders = "Content-Type, X-Requested-With, Authorization";
        }

        response.setHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
        response.setHeader("Access-Control-Allow-Headers", requestHeaders);
        response.setHeader("Access-Control-Max-Age", "600");
        response.setStatus(HttpServletResponse.SC_NO_CONTENT);
    }

    private String allowedOrigin(String origin) {
        if (origin == null || origin.isBlank()) {
            return null;
        }
        if (isLoopbackOrigin(origin) || configuredOrigins.contains(origin)) {
            return origin;
        }
        return null;
    }

    private boolean isLoopbackOrigin(String origin) {
        try {
            URI uri = URI.create(origin);
            String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase();
            String host = uri.getHost() == null ? "" : uri.getHost().toLowerCase();
            if (!scheme.equals("http") && !scheme.equals("https")) {
                return false;
            }
            return host.equals("127.0.0.1") || host.equals("localhost") || host.equals("::1");
        } catch (Exception ex) {
            return false;
        }
    }

    private void writeJson(HttpServletResponse response, int status, ErrorResponse payload) throws IOException {
        response.setStatus(status);
        response.setContentType(MediaType.APPLICATION_JSON_VALUE);
        response.getWriter().write(objectMapper.writeValueAsString(payload));
    }
}