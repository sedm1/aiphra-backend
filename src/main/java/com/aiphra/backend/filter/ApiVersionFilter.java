package com.aiphra.backend.filter;

import com.aiphra.backend.api.version.ApiVersionResolver;
import com.aiphra.backend.error.ApiException;
import com.aiphra.backend.error.ErrorCodes;
import com.aiphra.backend.dto.ErrorResponse;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletRequestWrapper;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import org.springframework.core.Ordered;
import org.springframework.core.annotation.Order;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Глобальный фильтр версионирования API.
 *
 * Поддерживает оба формата:
 * - /api/...
 * - /api/v{version}/...
 *
 * Для versioned URL путь прозрачно переписывается к /api/...,
 * поэтому контроллеры не нужно дублировать под каждую версию.
 */
@Component
@Order(Ordered.HIGHEST_PRECEDENCE + 5)
@RequiredArgsConstructor
public class ApiVersionFilter extends OncePerRequestFilter {
    private static final Pattern VERSIONED_PATH = Pattern.compile("^/api/v(\\d+)(/.*)?$");

    private final ObjectMapper objectMapper;
    private final ApiVersionResolver apiVersionResolver;

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain filterChain)
            throws ServletException, IOException {
        String path = pathWithinApplication(request);
        VersionRoute route = resolveRoute(path);

        if (route == null) {
            filterChain.doFilter(request, response);
            return;
        }

        int version;
        try {
            version = apiVersionResolver.resolve(route.requestedVersion());
        } catch (ApiException ex) {
            writeJson(response, normalizeStatus(ex.getCode()), new ErrorResponse(true, ex.getMessage()));
            return;
        }

        HttpServletRequest requestToUse = request;
        if (route.rewrittenPath() != null) {
            requestToUse = new VersionedPathRequestWrapper(
                    request,
                    requestUriWithContext(request, route.rewrittenPath()),
                    route.rewrittenPath()
            );
        }

        requestToUse.setAttribute(ApiVersionResolver.REQUEST_ATTRIBUTE, version);
        filterChain.doFilter(requestToUse, response);
    }

    private VersionRoute resolveRoute(String path) {
        if (path == null || path.isBlank()) {
            return null;
        }

        Matcher matcher = VERSIONED_PATH.matcher(path);
        if (matcher.matches()) {
            Integer requestedVersion = Integer.valueOf(matcher.group(1));
            String suffix = matcher.group(2) == null ? "" : matcher.group(2);
            return new VersionRoute(requestedVersion, "/api" + suffix);
        }

        if ("/api".equals(path) || path.startsWith("/api/")) {
            return new VersionRoute(null, null);
        }

        return null;
    }

    private String pathWithinApplication(HttpServletRequest request) {
        String contextPath = request.getContextPath();
        String uri = request.getRequestURI();
        if (contextPath == null || contextPath.isEmpty()) {
            return uri;
        }
        if (uri.startsWith(contextPath)) {
            return uri.substring(contextPath.length());
        }
        return uri;
    }

    private String requestUriWithContext(HttpServletRequest request, String rewrittenPath) {
        String contextPath = request.getContextPath();
        if (contextPath == null || contextPath.isEmpty()) {
            return rewrittenPath;
        }
        return contextPath + rewrittenPath;
    }

    private int normalizeStatus(int code) {
        if (code >= 400 && code < 600) {
            return code;
        }
        return ErrorCodes.INTERNAL_SERVER_ERROR;
    }

    private void writeJson(HttpServletResponse response, int status, ErrorResponse payload) throws IOException {
        response.setStatus(status);
        response.setContentType(MediaType.APPLICATION_JSON_VALUE);
        response.getWriter().write(objectMapper.writeValueAsString(payload));
    }

    private record VersionRoute(Integer requestedVersion, String rewrittenPath) {
    }

    private static final class VersionedPathRequestWrapper extends HttpServletRequestWrapper {
        private final String requestUri;
        private final String servletPath;

        private VersionedPathRequestWrapper(HttpServletRequest request, String requestUri, String servletPath) {
            super(request);
            this.requestUri = requestUri;
            this.servletPath = servletPath;
        }

        @Override
        public String getRequestURI() {
            return requestUri;
        }

        @Override
        public String getServletPath() {
            return servletPath;
        }

        @Override
        public StringBuffer getRequestURL() {
            StringBuilder builder = new StringBuilder();
            builder.append(getScheme()).append("://").append(getServerName());

            int port = getServerPort();
            if (port > 0 && !isDefaultPort(getScheme(), port)) {
                builder.append(':').append(port);
            }

            builder.append(requestUri);
            return new StringBuffer(builder.toString());
        }

        private boolean isDefaultPort(String scheme, int port) {
            return ("http".equalsIgnoreCase(scheme) && port == 80)
                    || ("https".equalsIgnoreCase(scheme) && port == 443);
        }
    }
}
