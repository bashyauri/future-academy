# Leaderboard System — Implementation Strategy & Guide

This guide outlines a clean, scalable, non-cluttering implementation strategy for adding a **Leaderboard / Gamification System** across both the **Laravel 12 Web Application** and the **React Native (Expo) Mobile App**.

---

## 1. Core Philosophy: Zero-Clutter & Positive Gamification

A poorly implemented leaderboard clutters screen space and discourages average students. A great leaderboard:
1. **Never competes for primary navigation** (No 6th bottom tab on mobile).
2. **Always shows where the student stands** without making them scroll through hundreds of strangers.
3. **Resets periodically (Weekly/Monthly)** so new and active learners always have a fighting chance to reach the top.
4. **Protects student privacy** with anonymization toggles and friendly display names.

---

## 2. Leaderboard Types

| Leaderboard Type | Metric | Reset Cycle | Target Audience |
| :--- | :--- | :--- | :--- |
| **Weekly Mock Champions** | Highest score on completed JAMB/Mock exams | Every Monday 00:00 | Exam readiness & high achievers |
| **Study Streak Legends** | Active consecutive daily study streak | Ongoing (Real-time) | Consistency & daily retention |
| **Subject Masters** | Highest quiz accuracy in specific subjects (Math, English, etc.) | Monthly | Subject-specific mastery |

---

## 3. UI/UX Placement (Zero Clutter)

