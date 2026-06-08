# Badge System Documentation

## Overview
Sistem badge otomatis memberikan penghargaan kepada user berdasarkan aktivitas dan pencapaian mereka di platform.

## Architecture

### Models
- **Badge**: Model untuk menyimpan definisi badge
- **UserBadge**: Model untuk relasi antara User dan Badge

### Service
- **BadgeService**: Service yang menangani logika pemberian badge secara otomatis

### Observer
- **UserObserver**: Observer yang dipicu setiap kali user diupdate atau dibuat untuk mengecek eligibility badges

### Console Commands
- **AwardBadgesCommand**: Command untuk award badges ke semua users

## Database Schema

### Badges Table
```sql
- id (UUID)
- name (string, unique)
- description (text, nullable)
- icon_url (string, nullable)
- tier (bronze, silver, gold, platinum)
- condition_type (reputation_points, posts_count, answers_accepted, comments_count, bookmarks_count, followers_count)
- condition_value (integer)
- created_at (timestamp)
```

### User Badges Table
```sql
- id (UUID)
- badge_id (UUID, foreign key)
- user_id (UUID, foreign key)
- created_at (timestamp)
- updated_at (timestamp)
```

## Supported Badge Conditions

### 1. Reputation Points
- Diberikan ketika user mencapai jumlah reputation points tertentu
- Contoh: 500 points = Silver "Rising Star" badge

### 2. Posts Count
- Diberikan ketika user membuat jumlah posts tertentu
- Contoh: 1 post = Bronze "Newcomer" badge

### 3. Answers Accepted
- Diberikan ketika jawaban user diterima sebanyak jumlah tertentu
- Contoh: 1 jawaban diterima = Bronze "First Answer" badge

### 4. Comments Count
- Diberikan ketika user membuat komentar sebanyak jumlah tertentu

### 5. Bookmarks Count
- Diberikan ketika user membuat bookmark sebanyak jumlah tertentu

### 6. Followers Count
- Diberikan ketika user memiliki followers sebanyak jumlah tertentu

## API Endpoints

### Get All Badges
```
GET /api/badges
```

### Get User Badges
```
GET /api/users/{user_id}/badges
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "Newcomer",
      "description": "Berhasil membuat postingan pertama",
      "tier": "bronze",
      "condition_type": "posts_count",
      "condition_value": 1,
      "created_at": "2026-06-08T..."
    }
  ]
}
```

### Get Upcoming Badges
```
GET /api/users/{user_id}/badges/upcoming
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "badge": {
        "id": "uuid",
        "name": "Rising Star",
        "description": "Mengumpulkan 500 poin reputasi",
        "tier": "silver",
        "condition_type": "reputation_points",
        "condition_value": 500
      },
      "progress": {
        "current": 250,
        "required": 500,
        "percentage": 50
      }
    }
  ]
}
```

### Get Badge Details
```
GET /api/badges/{badge_id}
```

Response:
```json
{
  "success": true,
  "data": {
    "badge": {
      "id": "uuid",
      "name": "Newcomer",
      ...
    },
    "users": {
      "current_page": 1,
      "data": [
        {
          "id": "uuid",
          "username": "user1",
          ...
        }
      ],
      "total": 100
    }
  }
}
```

### Create Badge (Admin/Moderator only)
```
POST /api/badges
```

Request Body:
```json
{
  "name": "Badge Name",
  "description": "Badge description",
  "icon_url": "file",
  "tier": "bronze|silver|gold|platinum",
  "condition_type": "reputation_points|posts_count|answers_accepted|comments_count|bookmarks_count|followers_count",
  "condition_value": 10
}
```

### Update Badge (Admin/Moderator only)
```
POST /api/badges/{badge_id}
```

### Delete Badge (Admin/Moderator only)
```
DELETE /api/badges/{badge_id}
```

## Usage Examples

### Seeding Badges
Badges sudah di-seed melalui `BadgeSeeder`. Untuk menjalankannya:

```bash
php artisan db:seed --class=BadgeSeeder
```

### Award Badges Manually
```bash
php artisan badges:award
```

### Programmatically Award Badges
```php
use App\Services\BadgeService;
use App\Models\User;

$badgeService = app(BadgeService::class);
$user = User::find($userId);

// Award badges untuk seorang user
$badgeService->awardBadgesForUser($user);

// Award badges untuk semua users
$badgeService->awardBadgesForAllUsers();

// Get user's badges
$badges = $badgeService->getUserBadges($user);

// Get upcoming badges
$upcoming = $badgeService->getUpcomingBadges($user);
```

## How It Works

1. **On User Creation/Update**: Observer `UserObserver` otomatis dipicu
2. **Check Eligibility**: `BadgeService::awardBadgesForUser()` mengecek semua badges
3. **Compare Conditions**: Setiap badge di-check kondisinya dengan data user
4. **Award Badge**: Jika kondisi terpenuhi dan user belum memiliki badge, maka badge di-attach
5. **Prevent Duplicates**: User tidak bisa mendapatkan badge yang sama dua kali

## Tier System

- **Bronze**: Badges untuk pencapaian awal (dasar)
- **Silver**: Badges untuk pencapaian sedang
- **Gold**: Badges untuk pencapaian tinggi
- **Platinum**: Badges untuk pencapaian tertinggi

## Extending the System

### Adding New Condition Type
Edit `BadgeService::userMeetsCondition()`:

```php
private function userMeetsCondition(User $user, Badge $badge): bool
{
    return match ($badge->condition_type) {
        // ... existing conditions
        'new_condition_type' => $this->checkNewCondition($user, $badge),
        default => false,
    };
}

private function checkNewCondition(User $user, Badge $badge): bool
{
    // Your logic here
}
```

## Notes
- Badges diberikan otomatis ketika user memenuhi kondisi
- User tidak bisa kehilangan badge (permanent)
- Sistem di-design untuk scalability dengan chunk processing untuk batch operations
