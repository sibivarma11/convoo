# 💬 Real-Time Chat Backend — Laravel + Reverb

A production-ready Laravel backend for a real-time chat application supporting chat rooms, text/emoji messages, image/audio/video sharing, typing indicators, reactions, and read receipts.

---

## 🏗 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Real-time | Laravel Reverb (WebSockets) |
| Authentication | Laravel Sanctum (token-based) |
| Queue | Redis + Laravel Queues |
| Storage | AWS S3 / Cloudflare R2 |
| Media Processing | FFmpeg via php-ffmpeg |
| Database | MySQL / PostgreSQL |
| Cache | Redis |

---

## ⚡ Quick Start

### 1. Install dependencies

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure your `.env`

Fill in your database, Redis, S3, and Reverb credentials (see `.env.example`).

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Install & start Reverb (WebSocket server)

```bash
php artisan reverb:install   # first time only
php artisan reverb:start
```

### 5. Start the queue worker (for media processing)

```bash
php artisan queue:work redis --queue=default --tries=3
```

### 6. Start the API server

```bash
php artisan serve
# or use Nginx/Apache in production
```

---

## 📡 API Reference

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login, returns Sanctum token |
| POST | `/api/auth/logout` | Logout (revoke token) |
| GET | `/api/auth/me` | Get authenticated user |
| POST | `/api/auth/profile` | Update name / avatar |

All protected routes require:
```
Authorization: Bearer {token}
```

### Rooms

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/rooms` | List public rooms + joined rooms |
| POST | `/api/rooms` | Create a room |
| GET | `/api/rooms/{room}` | Room details |
| PUT | `/api/rooms/{room}` | Update room (admin only) |
| DELETE | `/api/rooms/{room}` | Delete room (owner only) |
| POST | `/api/rooms/{room}/join` | Join a public room |
| POST | `/api/rooms/{room}/leave` | Leave a room |
| POST | `/api/rooms/{room}/invite` | Invite user (admin only) |
| DELETE | `/api/rooms/{room}/kick/{user}` | Kick member (admin only) |
| GET | `/api/rooms/{room}/members` | List room members |

### Messages

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/rooms/{room}/messages` | Paginated message history |
| POST | `/api/rooms/{room}/messages` | Send a text/emoji message |
| PUT | `/api/rooms/{room}/messages/{message}` | Edit a message |
| DELETE | `/api/rooms/{room}/messages/{message}` | Delete a message |
| POST | `/api/rooms/{room}/messages/{message}/react` | Add/remove emoji reaction |
| POST | `/api/rooms/{room}/typing` | Typing indicator |
| POST | `/api/rooms/{room}/read` | Mark all messages as read |

### Media

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/rooms/{room}/media` | Upload image/audio/video/file |
| POST | `/api/media/presign` | Get pre-signed S3 URL |

---

## 🔌 WebSocket Events (React Echo)

Connect using **Laravel Echo** with the Reverb driver.

```js
import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: true,
    authEndpoint: '/api/broadcasting/auth',
});
```

### Joining a room presence channel

```js
Echo.join(`room.${roomId}`)
    .here((users) => setOnlineUsers(users))          // initial online list
    .joining((user) => addUser(user))                // user came online
    .leaving((user) => removeUser(user))             // user went offline
    .listen('.message.sent', (e) => addMessage(e))  // new message
    .listen('.message.reacted', (e) => updateReactions(e))
    .listen('.user.typing', (e) => showTyping(e));
```

### Event Payloads

**`.message.sent`**
```json
{
  "id": 42,
  "room_id": 1,
  "type": "text",
  "content": "Hello world!",
  "media_url": null,
  "media_duration": null,
  "media_thumbnail": null,
  "reactions": {},
  "reply_to": null,
  "user": { "id": 7, "name": "Alice", "avatar": "https://..." },
  "created_at": "2024-01-15T10:30:00.000Z"
}
```

**`.message.reacted`**
```json
{
  "message_id": 42,
  "emoji": "👍",
  "action": "added",
  "reactions": { "👍": [7, 12] },
  "user": { "id": 7, "name": "Alice" }
}
```

**`.user.typing`**
```json
{
  "user_id": 7,
  "user_name": "Alice",
  "is_typing": true,
  "room_id": 1
}
```

---

## 📁 Project Structure

```
app/
├── Events/
│   ├── MessageSent.php       ← Broadcast on new/edited message
│   ├── MessageReacted.php    ← Broadcast on emoji reaction
│   └── UserTyping.php        ← Broadcast typing state
├── Http/Controllers/
│   ├── AuthController.php    ← Register, login, profile
│   ├── RoomController.php    ← CRUD rooms, membership
│   ├── MessageController.php ← Send, edit, delete, react, typing
│   └── MediaController.php   ← Upload media, presigned URLs
├── Jobs/
│   └── ProcessMedia.php      ← Video thumbnails, audio duration (queued)
└── Models/
    ├── User.php
    ├── Room.php
    ├── RoomMember.php
    └── Message.php
```

---

## 🚀 Production Deployment

### Supervisor config (keep Reverb + queue workers alive)

```ini
[program:reverb]
command=php /var/www/chat/artisan reverb:start
autostart=true
autorestart=true
user=www-data

[program:queue-worker]
command=php /var/www/chat/artisan queue:work redis --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
user=www-data
```

### Optimize for production

```bash
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

---

## 📦 Key Packages

- [`laravel/reverb`](https://reverb.laravel.com) — WebSocket server
- [`laravel/sanctum`](https://laravel.com/docs/sanctum) — API token auth
- [`php-ffmpeg/php-ffmpeg`](https://github.com/PHP-FFMpeg/PHP-FFMpeg) — Video/audio processing
- [`league/flysystem-aws-s3-v3`](https://flysystem.thephpleague.com) — S3 integration