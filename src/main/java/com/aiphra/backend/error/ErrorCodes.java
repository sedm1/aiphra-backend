package com.aiphra.backend.error;

import lombok.AccessLevel;
import lombok.NoArgsConstructor;

/**
 * Коды ошибок API.
 */
@NoArgsConstructor(access = AccessLevel.PRIVATE)
public final class ErrorCodes {
    public static final int FORBIDDEN = 403;
    public static final int NOT_FOUND = 404;
    public static final int METHOD_NOT_ALLOWED = 405;
    public static final int REQUEST_CONTENT_TOO_LARGE = 413;
    public static final int TOO_MANY_REQUESTS = 429;
    public static final int INTERNAL_SERVER_ERROR = 500;
    public static final int SERVER_OFF = 503;

    public static final int AUTH = 53;
    public static final int RIGHTS = 54;
    public static final int AUTH_TPA = 55;

    public static final int REQUEST_NAME = 1000;
    public static final int SERVICE = 1001;
    public static final int OPERATOR = 1002;
    public static final int METHOD = 1003;
    public static final int API_VERSION = 1004;

    public static final int REQUEST_DATA = 2000;
    public static final int REQUEST_REQUIRED = 2001;
    public static final int REQUEST_TYPE = 2002;
    public static final int REQUEST_VALUE = 2003;
    public static final int REQUEST_FILTER = 2004;
    public static final int REQUEST_PAGING = 2005;
    public static final int REQUEST_ORDER = 2006;
}