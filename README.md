# aiphra-backend

Spring Boot backend for Aiphra.

## Stack
- Java 21
- Spring Boot (Web + JDBC)
- MySQL
- Docker + Nginx

## Main endpoint
- `POST /v1/add/users/reg`

Request body:
```json
{"email":"user@example.com"}
```

Response:
```json
1
```

## Architecture (Spring-style)
- Ordinary Spring controllers and explicit endpoints
- Method = class (`services/.../methods/...`) called from controller
- Request validation via Bean Validation (`jakarta.validation`)
- No reflection-based manual field binding
- Service methods organized by domain path:
  `services/<domain>/methods/<feature>/<MethodClass>`

See [docs/local-dev.md](docs/local-dev.md) for local setup.
