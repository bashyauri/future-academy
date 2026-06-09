# Future Academy: Mobile App Development & Integration Guide

This checklist-style guide details everything you need to install and create for the mobile app integration, aligned specifically with the database schema, Practice Mode, JAMB Practice, Mock Exams, API documentation standards, and data-optimization strategies of Future Academy.

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

### 💻 2. Laravel Backend
Run these commands in your Laravel backend directory:

- [ ] **API Authentication** (Sanctum is already in `composer.json`).
- [ ] **Interactive API Documentation & Postman Generator**
  ```bash
  composer require knuckleswtf/scribe --dev
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
mobile-app/
├── app/                              
│   ├── (auth)/
│   │   └── login.tsx                 
│   ├── (tabs)/
│   │   ├── index.tsx                 <-- (Syllabus/Subject index)
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

### 1. `GET /api/v1/subjects/{id}/download`
Downloads single subject question packages for practice.
*   **Query Parameters**: `year` (optional).

### 2. `GET /api/v1/jamb/download`
Downloads questions for 4 selected subjects at once for JAMB practice.
*   **Query Parameters**: `subjects` (comma-separated), `year` (optional).

### 3. `GET /api/v1/mock/groups`
Fetches mock groups (batches) for a single subject and exam type.
*   **Query Parameters**: `subject_id` (required), `exam_type_id` (required).

### 4. `GET /api/v1/mock/groups/{id}/download`
Downloads questions assigned to a specific mock group (is_mock = 1).

### 5. `POST /api/v1/mock/session/start`
Validates and initializes a multi-subject Mock Session on the backend, generating custom configuration.

### 6. `POST /api/v1/sync`
Processes sync queues. The attempts payload supports `mock_group_id` for mock exam grading on the server.

---

## 📘 Phase 5: API Documentation & Postman Support

To explain how your APIs work to other programmers and provide them with a Postman collection automatically, we use **Scribe**. It parses your code, DocBlocks, and validation rules to auto-generate standard interactive documentation.

### 1. Initialize Scribe Configuration
Publish Scribe config:
```bash
php artisan scribe:install
```

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

*   **HTML Documentation**: Generated at `public/docs/index.html` (viewable in the browser at `http://your-app.test/docs`). It provides a beautiful interface (like Stripe's API docs) with complete request templates, copy-pasteable curls, and a "Try it out" feature.
*   **Postman Collection**: Generated automatically at `public/docs/collection.json`. Any programmer can import this file directly into **Postman** to load the complete endpoint library!

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