### Mobile App (`future-mobile`)
- **Location**: Inside the newly created [`future-mobile/src/app/progress.tsx`](file:///c:/laragon/www/future-academy/future-mobile/src/app/progress.tsx) screen.
- **Mechanism**: A sleek segmented switch at the very top:
  ```
  ┌────────────────────────────────────────────────────────┐
  │  [  📊 My Progress  ]   |   [  🏆 Leaderboard  ]       │
  └────────────────────────────────────────────────────────┘
  ```
- **Key UX Elements**:
  - **Top 3 Podium**: Elegant gold, silver, bronze cards for ranks 1, 2, and 3.
  - **Scrollable Ranks 4–50**: Lightweight list items showing rank, avatar/initials, name, and score.
  - **Sticky Personal Rank Bar**: Fixed at the bottom of the screen showing:
    `Your Rank: #14 • Score: 88% • Top 12% 🚀`
  - **Post-Exam Link**: When completing a mock exam in `mock/[id].tsx`, display a subtle link on the results screen: *"See where you rank on the weekly board →"*.

### Web Dashboard
- **Location**: Inside the Analytics dashboard (`/analytics`) as a secondary tab, or as a dedicated item under `/leaderboard`.
- **Layout**: Full-width container with filters:
  - Period: *This Week* | *This Month* | *All Time*
  - Category: *Overall Mock* | *JAMB Practice* | *Streak*

---

## 4. Database Architecture & Performance

Calculating rankings across thousands of `QuizAttempt` and `MockSession` rows on the fly will quickly slow down the database. Use a **cached snapshot table** or **scheduled aggregation**.

### Migration: `leaderboard_snapshots`
```php
Schema::create('leaderboard_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('type'); // 'weekly_mock', 'streak', 'subject_master'
    $table->string('period_key'); // '2026-W46', '2026-11', 'all_time'
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('rank');
    $table->decimal('score', 8, 2);
    $table->json('metadata')->nullable(); // e.g. time_taken, total_questions, subject_name
    $table->timestamps();

    $table->unique(['type', 'period_key', 'user_id']);
    $table->index(['type', 'period_key', 'rank']);
});
```

### User Privacy Setting
Add an optional column to `users`:
```php
$table->boolean('show_on_leaderboard')->default(true);
$table->string('leaderboard_nickname')->nullable(); // e.g. "Scholar-42" if anonymous
```

---

## 5. Backend Architecture (Laravel 12)

### 1. `LeaderboardService` (`app/Services/LeaderboardService.php`)
Responsible for calculating rankings and serving cached results:
```php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Get weekly mock exam leaderboard with the current user's position pinned.
     */
    public function getWeeklyMockLeaderboard(User $currentUser, int $limit = 50): array
    {
        $cacheKey = 'leaderboard:mock:weekly:' . now()->format('Y-W');

        $topRankings = Cache::remember($cacheKey, 300, function () use ($limit) {
            return DB::table('quiz_attempts')
                ->join('users', 'quiz_attempts.user_id', '=', 'users.id')
                ->where('quiz_attempts.status', 'completed')
                ->where('quiz_attempts.created_at', '>=', now()->startOfWeek())
                ->where('quiz_attempts.is_mock', true)
                ->where('users.is_active', true)
                ->select(
                    'users.id as user_id',
                    'users.name',
                    DB::raw('MAX(quiz_attempts.score_percentage) as top_score'),
                    DB::raw('MIN(quiz_attempts.time_taken_seconds) as fastest_time')
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('top_score')
                ->orderBy('fastest_time')
                ->limit($limit)
                ->get()
                ->map(function ($row, $index) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => $row->user_id,
                        'name' => $this->maskName($row->name),
                        'score' => round((float) $row->top_score, 1),
                    ];
                });
        });

        // Find current user position
        $currentUserRank = $this->getUserRank($currentUser->id, $topRankings);

        return [
            'period' => 'This Week',
            'resets_at' => now()->endOfWeek()->toIso8601String(),
            'top_list' => $topRankings,
            'current_user' => $currentUserRank,
        ];
    }

    private function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) > 1) {
            return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
        }
        return $parts[0];
    }

    private function getUserRank(int $userId, $topRankings): array
    {
        $match = collect($topRankings)->firstWhere('user_id', $userId);
        if ($match) {
            return $match;
        }

        // Optional: query individual rank if outside top 50
        return [
            'rank' => null,
            'message' => 'Complete a mock exam this week to enter the leaderboard!',
        ];
    }
}
```

### 2. API Controller & Route
Add to `routes/api.php`:
```php
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
```

---

## 6. Mobile Implementation Roadmap (`future-mobile`)

### Step 1: Add Tab State in `src/app/progress.tsx`
```tsx
const [activeTab, setActiveTab] = useState<'stats' | 'leaderboard'>('stats');

return (
  <View className="flex-1 bg-neutral-50 dark:bg-neutral-950">
    <Stack.Screen options={{ title: "Progress & Rankings" }} />
    
    {/* Segmented Control */}
    <View className="flex-row mx-4 mt-3 p-1 bg-neutral-200 dark:bg-neutral-800 rounded-xl">
      <TouchableOpacity 
        onPress={() => setActiveTab('stats')}
        className={`flex-1 py-2 rounded-lg items-center ${activeTab === 'stats' ? 'bg-white dark:bg-neutral-900 shadow-sm' : ''}`}
      >
        <BodyText className={activeTab === 'stats' ? 'font-bold text-primary-600' : 'text-neutral-500'}>
          My Stats
        </BodyText>
      </TouchableOpacity>

      <TouchableOpacity 
        onPress={() => setActiveTab('leaderboard')}
        className={`flex-1 py-2 rounded-lg items-center ${activeTab === 'leaderboard' ? 'bg-white dark:bg-neutral-900 shadow-sm' : ''}`}
      >
        <BodyText className={activeTab === 'leaderboard' ? 'font-bold text-primary-600' : 'text-neutral-500'}>
          Leaderboard 🏆
        </BodyText>
      </TouchableOpacity>
    </View>

    {activeTab === 'stats' ? (
      <MyStatsScrollView ... />
    ) : (
      <LeaderboardView ... />
    )}
  </View>
);
```

### Step 2: Component Structure
- `LeaderboardPodium`: Top 3 ranks with avatars and badges (1st: 🥇 Gold, 2nd: 🥈 Silver, 3rd: 🥉 Bronze).
- `LeaderboardRow`: Ranks 4+ with rank number, masked name (e.g., `Amina K.`), and score.
- `StickyUserRankFooter`: Floating footer bar when the user's position is not on screen.

---

## 7. Rollout Checklist

- [ ] **Phase 1: Database & Caching**
  - Add optional `show_on_leaderboard` flag to `users` table.
  - Implement `LeaderboardService` with 5-minute Redis/File caching to prevent database overload.
- [ ] **Phase 2: Backend API**
  - Create `app/Http/Controllers/Api/LeaderboardController.php`.
  - Expose `GET /api/v1/leaderboard` with filtering by period (`weekly`) and type (`mock`, `streak`).
  - Write Pest tests in `tests/Feature/LeaderboardTest.php`.
- [ ] **Phase 3: Mobile UI**
  - Add segmented control (`My Stats` | `Leaderboard`) to `future-mobile/src/app/progress.tsx`.
  - Implement top 3 podium card and scrollable rank list.
- [ ] **Phase 4: Web UI**
  - Add a Livewire component `App\Livewire\Dashboard\Leaderboard` or embed a toggle inside `/analytics`.
