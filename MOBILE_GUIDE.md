# Future Academy: Mobile App Development & Integration Guide

This guide is a practical, MVP-first plan for building the mobile app against the current Laravel API. It keeps the first release focused on the pieces that matter most: auth, profile, downloads, offline practice, sync, and documentation.

## Recommended Build Order

1. Auth, Profile, and Onboarding
2. Subject download and offline caching
3. Single-subject practice player
4. JAMB and mock flows
5. Background sync, analytics/streaks dashboard, and polish
6. Scribe documentation driven by real database content

---

## 🎨 Web UI Parity & Design Guidelines

To match the look and feel of the web version (built with TailwindCSS v4 and Livewire Flux UI), the mobile app should adhere to the following design system tokens:

### 1. Color Palette (Neutral Zinc & Indigo Accents)
- **Primary / Ambient Background**: Clean `#ffffff` (light mode) or `#0a0a0a` (dark mode).
- **Secondary Card/List background**: Neutral Zinc-50 (`#fafafa`) or Zinc-900 (`#171717`).
- **Borders & Dividers**: Slate/Zinc-200 (`#e5e5e5`) or Zinc-800 (`#262626`).
- **Text Color**: Zinc-900 (`#171717`) or Zinc-50 (`#fafafa`).
- **Primary Buttons & Active State Accents**: Indigo-600 (`#4f46e5`) or custom dark accents like `#171717` (light) and `#ffffff` (dark).
- **Alert / Timer States**: 
  - Green (Success/Safe): `#10b981` (Emerald-500)
  - Amber (Warning): `#f59e0b` (Amber-500)
  - Red (Danger/Expired): `#ef4444` (Red-500)

### 2. Typography
- The web app uses **Instrument Sans** (a modern geometric sans-serif).
- On mobile, import and load `'InstrumentSans-Regular'` and `'InstrumentSans-Bold'` from Google Fonts using `expo-font` or fallback to system sans-serif (`System`, `ui-sans-serif`).
- Avoid standard default Android/iOS font weights; use clean headings with tracking/letter-spacing adjusted slightly closer for large titles.

### 3. Flux-Style Component Architecture
- **Buttons**: Rounded-lg (8px border-radius), medium/semi-bold weight, solid background with subtle micro-elevation or flat layout.
- **Inputs**: Flat, bordered inputs with subtle gray borders (`#ddd`) and high-contrast focus rings (indigo/dark).
- **Badges**: Rounded-full, high-padding, low-opacity backgrounds with high-contrast text.
- **Separators**: 1px thin dividers (`#e5e5e5` or `#262626`) for neat, flat modular design.

### 4. Mobile UI/UX Adaptations (Viewport Adjustments)
To preserve features while maintaining excellent mobile UX, the following layout adaptations are recommended:
- **Practice Player (Single Subject)**:
  * *Web layout*: Split-pane (left column question text, right column options/explanation).
  * *Mobile adaptation*: Single vertical column scroll. The question text/image rests at the top (maximum 40% height, scrollable if long), followed by options. Navigation controls ("Previous", "Next", "Submit") should live on a sticky bottom bar for easy thumb access.
- **JAMB Player (Multi-Subject Tabs)**:
  * *Web layout*: Side-by-side or large tabs for switching subjects.
  * *Mobile adaptation*: Horizontal-scrolling tab bar at the top (e.g., Mathematics | English | Biology | Chemistry). Active tab highlighted in Indigo, with swipe gestures allowed to switch subjects.
- **Mock Exam Player**:
  * *Web layout*: Large quiz grid showing status of all questions simultaneously.
  * *Mobile adaptation*: Replace the permanent side-grid with a bottom sheet drawer. Clicking "View Questions Grid" opens a slide-up drawer showing the status (unanswered, answered, flagged) of all questions, keeping the main screen distraction-free.
  * *Timer*: Keep a small, floating countdown timer centered at the top header that turns red on critical time limits.

---

## 📱 Phase 0: Creating the Mobile App From Scratch

> **Complete this phase first.** Everything in Phases 1–6 assumes the app already exists.

### Prerequisites (Install Once on Your Machine)

| Tool | Where to Get It |
|---|---|
| **Node.js 20+ LTS** | https://nodejs.org |
| **Git** | https://git-scm.com |
| **VS Code** | https://code.visualstudio.com |
| **Expo Go** (Android) | Google Play Store on your test phone |

Verify Node is ready:
```bash
node -v   # v20.x or higher
npm -v    # 10.x or higher
```

---

### Step 1: Create the Expo App

Run this **outside** your Laravel project folder — e.g., in `C:\laragon\www\`:

```bash
npx create-expo-app@latest future-academy-mobile --template blank-typescript
cd future-academy-mobile
```

This creates a `future-academy-mobile/` folder with TypeScript support already configured.

---

### Step 2: Install Expo Router (File-Based Navigation)

Expo Router makes each file in `app/` a screen route — just like Next.js pages:

```bash
npx expo install expo-router react-native-safe-area-context react-native-screens expo-linking expo-constants expo-status-bar
```

Update `package.json` — set the main entry:
```json
{
  "main": "expo-router/entry"
}
```

Update `app.json` — add scheme for deep linking:
```json
{
  "expo": {
    "scheme": "futureacademy",
    "web": { "bundler": "metro" }
  }
}
```

---

### Step 3: Create the App Folder Structure

Run inside `future-academy-mobile/`:

```bash
mkdir -p app/(auth) app/(tabs) app/practice app/mock app/lessons lib
```

Then create the core screen files (Windows PowerShell):

```powershell
# Layouts
New-Item app/_layout.tsx -Force
New-Item "app/(auth)/_layout.tsx" -Force
New-Item "app/(tabs)/_layout.tsx" -Force

# Auth
New-Item "app/(auth)/login.tsx" -Force

