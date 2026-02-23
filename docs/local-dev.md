# Local development

Goal: keep local dev simple. Backend can be edited directly and deployed by FTP if needed.

## PHP backend (local)
Requirements:
- PHP 8.5 (or 8.4 if 8.5 is not available locally)

Run locally:
```bash
cd backend
php -S 127.0.0.1:9000 -t public
```

Test:
- Open http://127.0.0.1:9000/ — should return JSON.

## FTP workflow (optional)
If you prefer editing via FTP:
- Use the `public/` folder as the web root.
- Upload `public/` and `src/` to your hosting.
- Keep local source in sync with Git to avoid drift.

## Production
Production deploy is handled by CI/CD with Docker. Local PHP server is only for development.
