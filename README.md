# 🔍 OSINT Masters Backend

![PHP](https://img.shields.io/badge/PHP-90.4%25-777BB4)
![Blade](https://img.shields.io/badge/Blade-6.2%25-F7523F)
![TypeScript](https://img.shields.io/badge/TypeScript-1.9%25-3178C6)
![Laravel](https://img.shields.io/badge/Laravel-8.x-FF2D20)
![License](https://img.shields.io/badge/License-MIT-green)

A robust Laravel-based backend system for the OSINT Masters platform, providing comprehensive API services for user management, content delivery, and real-time communications.

## 🌟 Key Features

### 👥 User Management & Authentication
- **Secure Authentication**
  - Laravel Sanctum implementation
  - Token-based API authentication
  - User session management
  - Role-based authorization
  - Admin privileges system

### 📊 Dashboard & Analytics
- **User Dashboard**
  - Activity tracking
  - Statistics overview
  - Recent activities monitoring
  - Performance metrics

### 📰 Content Management
- **News System**
  - Article publishing
  - Latest news feed
  - Content moderation
  - Administrative controls

### 📊 Polling & Voting System
- **Interactive Polls**
  - Active polls management
  - User responses tracking
  - Results compilation
  - Vote counting system
  - Poll activation toggling

### 🤝 Meeting Management
- **Meeting Organization**
  - Upcoming meetings display
  - Meeting scheduling
  - Participant management
  - Meeting updates and deletion

### 💬 Chat & Messaging
- **Real-time Communication**
  - Message streaming
  - Chat history
  - Real-time updates
  - User-to-user messaging
  - Chat room management

### 📚 Material Management
- **Resource Handling**
  - File uploads
  - Material streaming
  - Resource organization
  - Access control
  - File type support

## 🛠️ Technical Stack

### Core Technologies
- **Framework**: Laravel 8.x
- **Language**: PHP ^7.3|^8.0
- **Database**: MySQL
- **Cache**: Redis support
- **Queue**: Laravel Queue

### Key Packages
- **PDF Processing**
  - barryvdh/laravel-dompdf
  - carlos-meneses/laravel-mpdf
- **Real-time**
  - pusher/pusher-php-server
  - ably/ably-php-laravel
- **Image Processing**
  - intervention/image
- **Authentication**
  - laravel/sanctum
  - laravel/ui

## 🚀 Getting Started

### Prerequisites
- PHP >= 7.3
- Composer
- MySQL
- Redis (optional)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/prewcio/osintmasters_backend.git
```

2. Install dependencies:
```bash
composer install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Run migrations:
```bash
php artisan migrate
```

## 📡 API Endpoints

### Authentication
```
POST   /api/login              - User login
POST   /api/logout             - User logout
GET    /api/user              - Get current user
```

### Dashboard
```
GET    /api/dashboard         - Get dashboard data
GET    /api/admin/statistics  - Get admin statistics
```

### News Management
```
GET    /api/news              - List news
GET    /api/news/latest       - Get latest news
POST   /api/admin/news        - Create news (admin)
PUT    /api/admin/news/{id}   - Update news (admin)
```

### Polls & Voting
```
GET    /api/polls/active      - Get active polls
POST   /api/polls/{id}/respond - Submit poll response
GET    /api/votes/active      - Get active votes
```

### Meetings
```
GET    /api/meetings/upcoming - Get upcoming meetings
POST   /api/meetings         - Create meeting
PUT    /api/meetings/{id}    - Update meeting
```

### Chat System
```
GET    /api/chat/messages    - Get chat messages
POST   /api/chat/message     - Send message
GET    /api/chat/stream      - Stream chat messages
```

## ⚙️ Configuration

### Essential Environment Variables
```env
APP_NAME=OSINTMasters
APP_ENV=production
APP_DEBUG=false
APP_URL=your_url

BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret

ABLY_KEY=your_ably_key
```

## 🔒 Security Features

- Sanctum authentication
- CORS protection
- Rate limiting
- Input validation
- XSS protection
- CSRF protection

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

## 👤 Developer

**Prewcio**
- Website: [prewcio.dev](https://prewcio.dev)
- Email: [this.prewcio@gmail.com](mailto:this.prewcio@gmail.com)
- GitHub: [@prewcio](https://github.com/prewcio)

## 📄 License

This project is licensed under the MIT License.

---

*Last Updated: 2025-02-28 22:49:55 UTC*  
*Author: @prewcio*
