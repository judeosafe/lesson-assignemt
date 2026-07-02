# Laravel Reverb WebSocket Server Setup in Docker

This document describes how Laravel Reverb is set up and run inside the Docker-based containerized environment to handle real-time notifications for task comments.

---

## 🚀 Reverb Port Configuration (Docker Compose)
To allow WebSocket connections from the client (browser running on the host machine) to reach the Reverb server running inside the Docker `app` container, port `8080` is exposed:

### `docker-compose.yml`
```yaml
  app:
    # ...
    ports:
      - "8000:80"      # Web/API traffic
      - "8080:8080"    # WebSocket traffic (Reverb)
```

---

## ⚡ Starting Reverb Server
Because Reverb is running inside the `app` container, it must be started as a background process inside the container. 

Run the following command on your host machine to start it:
```bash
docker compose exec -d app php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Options Breakdown:
* `-d` (Docker Compose): Runs the command in detached (background) mode.
* `--host=0.0.0.0`: Ensures the server listens to all network interfaces inside the container, allowing external traffic (routed through the Docker port map) to be accepted.
* `--port=8080`: Defines the port Reverb runs on.

---

## 🔧 Verification
To check if the Reverb server is running inside the `app` container, execute:
```bash
docker compose exec app ps aux
```

You should see a line indicating `php artisan reverb:start` is running:
```text
root        22  php artisan reverb:start --host=0.0.0.0 --port=8080
```

---

## 🌐 Environment Variables Configuration (`.env`)
The Reverb server connection variables are configured in `.env` as follows:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=677814
REVERB_APP_KEY=kbgy4qotbvdn4mgfefiq
REVERB_APP_SECRET=tllot4ehyxidfch83lxk
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```
* **Backend Communication:** The PHP backend broadcasts notifications by connecting directly to `localhost:8080` (since it executes inside the same container).
* **Frontend Communication:** The Vue app running in the browser connects to `localhost:8080` (which is mapped to the container via `docker-compose.yml`).
