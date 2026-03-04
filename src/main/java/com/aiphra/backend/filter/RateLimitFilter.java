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
import lombok.AllArgsConstructor;
import lombok.RequiredArgsConstructor;
import org.springframework.core.Ordered;
import org.springframework.core.annotation.Order;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.time.Instant;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.atomic.AtomicLong;

/**
 * Глобальный rate-limit фильтр по IP.
 * По умолчанию ограничивает количество запросов в секунду.
 */
@Component
@Order(Ordered.HIGHEST_PRECEDENCE + 10)
@RequiredArgsConstructor
public class RateLimitFilter extends OncePerRequestFilter {
    private final ObjectMapper objectMapper;
    private final AppProperties properties;

    private boolean enabled;
    private int requestsPerSecond;
    private final Map<String, Counter> counters = new ConcurrentHashMap<>();
    private final AtomicLong requestCounter = new AtomicLong();

    @PostConstruct
    void initRateLimit() {
        enabled = properties.getRateLimit().isEnabled();
        requestsPerSecond = properties.getRateLimit().getRequestsPerSecond();
    }

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain filterChain)
            throws ServletException, IOException {
        if (!enabled || requestsPerSecond < 1) {
            filterChain.doFilter(request, response);
            return;
        }

        long now = Instant.now().getEpochSecond();
        String ip = resolveClientIp(request);
        Counter counter = counters.computeIfAbsent(ip, ignored -> new Counter(now, 0));

        int currentCount;
        synchronized (counter) {
            if (counter.second != now) {
                counter.second = now;
                counter.count = 0;
            }
            counter.count++;
            currentCount = counter.count;
        }

        int remaining = Math.max(0, requestsPerSecond - currentCount);
        response.setHeader("X-RateLimit-Limit", String.valueOf(requestsPerSecond));
        response.setHeader("X-RateLimit-Remaining", String.valueOf(remaining));
        response.setHeader("X-RateLimit-Reset", String.valueOf(now + 1));

        maybeCleanup(now);

        if (currentCount <= requestsPerSecond) {
            filterChain.doFilter(request, response);
            return;
        }

        response.setHeader("Retry-After", "1");
        writeJson(response, ErrorCodes.TOO_MANY_REQUESTS, new ErrorResponse(true, "Too many requests"));
    }

    private void maybeCleanup(long now) {
        long n = requestCounter.incrementAndGet();
        if (n % 500 != 0) {
            return;
        }
        counters.entrySet().removeIf(e -> e.getValue().second < now - 2);
    }

    private String resolveClientIp(HttpServletRequest request) {
        String forwardedFor = request.getHeader("X-Forwarded-For");
        if (forwardedFor != null && !forwardedFor.isBlank()) {
            for (String part : forwardedFor.split(",")) {
                String candidate = part.trim();
                if (!candidate.isBlank()) {
                    return candidate;
                }
            }
        }

        String xRealIp = request.getHeader("X-Real-IP");
        if (xRealIp != null && !xRealIp.isBlank()) {
            return xRealIp.trim();
        }

        String remote = request.getRemoteAddr();
        return remote == null || remote.isBlank() ? "unknown" : remote;
    }

    private void writeJson(HttpServletResponse response, int status, ErrorResponse payload) throws IOException {
        response.setStatus(status);
        response.setContentType(MediaType.APPLICATION_JSON_VALUE);
        response.getWriter().write(objectMapper.writeValueAsString(payload));
    }

    @AllArgsConstructor
    private static final class Counter {
        private long second;
        private int count;
    }
}