# Main tabs
New-Item "app/(tabs)/index.tsx" -Force
New-Item "app/(tabs)/practice-setup.tsx" -Force
New-Item "app/(tabs)/jamb-setup.tsx" -Force
New-Item "app/(tabs)/mock-setup.tsx" -Force
New-Item "app/(tabs)/settings.tsx" -Force

# Quiz players
New-Item app/practice/single-quiz.tsx -Force
New-Item app/practice/jamb-quiz.tsx -Force
New-Item app/mock/mock-quiz.tsx -Force
New-Item app/lessons/video.tsx -Force
```

---

### Step 4: Configure the API Base URL

Create `lib/api.ts` inside your mobile project:

```typescript
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

// Local dev:  replace with your machine's local IP (run `ipconfig` in CMD)
// Production: your Railway/VPS URL
const API_BASE_URL = __DEV__
  ? 'http://192.168.x.x/future-academy/public/api/v1'
  : 'https://your-production-domain.com/api/v1';

const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15000, // 15 s — generous for Nigerian 3G/4G
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

// Attach the Sanctum token to every request automatically
api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

> **Finding your local IP:** Open Command Prompt → type `ipconfig` → look for **IPv4 Address** under your Wi-Fi adapter. Your phone and laptop must be on the same Wi-Fi network.

---

### Step 5: Run the App on Your Phone

```bash
npx expo start
```

A **QR code** appears in the terminal.

1. Open **Expo Go** on your Android phone.
2. Tap **"Scan QR Code"** and scan the terminal QR code.
3. The app loads live on your phone — changes save instantly.

> **Can't connect?** Make sure your phone and laptop share the same Wi-Fi. Check that Windows Firewall isn't blocking port `8081`. As a workaround, press `t` in the terminal to switch to **tunnel** mode (uses Expo's cloud relay — slower but works on any network).

---

### Step 6: Build a Shareable APK (No Play Store Needed)

Perfect for sharing with the client or testers via WhatsApp:

```bash
# Install EAS CLI once globally
npm install -g eas-cli

# Login to your Expo account (create one free at expo.dev)
eas login

# Configure your project for EAS builds
eas build:configure

# Build an APK (Android only, internal distribution)
eas build --platform android --profile preview
```

EAS builds in the cloud and gives you a **download link** for the `.apk`. Send it via WhatsApp — testers install it by enabling "Install from unknown sources" on their Android phone.

> For **Play Store submission**, use `--profile production` to generate an `.aab` bundle instead.

---

### Step 7: Test Login Against Your Local Laravel API

With Laragon running and the mobile app on your phone:

```
POST http://192.168.1.x/future-academy/public/api/v1/login
Content-Type: application/json

{
  "email": "student@futureacademy.com",
  "password": "your-password",
  "device_name": "Tecno Spark 10"
}
```

Expected response:
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Bashir",
    "email": "student@futureacademy.com",
    "account_type": "student",
    "avatar": null,
    "has_completed_onboarding": true,
    "role": "student",
    "on_trial": false,
    "has_active_subscription": true
  }
}
```

Store the `token` securely using `expo-secure-store` and attach it to all future requests.

---

## 🛠️ Phase 1: Package Installations

### 📱 1. Expo Mobile Frontend
Run these commands in your mobile project directory:

- [ ] **Core Mobile Navigation**
  ```bash
  npx expo install expo-router react-native-safe-area-context react-native-screens
  ```
- [ ] **Data Storage & Device Cache**
  ```bash
  npx expo install expo-sqlite expo-secure-store expo-file-system
  ```
- [ ] **Video Player & Networking**
  ```bash
  npx expo install expo-video axios
  ```
- [ ] **Query Caching & Auto-Sync State**
  ```bash
  npm install @tanstack/react-query @tanstack/react-query-persist-client-ext-sqlite
  ```
- [ ] **Expo Application Services (EAS) for Building & Deployment**
  ```bash
  npm install -g eas-cli
  eas build:configure
  ```
- [ ] **Analytics & Crash Reporting**
  ```bash
  npx expo install @react-native-firebase/app @react-native-firebase/analytics
  npm install @sentry/react-native
  npx sentry-wizard -i react-native -p android
  npx sentry-wizard -i react-native -p ios
  ```
- [ ] **Error Boundaries & Network Resilience**
  ```bash
  npm install react-error-boundary @react-native-community/netinfo
  ```
- [ ] **Tailwind Styling (NativeWind v4)**
  ```bash
  npm install nativewind@^4.0.0-beta.0 tailwindcss@^3.4.0 postcss@^8.0.0
  ```

---

### 💻 2. Laravel Backend
Run these commands in your Laravel backend directory:

- [ ] **API Authentication** (Sanctum is already in `composer.json`).
- [ ] **Interactive API Documentation & Postman Generator**
  ```bash
  composer require knuckleswtf/scribe --dev
  ```

---

## 🔧 Phase 1.5: Production-Ready Configuration

### NativeWind (Tailwind CSS) Configuration

1. **Configure Tailwind**: Run `npx tailwindcss init` and edit the resulting `tailwind.config.js` to scan the `app` folder:
```javascript
module.exports = {
  content: ["./app/**/*.{js,jsx,ts,tsx}", "./components/**/*.{js,jsx,ts,tsx}"],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        accent: '#171717',
      },
    },
  },
  plugins: [],
}
```

2. **Configure Babel Plugin**: Update `babel.config.js` to include the NativeWind babel plugin:
```javascript
module.exports = function (api) {
  api.cache(true);
  return {
    presets: [
      ["babel-preset-expo", { jsxImportSource: "nativewind" }],
      "nativewind/babel",
    ],
  };
};
```

3. **Global Stylesheet**: Create `global.css` at root and add:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

4. **Import Global Styles**: In `app/_layout.tsx` root layout, import the styles:
```typescript
import "../global.css";
```

### EAS Build Configuration
Create `eas.json` in your mobile project root:

```json
{
  "cli": {
    "version": ">= 5.2.0"
  },
  "build": {
    "development": {
      "developmentClient": true,
      "distribution": "internal"
    },
    "preview": {
      "distribution": "internal",
      "android": {
        "buildType": "apk"
      }
    },
    "production": {
      "android": {
        "buildType": "app-bundle"
      },
      "ios": {
        "autoIncrement": true
      }
    }
  },
  "submit": {
    "production": {}
  }
}
```

### Expo Updates Configuration
Configure over-the-air updates in `app.json`:

```json
{
  "expo": {
    "updates": {
      "url": "https://u.expo.dev/YOUR_PROJECT_ID"
    },
    "runtimeVersion": {
      "policy": "appVersion"
    }
  }
}
```

Enable updates in your app entry point:

```typescript
import * as Updates from 'expo-updates';

