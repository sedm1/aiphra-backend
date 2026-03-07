# aiphra-backend

Spring Boot backend for Aiphra.

## Stack
- Java 21
- Spring Boot (Web + Spring Data JPA)
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
Returned value is the created user id.

## Architecture (Spring-style)
- Ordinary Spring controllers and explicit endpoints
- Business logic lives in Spring services (`@Service`) with constructor DI
- Request validation via Bean Validation (`jakarta.validation`)
- No reflection-based manual field binding
- Domain-oriented package structure (`controller`, `services`, `repositories`, `models`)

See [docs/local-dev.md](docs/local-dev.md) for local setup.

