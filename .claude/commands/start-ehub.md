# Start eHub Locally

Start all 4 eHub services for local development.

## Prerequisites

- WAMP running (MySQL on port 3306, root with no password)
- Node.js installed
- PHP in PATH

## Startup sequence

Run these steps in order:

### 1. Ensure databases exist

```powershell
$mysql = "C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe"
& $mysql -u root -e "CREATE DATABASE IF NOT EXISTS ehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS ehub_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Run pending migrations

```powershell
cd "D:\OneDrive\Git\ehub-api"     ; php artisan migrate
cd "D:\OneDrive\Git\ehub-website"  ; php artisan migrate
```

### 3. Start services (background, each in its own terminal or as background jobs)

```powershell
# WebSocket — port 3001 (start first, API depends on it)
Start-Process powershell -ArgumentList '-NoExit', '-Command', 'cd "D:\OneDrive\Git\ehub-websocket"; node server.js'

# API — port 8000
Start-Process powershell -ArgumentList '-NoExit', '-Command', 'cd "D:\OneDrive\Git\ehub-api"; php artisan serve --port=8000'

# Website — port 8001 (different from API to avoid conflict)
Start-Process powershell -ArgumentList '-NoExit', '-Command', 'cd "D:\OneDrive\Git\ehub-website"; php artisan serve --port=8001'

# Viewer (Vue SPA) — port 5173
Start-Process powershell -ArgumentList '-NoExit', '-Command', 'cd "D:\OneDrive\Git\ehub-viewer"; npm run dev'
```

## Service map

| Service     | URL                        | Project path                        |
|-------------|----------------------------|-------------------------------------|
| API         | http://127.0.0.1:8000      | D:\OneDrive\Git\ehub-api            |
| Website     | http://127.0.0.1:8001      | D:\OneDrive\Git\ehub-website        |
| WebSocket   | http://127.0.0.1:3001      | D:\OneDrive\Git\ehub-websocket      |
| Viewer      | http://localhost:5173       | D:\OneDrive\Git\ehub-viewer         |

## Notes

- Viewer connects to API at `:8000` (hardcoded in `src/helpers/General/SystemVars.js`)
- Viewer connects to WebSocket at `:3001` (hardcoded in `src/helpers/communication/Socket.js`)
- EPERM error on Vite startup (OneDrive lock on `.vite/deps_temp_*`) is harmless — server still starts
- WAMP MySQL binary: `C:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe`