// Check for updates on app start
async function checkForUpdates() {
  try {
    const update = await Updates.checkForUpdateAsync();
    if (update.isAvailable) {
      await Updates.fetchUpdateAsync();
      await Updates.reloadAsync();
    }
  } catch (error) {
    console.log('Error checking for updates:', error);
  }
}
```

### Firebase Analytics Setup
1. Create Firebase project at console.firebase.google.com
2. Add Android/iOS apps and download config files
3. Place `google-services.json` (Android) and `GoogleService-Info.plist` (iOS) in appropriate folders
4. Initialize analytics in your app:

```typescript
import analytics from '@react-native-firebase/analytics';

// Track screen views
await analytics().logScreenView({
  screen_name: 'Practice Quiz',
  screen_class: 'PracticeQuizScreen'
});

// Track quiz completion
await analytics().logEvent('quiz_completed', {
  subject_id: subjectId,
  score: score,
  time_taken: timeTaken
});
```

### Sentry Crash Reporting
Configure Sentry in `app.json`:

```json
{
  "expo": {
    "plugins": [
      [
        "sentry-expo",
        {
          "organization": "your-org",
          "project": "future-academy-mobile"
        }
      ]
    ]
  }
}
```

Initialize Sentry in your app:

```typescript
import * as Sentry from '@sentry/react-native';

Sentry.init({
  dsn: 'YOUR_SENTRY_DSN',
  tracesSampleRate: 1.0,
  environment: __DEV__ ? 'development' : 'production',
});
```

### Error Boundaries & Network Resilience
Create a global error boundary component:

```typescript
import { ErrorBoundary } from 'react-error-boundary';
import NetInfo from '@react-native-community/netinfo';
import * as Updates from 'expo-updates';

function ErrorFallback({ error }: { error: Error }) {
  return (
    <View style={styles.container}>
      <Text>Something went wrong!</Text>
      <Text>{error.message}</Text>
      <Button onPress={() => Updates.reloadAsync()} title="Retry" />
    </View>
  );
}

// Wrap your app
<ErrorBoundary FallbackComponent={ErrorFallback}>
  <App />
</ErrorBoundary>

// Network monitoring
NetInfo.addEventListener(state => {
  if (!state.isConnected) {
    // Show offline warning
  }
});
```

---

## 📂 Phase 2: Directory & File Structure (To Create)

Verify and create the following directories and files on both sides of the codebase:

### 💻 Backend (Laravel API structure)
```text
app/
└── Http/
    └── Controllers/
        └── Api/                      
            ├── AuthController.php            <-- (Login/Profile API)
            ├── SubjectDownloadController.php <-- (Curriculum & Question download with optional years)
            ├── VideoDownloadController.php   <-- (Bunny CDN secure token generator)
            ├── MockExamController.php        <-- (Mock setup, batches & multi-subject sessions)
            └── SyncController.php            <-- (Single, Multi-subject, and Mock attempt syncer)
routes/
└── api.php                           <-- (Register mobile prefix routes)
```

### 📱 Frontend (Expo app structure)
```text
future-academy-mobile/
├── app/                              
│   ├── (auth)/
│   │   ├── login.tsx                 
│   │   └── onboarding.tsx            <-- (Stream & subject selection onboarding flow)
│   ├── (tabs)/
│   │   ├── index.tsx                 <-- (Syllabus/Subject index & Streaks dashboard)
│   │   ├── practice-setup.tsx        <-- (Practice: Subject + Optional Year Selection)
│   │   ├── jamb-setup.tsx            <-- (JAMB Setup: 4 Subjects + Optional Year Selection)
│   │   ├── mock-setup.tsx            <-- (Mock Setup: Single/Multi-subject setup)
│   │   ├── mock-groups.tsx           <-- (Batch/Mock group selector for single subject mocks)
│   │   └── settings.tsx              
│   ├── practice/
│   │   ├── single-quiz.tsx           <-- (Single subject practice player)
│   │   └── jamb-quiz.tsx             <-- (Multi-subject JAMB practice player)
│   ├── mock/
│   │   └── mock-quiz.tsx             <-- (Mock exam player - timed, anti-cheat, session-backed)
│   └── lessons/
│       └── video.tsx                 
└── lib/
    └── api.ts                        <-- (Axios instance with Sanctum token interceptor)
```

---

## 🗄️ Phase 3: SQLite Database Schema (Mobile App)

Create these tables in the local Expo database on mobile start. Notice the additions for mock groups and attempts:

```sql
-- 1. Table to store downloaded questions
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY,
    subject_id INTEGER NOT NULL,
    topic_id INTEGER,
    exam_type_id INTEGER,
    exam_year INTEGER, -- e.g. 2024, or NULL
    question_text TEXT NOT NULL,
    question_image TEXT,
    difficulty TEXT,
    explanation TEXT,
    is_mock INTEGER DEFAULT 0, -- 1 if mock question, 0 otherwise
    mock_group_id INTEGER -- References mock group if applicable
);

