# Local Development

## Requirements
- Java 21
- Maven 3.9+
- MySQL 8+

## Environment
1. Copy template:
```bash
cp .env.example .env
```
2. Fill values:
```
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3306
DB_MYSQL_NAME=aiphra
DB_MYSQL_USER=aiphra
DB_MYSQL_PASS=secret
APP_SECRET=change_me
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS_PER_SECOND=2
CORS_ALLOWED_ORIGINS=http://localhost:3000
SERVER_PORT=8080
SPRING_PROFILES_ACTIVE=dev
```

## Run locally
```bash
mvn spring-boot:run -Dspring-boot.run.profiles=dev
```

## API check
```bash
curl -X POST http://127.0.0.1:8080/api/v1/add/users/reg \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"user@example.com\"}"
```

Expected response: created user id (JSON number), for example `1`.

## Docker
```bash
docker compose up --build -d
```