-- 2. Table to store choices/options for questions
CREATE TABLE IF NOT EXISTS options (
    id INTEGER PRIMARY KEY,
    question_id INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    option_image TEXT,
    is_correct INTEGER DEFAULT 0,
    FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- 3. Table to record user quiz attempts offline (Single, Multi-subject, or Mock Group)
CREATE TABLE IF NOT EXISTS offline_attempts (
    uuid TEXT PRIMARY KEY, 
    user_id INTEGER NOT NULL,
    exam_type_id INTEGER, -- e.g. JAMB, WAEC, NECO
    subject_id INTEGER, -- NULL for multi-subject JAMB / Mock sessions
    mock_group_id INTEGER, -- References the local/remote MockGroup if a single subject mock
    exam_year INTEGER, -- Selected exam year (or NULL for "All Years")
    status TEXT DEFAULT 'in_progress', -- 'in_progress', 'completed'
    started_at TEXT NOT NULL,
    completed_at TEXT,
    time_taken_seconds INTEGER DEFAULT 0,
    total_questions INTEGER DEFAULT 0,
    correct_answers INTEGER DEFAULT 0,
    question_order TEXT NOT NULL, -- JSON String (flat array [1,2,3] OR key-value mapping {"1":[12,15],"3":[42,45]})
    is_synced INTEGER DEFAULT 0
);

-- 4. Table to record answers selected by the student
CREATE TABLE IF NOT EXISTS offline_answers (
    attempt_uuid TEXT NOT NULL,
    question_id INTEGER NOT NULL,
    selected_option_id INTEGER,
    is_correct INTEGER DEFAULT 0,
    PRIMARY KEY (attempt_uuid, question_id),
    FOREIGN KEY(attempt_uuid) REFERENCES offline_attempts(uuid) ON DELETE CASCADE
);
```

---

## 🌐 Phase 4: APIs to Create (Aligned with Web Logic)

### ✅ Phase 4.1: Question Download APIs (Milestone 2 — COMPLETE)

### 1. `GET /api/v1/subjects/{id}/download`
Downloads single subject question packages for practice.
*   **Query Parameters**: `year` (optional).

### 2. `GET /api/v1/jamb/download`
Downloads questions for 4 selected subjects at once for JAMB practice.
*   **Query Parameters**: `subjects` (comma-separated), `year` (optional).

### 🟡 Phase 4.2: Configuration APIs (Milestone 5 — PENDING)

### 3. `GET /api/v1/subjects`
Lists all active subjects with metadata (id, name, code, slug, icon, color).
*   **Response**: Array of subject objects.

### 4. `GET /api/v1/exam-types`
Lists all exam types (JAMB, WAEC, NECO) with IDs.
*   **Response**: Array of exam type objects.

### 5. `GET /api/v1/years`
Lists available years for filtering questions.
*   **Response**: Array of year integers (e.g., [2020, 2021, 2022, 2023, 2024]).

### 6. `GET /api/v1/config/mock-formats`
Returns mock exam configuration from `config/mock.php` (question counts, time limits per exam type).
*   **Response**: Mock format configuration object.

### 🟡 Phase 4.3: Quiz & Lesson APIs (Milestone 5 — PENDING)

### 7. `GET /api/v1/quizzes`
Lists available quizzes with metadata (title, type, duration, question_count).
*   **Query Parameters**: `subject_id` (optional), `type` (optional).

### 8. `GET /api/v1/quizzes/{id}`
Gets quiz details including questions (for lesson quizzes).
*   **Response**: Quiz object with questions and options.

### 9. `POST /api/v1/quizzes/{id}/start`
Creates a new quiz attempt and returns attempt ID.
*   **Body**: `{ "question_count": 40, "shuffle": true }`

### 10. `POST /api/v1/quiz-attempts/{id}/answers`
Batch submits answers for a quiz attempt (for offline sync).
*   **Body**: `{ "answers": [{ "question_id": 1, "option_id": 3 }, ...] }`

### 11. `POST /api/v1/quiz-attempts/{id}/submit`
Finalizes a quiz attempt and calculates score.
*   **Response**: Attempt object with score and results.

### 12. `GET /api/v1/quiz-attempts/{id}/results`
Gets attempt results with detailed answer review and explanations.
*   **Response**: Results object with correct/incorrect breakdown.

### 13. `GET /api/v1/subjects/{id}/lessons`
Lists lessons for a subject with progress tracking.
*   **Response**: Array of lesson objects with completion status.

### 14. `GET /api/v1/lessons/{id}`
Gets lesson details including video URL, duration, and content.
*   **Response**: Lesson object with video metadata.

### 15. `POST /api/v1/lessons/{id}/progress`
Updates video watch progress (for offline sync).
*   **Body**: `{ "watched_seconds": 120, "completed": false }`

### 16. `POST /api/v1/lessons/{id}/complete`
Marks a lesson as completed.
*   **Response**: Success confirmation.

### 🟡 Phase 4.4: Analytics APIs (Milestone 5 — PENDING)

### 17. `GET /api/v1/analytics/overview`
Returns user dashboard stats (total quizzes, avg score, streak, time spent).
*   **Response**: Overview stats object.

### 18. `GET /api/v1/analytics/subject-performance`
Returns performance breakdown by subject.
*   **Response**: Array of subject performance objects.

### 19. `GET /api/v1/analytics/quiz-history`
Returns recent quiz attempts with scores.
*   **Query Parameters**: `limit` (optional, default 10).
*   **Response**: Array of quiz attempt objects.

### 20. `GET /api/v1/analytics/study-streak`
Returns study streak data (last 30 days).
*   **Response**: Streak data object.

### 🟡 Phase 4.5: Mock Exam APIs (Milestone 4 — PENDING)

### 21. `GET /api/v1/mock/groups`
Fetches mock groups (batches) for a single subject and exam type.
*   **Query Parameters**: `subject_id` (required), `exam_type_id` (required).

### 22. `GET /api/v1/mock/groups/{id}/download`
Downloads questions assigned to a specific mock group (is_mock = 1).

### 23. `POST /api/v1/mock/sessions`
Validates and initializes a multi-subject Mock Session on the backend, generating custom configuration.

### 🟡 Phase 4.6: Sync APIs (Milestone 3 — PENDING)

### 24. `POST /api/v1/sync`
Processes sync queues. The attempts payload supports `mock_group_id` for mock exam grading on the server.
*   **Body**: `{ "attempts": [...], "answers": [...], "lesson_progress": [...] }`

---

## 📘 Phase 5: API Documentation & Postman Support

To explain how your APIs work to other programmers and provide them with a Postman collection automatically, we use **Scribe**. It parses your code, DocBlocks, validation rules, and live response calls to generate documentation that reflects real database content.

### 1. Initialize Scribe Configuration
Publish Scribe config:
```bash
php artisan scribe:install
```

Before generating docs, make sure the database already contains real users, subscriptions, subjects, and question data so Scribe can use those rows for example models.

### 2. Document Your Code (Standard Laravel PHPDoc Annotation)
Decorate your API controllers with standard comments. Scribe automatically extracts parameters:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * APIs for managing student tokens.
 */
class AuthController extends Controller
{
    /**
     * Mobile Login
     *
     * Authenticates the user and returns a Bearer Token.
     *
     * @bodyParam email string required The student's email address. Example: student@futureacademy.com
     * @bodyParam password string required The student's account password. Example: secret123
     * @bodyParam device_name string required The student's device type. Example: Infinix Hot 30i
     */
    public function login(Request $request)
    {
        // code...
    }
}
```

### 3. Generate HTML Docs & Postman Collection
Run this command:
```bash
php artisan scribe:generate
```

*   **HTML Documentation**: Generated at `/docs` in the browser. It provides request templates, copy-pasteable curls, and a "Try it out" feature.
*   **Postman Collection**: Generated automatically in `storage/app/scribe/collection.json` for the Laravel docs type. Any programmer can import this file directly into **Postman**.

### 4. Scribe Example Model Order

In `config/scribe.php`, keep the database-first example order so generated samples prefer real data already present in the database:

```php
'examples' => [
    'models_source' => ['databaseFirst', 'factoryMake'],
],
```

---

## ⚡ Phase 6: Data-Saving Download Optimizations (For Nigerian Networks)

To ensure that downloading questions takes **less than 2 seconds** on slow 3G/4G connections in Nigeria:

1. **Text JSON payloads are extremely small**:
   A single question with 4 options is around **350 bytes** of text.
   * A single subject mock exam (50 questions) = **17.5 KB**.
   * A full 5-year single subject bank (200 questions) = **70 KB**.
   * Even downloading all 4 JAMB subjects (e.g. 160 questions) is less than **60 KB**.

2. **Enable Gzip/Brotli Compression on Nginx/Apache**:
   Configure your backend web server to compress JSON responses. This reduces the text data size by **75%**, meaning 100 KB of questions transfers as a tiny **25 KB** zip package over the air.

3. **Lazy-Load Images (Critical)**:
   Do not download question diagrams and math graphs during the initial question package sync. Instead:
   * Download the text JSON structure first (instant).
   * Caches image URIs (`question_image`) in SQLite.
   * Let the mobile app download and cache individual images in the background or load them only when the student navigates to a question that actually has a diagram. This prevents downloading megabytes of unused images.

4. **Delta Sync for Updates**:
   Instead of re-downloading entire question banks, implement a delta sync mechanism:
   * Send `last_synced_at` timestamp to download endpoint
   * Server returns only questions added/modified since that timestamp
   * Mobile app merges delta into local SQLite database

5. **Batch API Calls**:
   Group multiple API calls into single requests where possible:
   * Use `GET /api/v1/jamb/download` instead of 4 separate subject downloads
   * Batch answer submissions in single `POST /api/v1/sync` call
   * Combine configuration data (subjects, exam types, years) into single endpoint

6. **Request Queuing with Retry Logic**:
   Implement a robust request queue for poor network conditions:
   * Queue failed requests automatically
   * Exponential backoff retry (1s, 2s, 4s, 8s, 16s)
   * Max retry limit (e.g., 5 attempts)
   * Persist queue to SQLite so requests survive app restarts

---

## Phase 7: App Shell, Navigation and Authentication Screens

> Milestone 6 deliverable. Get a real student logged in on a real Android phone.

### 7.1 Root Layout (app/_layout.tsx)

This is the entry point for the whole app. It checks for a stored token and onboarding status, then redirects accordingly:

```typescript
import { Stack } from 'expo-router';
import { useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { useRouter } from 'expo-router';

export default function RootLayout() {
  const router = useRouter();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    async function bootstrap() {
      const token = await SecureStore.getItemAsync('auth_token');
      const userStr = await SecureStore.getItemAsync('user');
      const user = userStr ? JSON.parse(userStr) : null;

      if (token) {
        if (user && !user.has_completed_onboarding) {
          router.replace('/(auth)/onboarding');
        } else {
          router.replace('/(tabs)');
        }
      } else {
        router.replace('/(auth)/login');
      }
      setReady(true);
    }
    bootstrap();
  }, []);

  if (!ready) return null;

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(auth)" />
      <Stack.Screen name="(tabs)" />
    </Stack>
  );
}
```

---

### 7.2 Login Screen (app/(auth)/login.tsx)

```typescript
import { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, StyleSheet } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { useRouter } from 'expo-router';
import * as Device from 'expo-device';
import api from '../../lib/api';

export default function LoginScreen() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleLogin() {
    setLoading(true);
    try {
      const response = await api.post('/login', {
        email,
        password,
        device_name: Device.modelName ?? 'Unknown Device',
      });
      await SecureStore.setItemAsync('auth_token', response.data.token);
      await SecureStore.setItemAsync('user', JSON.stringify(response.data.user));
      
      if (!response.data.user.has_completed_onboarding) {
        router.replace('/(auth)/onboarding');
      } else {
        router.replace('/(tabs)');
      }
    } catch (error: any) {
      const message = error.response?.data?.errors?.email?.[0]
        ?? error.response?.data?.message
        ?? 'Login failed. Check your internet connection.';
      Alert.alert('Login Failed', message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Future Academy</Text>
      <Text style={styles.subtitle}>Sign in to continue</Text>
      <TextInput style={styles.input} placeholder="Email address" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" />
      <TextInput style={styles.input} placeholder="Password" value={password} onChangeText={setPassword} secureTextEntry />
      <TouchableOpacity style={[styles.button, loading && styles.buttonDisabled]} onPress={handleLogin} disabled={loading}>
        <Text style={styles.buttonText}>{loading ? 'Signing in...' : 'Sign In'}</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', padding: 24, backgroundColor: '#fff' },
  title: { fontSize: 32, fontWeight: 'bold', color: '#171717', marginBottom: 8, fontFamily: 'System' },
  subtitle: { fontSize: 16, color: '#666', marginBottom: 32, fontFamily: 'System' },
  input: { borderWidth: 1, borderColor: '#e5e5e5', borderRadius: 8, padding: 14, marginBottom: 16, fontSize: 16, fontFamily: 'System' },
  button: { backgroundColor: '#4f46e5', borderRadius: 8, padding: 16, alignItems: 'center' },
  buttonDisabled: { backgroundColor: '#a5a5a5' },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600', fontFamily: 'System' },
});
```

---

### 7.3 Onboarding Screen (app/(auth)/onboarding.tsx)

```typescript
import { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, ScrollView, Alert, StyleSheet } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { useRouter } from 'expo-router';
import api from '../../lib/api';

export default function OnboardingScreen() {
  const router = useRouter();
  const [streams, setStreams] = useState<any[]>([]);
  const [selectedStream, setSelectedStream] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Fetch available configurations/subjects
    async function loadConfig() {
      try {
        const response = await api.get('/config/subjects');
        // Group subjects or streams from response...
      } catch (error) {
        console.error('Failed to load onboarding options', error);
      }
    }
    loadConfig();
  }, []);

  async function handleOnboardingComplete() {
    if (!selectedStream) {
      Alert.alert('Selection Required', 'Please select a stream to continue.');
      return;
    }
    setLoading(true);
    try {
      // POST onboarding data to the backend
      const response = await api.post('/user/onboarding', {
        stream: selectedStream,
      });

      // Update stored user details
      const userStr = await SecureStore.getItemAsync('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        user.has_completed_onboarding = true;
        await SecureStore.setItemAsync('user', JSON.stringify(user));
      }

      router.replace('/(tabs)');
    } catch (error) {
      Alert.alert('Error', 'Failed to save onboarding selections. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Text style={styles.title}>Welcome to Future Academy</Text>
      <Text style={styles.subtitle}>Choose your learning stream to customize your question banks</Text>
      
      <TouchableOpacity 
        style={[styles.card, selectedStream === 'science' && styles.cardSelected]} 
        onPress={() => setSelectedStream('science')}
      >
        <Text style={styles.cardTitle}>Science</Text>
        <Text style={styles.cardDesc}>Physics, Chemistry, Biology, Mathematics</Text>
      </TouchableOpacity>

      <TouchableOpacity 
        style={[styles.card, selectedStream === 'arts' && styles.cardSelected]} 
        onPress={() => setSelectedStream('arts')}
      >
        <Text style={styles.cardTitle}>Arts</Text>
        <Text style={styles.cardDesc}>Literature, Government, CRK, History</Text>
      </TouchableOpacity>

      <TouchableOpacity 
        style={[styles.card, selectedStream === 'social_science' && styles.cardSelected]} 
        onPress={() => setSelectedStream('social_science')}
      >
        <Text style={styles.cardTitle}>Social Sciences</Text>
        <Text style={styles.cardDesc}>Economics, Geography, Commerce, Government</Text>
      </TouchableOpacity>

      <TouchableOpacity 
        style={[styles.button, !selectedStream && styles.buttonDisabled]} 
        onPress={handleOnboardingComplete}
        disabled={loading || !selectedStream}
      >
        <Text style={styles.buttonText}>{loading ? 'Saving...' : 'Finish Setup'}</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, padding: 24, justifyContent: 'center', backgroundColor: '#fff' },
  title: { fontSize: 26, fontWeight: 'bold', color: '#171717', marginBottom: 8, textAlign: 'center' },
  subtitle: { fontSize: 15, color: '#666', marginBottom: 32, textAlign: 'center', lineHeight: 22 },
  card: { borderWidth: 1, borderColor: '#e5e5e5', borderRadius: 12, padding: 18, marginBottom: 16, backgroundColor: '#fafafa' },
  cardSelected: { borderColor: '#4f46e5', backgroundColor: '#eef2ff', borderWidth: 2 },
  cardTitle: { fontSize: 18, fontWeight: 'bold', color: '#171717', marginBottom: 4 },
  cardDesc: { fontSize: 14, color: '#666' },
  button: { backgroundColor: '#4f46e5', borderRadius: 8, padding: 16, alignItems: 'center', marginTop: 16 },
  buttonDisabled: { backgroundColor: '#a5a5a5' },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});
```

### 7.4 Checklist

- [ ] Root layout reads stored token and onboarding status, redirecting correctly
- [ ] Login screen calls POST /api/v1/login with device_name
- [ ] Token and user object stored in expo-secure-store
- [ ] Onboarding screen displays stream selections and submits to backend
- [ ] Logout button in Settings calls POST /api/v1/logout and clears secure store
- [ ] Bottom tab navigator with 5 tabs: Home/Dashboard, Practice, JAMB, Mock, Settings
- [ ] Protected routes — unauthenticated users always redirected to login, non-onboarded to onboarding

---

## Phase 8: Offline Download Manager and Local SQLite

> Milestone 7 deliverable. Questions stored on-device for 100% offline use.

### 8.1 Initialize the SQLite Database on App Start

Create lib/database.ts:

```typescript
import * as SQLite from 'expo-sqlite';

let db: SQLite.SQLiteDatabase;

export async function getDatabase(): Promise<SQLite.SQLiteDatabase> {
  if (!db) {
    db = await SQLite.openDatabaseAsync('future_academy.db');
    await db.execAsync(`
      PRAGMA journal_mode = WAL;
      CREATE TABLE IF NOT EXISTS questions (id INTEGER PRIMARY KEY, subject_id INTEGER NOT NULL, topic_id INTEGER, exam_type_id INTEGER, exam_year INTEGER, question_text TEXT NOT NULL, question_image TEXT, difficulty TEXT, explanation TEXT, is_mock INTEGER DEFAULT 0, mock_group_id INTEGER);
      CREATE TABLE IF NOT EXISTS options (id INTEGER PRIMARY KEY, question_id INTEGER NOT NULL, option_text TEXT NOT NULL, option_image TEXT, is_correct INTEGER DEFAULT 0);
      CREATE TABLE IF NOT EXISTS offline_attempts (uuid TEXT PRIMARY KEY, user_id INTEGER NOT NULL, exam_type_id INTEGER, subject_id INTEGER, mock_group_id INTEGER, exam_year INTEGER, status TEXT DEFAULT 'in_progress', started_at TEXT NOT NULL, completed_at TEXT, time_taken_seconds INTEGER DEFAULT 0, total_questions INTEGER DEFAULT 0, correct_answers INTEGER DEFAULT 0, question_order TEXT NOT NULL, is_synced INTEGER DEFAULT 0);
      CREATE TABLE IF NOT EXISTS offline_answers (attempt_uuid TEXT NOT NULL, question_id INTEGER NOT NULL, selected_option_id INTEGER, is_correct INTEGER DEFAULT 0, PRIMARY KEY (attempt_uuid, question_id));
    `);
  }
  return db;
}
```

### 8.2 Download a Subject's Questions into SQLite

```typescript
// lib/downloader.ts
import api from './api';
import { getDatabase } from './database';

export async function downloadSubject(subjectId: number): Promise<void> {
  const db = await getDatabase();
  const response = await api.get(`/subjects/${subjectId}/download`);
  const { questions } = response.data;

  await db.withTransactionAsync(async () => {
    await db.runAsync('DELETE FROM questions WHERE subject_id = ?', [subjectId]);

    for (const q of questions) {
      await db.runAsync(
        'INSERT OR REPLACE INTO questions (id, subject_id, topic_id, exam_type_id, exam_year, question_text, question_image, difficulty, explanation, is_mock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [q.id, q.subject_id, q.topic_id, q.exam_type_id, q.exam_year, q.question_text, q.question_image, q.difficulty, q.explanation, q.is_mock ? 1 : 0]
      );
      for (const opt of q.options) {
        await db.runAsync(
          'INSERT OR REPLACE INTO options (id, question_id, option_text, option_image, is_correct) VALUES (?, ?, ?, ?, ?)',
          [opt.id, q.id, opt.option_text, opt.option_image, opt.is_correct ? 1 : 0]
        );
      }
    }
  });
}
```

### 8.3 Checklist

- [ ] SQLite schema created on every app launch (idempotent)
- [ ] Subject index screen fetches enrolled subjects from GET /api/v1/subjects
- [ ] Each subject card shows: Not Downloaded / Downloaded / Updating status
- [ ] Download button triggers downloadSubject() with progress indicator
- [ ] Downloaded subjects accessible in airplane mode

---

## Phase 9: Practice Quiz and JAMB Quiz Players

> Milestone 8 deliverable. Full offline quiz experience.

### 9.1 Load Questions from SQLite

```typescript
// lib/quiz.ts
import { getDatabase } from './database';

export async function getQuestionsForSubject(subjectId: number, examYear?: number) {
  const db = await getDatabase();
  const query = examYear
    ? 'SELECT q.* FROM questions q WHERE q.subject_id = ? AND q.is_mock = 0 AND q.exam_year = ?'
    : 'SELECT q.* FROM questions q WHERE q.subject_id = ? AND q.is_mock = 0';
  return await db.getAllAsync(query, examYear ? [subjectId, examYear] : [subjectId]);
}
```

### 9.2 Save a Completed Attempt

```typescript
import { getDatabase } from './database';
import { randomUUID } from 'expo-crypto';

export async function saveAttempt(attempt: {
  userId: number; subjectId: number;
  answers: { questionId: number; selectedOptionId: number | null; isCorrect: boolean }[];
  timeTakenSeconds: number;
}): Promise<string> {
  const db = await getDatabase();
  const uuid = randomUUID();
  const correct = attempt.answers.filter(a => a.isCorrect).length;
  const now = new Date().toISOString();

  await db.withTransactionAsync(async () => {
    await db.runAsync(
      "INSERT INTO offline_attempts (uuid, user_id, subject_id, status, started_at, completed_at, time_taken_seconds, total_questions, correct_answers, question_order, is_synced) VALUES (?, ?, ?, 'completed', ?, ?, ?, ?, ?, '[]', 0)",
      [uuid, attempt.userId, attempt.subjectId, now, now, attempt.timeTakenSeconds, attempt.answers.length, correct]
    );
    for (const ans of attempt.answers) {
      await db.runAsync(
        'INSERT INTO offline_answers (attempt_uuid, question_id, selected_option_id, is_correct) VALUES (?, ?, ?, ?)',
        [uuid, ans.questionId, ans.selectedOptionId, ans.isCorrect ? 1 : 0]
      );
    }
  });
  return uuid;
}
```

### 9.3 Checklist

- [ ] Practice Setup: subject picker, optional year filter, start button
- [ ] Single-Subject Quiz Player: question card, 4 option buttons, answer reveal + explanation
- [ ] Progress bar and Question X of Y counter
- [ ] Results screen: score, percentage, time taken, incorrect answer review
- [ ] JAMB Setup: 4-subject selector, optional year filter
- [ ] JAMB Quiz Player: subject tabs, shared countdown timer
- [ ] All answers written to offline_answers with is_synced = 0

---

## Phase 10: Mock Exam Player and Anti-Cheat Timer

> Milestone 9 deliverable. Timed, invigilated exam experience.

### 10.1 Countdown Timer Hook

```typescript
// hooks/useCountdownTimer.ts
import { useEffect, useRef, useState } from 'react';

export function useCountdownTimer(totalSeconds: number, onExpire: () => void) {
  const [secondsLeft, setSecondsLeft] = useState(totalSeconds);
  const ref = useRef<NodeJS.Timeout>();

  useEffect(() => {
    ref.current = setInterval(() => {
      setSecondsLeft(prev => {
        if (prev <= 1) { clearInterval(ref.current); onExpire(); return 0; }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(ref.current);
  }, []);

  const minutes = Math.floor(secondsLeft / 60);
  const seconds = secondsLeft % 60;
  return { secondsLeft, formatted: `${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}` };
}
```

### 10.2 Anti-Cheat: App Backgrounding Detection

```typescript
import { AppState } from 'react-native';
import { useEffect, useRef } from 'react';

export function useAntiCheat(onViolation: () => void) {
  const violations = useRef(0);
  useEffect(() => {
    const sub = AppState.addEventListener('change', state => {
      if (state === 'background') { violations.current += 1; onViolation(); }
    });
    return () => sub.remove();
  }, []);
  return violations;
}
```

### 10.3 Checklist

- [ ] Mock Setup: single subject (pick mock group) vs. multi-subject toggle
- [ ] Mock Groups screen: lists batches from GET /api/v1/mock/groups
- [ ] Mock Quiz Player with countdown timer that auto-submits at 0:00
- [ ] Timer changes color: green -> amber (30 min left) -> red (5 min left)
- [ ] App backgrounding detected and logged
- [ ] Answers locked after submission (read-only review mode)
- [ ] Results screen: per-subject score breakdown, total percentage

---

## Phase 11: Background Sync Engine and App Polish

> Milestone 10 deliverable. All offline data synced to the server. App ready for Play Store.

### 11.1 Sync Engine (lib/sync.ts)

```typescript
import api from './api';
import { getDatabase } from './database';

export async function syncPendingAttempts(): Promise<{ synced: number; failed: number }> {
  const db = await getDatabase();
  const pending = await db.getAllAsync("SELECT * FROM offline_attempts WHERE is_synced = 0 AND status = 'completed'");
  if (pending.length === 0) return { synced: 0, failed: 0 };

  const uuidList = (pending as any[]).map(a => a.uuid);
  const allAnswers = await db.getAllAsync(
    `SELECT * FROM offline_answers WHERE attempt_uuid IN (${uuidList.map(() => '?').join(',')})`,
    uuidList
  );

  try {
    const response = await api.post('/sync', {
      attempts: (pending as any[]).map(a => ({ ...a, question_order: JSON.parse(a.question_order ?? '[]') })),
      answers: (allAnswers as any[]).map(ans => ({
        attempt_uuid: ans.attempt_uuid,
        question_id: ans.question_id,
        selected_option_id: ans.selected_option_id,
        is_correct: ans.is_correct === 1,
      })),
    });

    for (const attempt of pending as any[]) {
      await db.runAsync('UPDATE offline_attempts SET is_synced = 1 WHERE uuid = ?', [attempt.uuid]);
    }
    return { synced: response.data.synced_attempts, failed: response.data.failed_attempts };
  } catch {
    return { synced: 0, failed: pending.length };
  }
}
```

### 11.2 Trigger Sync When App Comes Back Online

```typescript
// In your root layout _layout.tsx
import { AppState } from 'react-native';
import { syncPendingAttempts } from '../lib/sync';

AppState.addEventListener('change', async (state) => {
  if (state === 'active') {
    await syncPendingAttempts();
  }
});
```

### 11.3 Final Polish Checklist

- [ ] Sync status banner: "3 attempts pending sync" / "All synced"
- [ ] Skeleton loading screens while data fetches
- [ ] Pull-to-refresh on Subject Index screen
- [ ] Empty state illustrations when no subjects are downloaded
- [ ] Sentry crash reporting initialized and tested
- [ ] Firebase Analytics: track login, quiz_start, quiz_complete, sync_success events
- [ ] Profile / Dashboard screen:
  - [ ] Show total questions answered, accuracy percentage, and subjects downloaded.
  - [ ] Display **Study Streak** counter and flame/streak indicator (fetched from `GET /api/v1/analytics/study-streak`).
  - [ ] Display **Subject Performance Analytics** breakdown charts/lists (fetched from `GET /api/v1/analytics/subject-performance`).
- [ ] `eas build --platform android --profile production` - generate .aab for Play Store
- [ ] Play Store listing prepared: screenshots, description, content rating
