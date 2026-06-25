# API Testing Guide - Postman

This guide provides instructions for testing the Future Academy API endpoints using Postman.

## Authentication

### 1. Get Authentication Token

- **Method**: `POST`
- **URL**: `https://future-academy.test/api/v1/login`
- **Headers**: `Content-Type: application/json`
- **Body**:
```json
{
  "email": "your-email@example.com",
  "password": "your-password",
  "device_name": "Postman Test"
}
```
- **Response**:
```json
{
  "message": "Login successful",
  "token": "your-token-here",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com"
  }
}
```
- **Copy the `token` from the response for subsequent requests**

### 2. Use Token in Subsequent Requests

Add the following header to all authenticated requests:
- **Header**: `Authorization: Bearer YOUR_TOKEN_HERE`

**Tip**: Save the token in Postman environment variables for easier reuse across requests.

## API Endpoints

### User Profile

**Get Current User Profile**
- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/user`
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Response**: User profile data

**Logout**
- **Method**: `POST`
- **URL**: `https://future-academy.test/api/v1/logout`
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Response**: `{ "message": "Logged out successfully" }`

### Question Pack Downloads

#### Download Single Subject Questions

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/subjects/{id}/download`
- **URL Parameters**:
  - `{id}` - Subject ID (required)
- **Query Parameters** (optional):
  - `year` - Filter by year (e.g., `2024`)
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Example**: `https://future-academy.test/api/v1/subjects/1/download?year=2024`
- **Response**:
```json
{
  "subject": {
    "id": 1,
    "name": "Mathematics",
    "code": "MATH",
    "slug": "mathematics",
    "icon": "icon-name",
    "color": "#hex-color"
  },
  "questions": [
    {
      "id": 1,
      "question_text": "Question text here",
      "question_text_html": "HTML formatted question",
      "question_image": "image-url",
      "explanation": "Explanation text",
      "explanation_html": "HTML formatted explanation",
      "explanation_image": "image-url",
      "subject_id": 1,
      "topic_id": 1,
      "exam_type_id": 1,
      "exam_year": 2024,
      "year": 2024,
      "difficulty": "medium",
      "is_mock": false,
      "mock_group_id": null,
      "options": [
        {
          "id": 1,
          "label": "A",
          "option_text": "Option text",
          "option_text_html": "HTML formatted option",
          "option_image": "image-url",
          "is_correct": true,
          "sort_order": 1
        }
      ]
    }
  ],
  "total_questions": 100,
  "year_filter": 2024
}
```

#### Download JAMB Practice Questions (Multi-Subject)

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/jamb/download`
- **Query Parameters**:
  - `subjects` - Comma-separated subject IDs (required, 1-4 subjects)
  - `year` - Filter by year (optional)
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Example**: `https://future-academy.test/api/v1/jamb/download?subjects=1,2,3,4&year=2024`
- **Response**:
```json
{
  "subjects": [
    {
      "id": 1,
      "name": "Mathematics",
      "code": "MATH",
      "slug": "mathematics",
      "icon": "icon-name",
      "color": "#hex-color",
      "questions": [...],
      "total_questions": 40
    }
  ],
  "total_questions": 160,
  "year_filter": 2024
}
```

### Sync Engine (Offline Data Synchronization)

#### Sync Offline Data

- **Method**: `POST`
- **URL**: `https://future-academy.test/api/v1/sync`
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
```json
{
  "attempts": [
    {
      "uuid": "unique-uuid-for-attempt",
      "user_id": 1,
      "quiz_id": 1,
      "subject_id": 1,
      "exam_year": 2024,
      "status": "completed",
      "started_at": "2024-01-01T10:00:00Z",
      "completed_at": "2024-01-01T10:30:00Z",
      "time_taken_seconds": 1800,
      "total_questions": 40,
      "correct_answers": 32,
      "score_percentage": 80,
      "passed": true,
      "question_order": [1, 2, 3, 4]
    }
  ],
  "answers": [
    {
      "attempt_uuid": "unique-uuid-for-attempt",
      "question_id": 1,
      "option_id": 3,
      "is_correct": true,
      "time_spent_seconds": 30
    }
  ],
  "lesson_progress": [
    {
      "user_id": 1,
      "lesson_id": 1,
      "current_time_seconds": 120,
      "progress_percentage": 30,
      "is_completed": false,
      "time_spent_seconds": 120
    }
  ]
}
```
- **Response**:
```json
{
  "message": "Sync completed successfully",
  "synced_attempts": 1,
  "synced_answers": 1,
  "synced_lesson_progress": 1,
  "failed_attempts": 0,
  "failed_answers": 0
}
```
- **Notes**:
  - All arrays are optional - send only what you need to sync
  - UUID is used to prevent duplicate submissions (double-grading protection)
  - Uses database transactions for data integrity
  - Designed for poor network conditions with retry support

#### Get Sync Status

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/sync/status`
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Response**:
```json
{
  "unsynced_attempts": 0,
  "unsynced_answers": 0,
  "unsynced_lesson_progress": 0
}
```
- **Notes**: Returns count of unsynced data (mobile app tracks this locally)

### Mock Exam Batches & Sessions

#### Get Mock Groups for Subject and Exam Type

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/mock/groups`
- **Query Parameters**:
  - `subject_id` - Subject ID (required)
  - `exam_type_id` - Exam type ID (required)
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Example**: `https://future-academy.test/api/v1/mock/groups?subject_id=1&exam_type_id=1`
- **Response**:
```json
{
  "message": "Mock groups retrieved successfully",
  "data": [
    {
      "id": 1,
      "subject_id": 1,
      "exam_type_id": 1,
      "batch_number": 1,
      "total_questions": 40,
      "subject": {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH",
        "slug": "mathematics",
        "icon": "icon-name",
        "color": "#hex-color"
      },
      "exam_type": {
        "id": 1,
        "name": "JAMB",
        "slug": "jamb"
      }
    }
  ]
}
```

#### Get Specific Mock Group by Batch Number

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/mock/groups/{batchNumber}`
- **URL Parameters**:
  - `{batchNumber}` - Batch number (required)
- **Query Parameters**:
  - `subject_id` - Subject ID (required)
  - `exam_type_id` - Exam type ID (required)
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Example**: `https://future-academy.test/api/v1/mock/groups/1?subject_id=1&exam_type_id=1`
- **Response**:
```json
{
  "message": "Mock group retrieved successfully",
  "data": {
    "id": 1,
    "subject_id": 1,
    "exam_type_id": 1,
    "batch_number": 1,
    "total_questions": 40,
    "subject": {...},
    "exam_type": {...}
  }
}
```

#### Download Mock Group Questions

- **Method**: `GET`
- **URL**: `https://future-academy.test/api/v1/mock/groups/{batchNumber}/download`
- **URL Parameters**:
  - `{batchNumber}` - Batch number (required)
- **Query Parameters**:
  - `subject_id` - Subject ID (required)
  - `exam_type_id` - Exam type ID (required)
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Example**: `https://future-academy.test/api/v1/mock/groups/1/download?subject_id=1&exam_type_id=1`
- **Response**:
```json
{
  "message": "Mock group questions downloaded successfully",
  "data": {
    "mock_group": {
      "id": 1,
      "subject_id": 1,
      "exam_type_id": 1,
      "batch_number": 1,
      "total_questions": 40,
      "subject": {...},
      "exam_type": {...}
    },
    "questions": [
      {
        "id": 1,
        "question_text": "Question text here",
        "question_text_html": "HTML formatted question",
        "question_image": "image-url",
        "explanation": "Explanation text",
        "explanation_html": "HTML formatted explanation",
        "explanation_image": "image-url",
        "subject_id": 1,
        "topic_id": 1,
        "exam_type_id": 1,
        "exam_year": 2024,
        "year": 2024,
        "difficulty": "medium",
        "is_mock": true,
        "mock_group_id": 1,
        "options": [...]
      }
    ]
  }
}
```

#### Initialize Multi-Subject Mock Session

- **Method**: `POST`
- **URL**: `https://future-academy.test/api/v1/mock/sessions`
- **Headers**: `Authorization: Bearer YOUR_TOKEN`
- **Body**:
```json
{
  "subject_ids": [1, 2, 3, 4],
  "exam_type_id": 1,
  "duration_minutes": 120
}
```
- **Response**:
```json
{
  "message": "Mock session initialized successfully",
  "data": {
    "session_id": "uuid-v4-string",
    "exam_type": {
      "id": 1,
      "name": "JAMB",
      "slug": "jamb"
    },
    "subjects": [
      {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH",
        "slug": "mathematics",
        "icon": "icon-name",
        "color": "#hex-color",
        "total_groups": 5,
        "first_group": {...},
        "time_limit_minutes": 30
      }
    ],
    "duration_minutes": 120,
    "total_questions": 160,
    "time_limit_per_subject": 30,
    "created_at": "2024-01-01T10:00:00Z"
  }
}
```
- **Notes**:
  - `subject_ids` must be an array of 1-4 subject IDs
  - `duration_minutes` is optional (defaults to 120 minutes)
  - Time is divided equally among subjects
  - Returns unique session_id for tracking the mock attempt
  - Includes first mock group for each subject to start the exam

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```
**Solution**: Check your token is valid and properly formatted in the Authorization header.

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\Subject] 999"
}
```
**Solution**: Verify the subject ID exists.

### 422 Validation Error
```json
{
  "message": "The year must be 2000 or later.",
  "errors": {
    "year": ["The year must be 2000 or later."]
  }
}
```
**Solution**: Check request parameters match validation rules.

## Postman Tips

1. **Environment Variables**: Create an environment and save your token as `{{api_token}}`
2. **Collection**: Organize related requests in a collection
3. **Tests**: Add tests to validate responses automatically
4. **Pre-request Script**: Use scripts to automatically set tokens
5. **Documentation**: Use Postman's documentation feature to share with team

## Performance Expectations

- Single subject download: < 500ms
- JAMB practice download (4 subjects): < 500ms
- All endpoints use eager loading to prevent N+1 queries
- Responses are optimized for mobile offline use

---

## Milestone 5: Analytics, Lessons & Configuration APIs

### Configuration Endpoints

#### GET /api/v1/config/subjects
Get all active subjects with metadata.

**Method:** GET
**URL:** https://future-academy.test/api/v1/config/subjects
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Subjects retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Mathematics",
      "code": "MATH",
      "slug": "mathematics",
      "icon": "calculator",
      "color": "#3B82F6",
      "is_active": true
    }
  ]
}
```

#### GET /api/v1/config/exam-types
Get all exam types (JAMB, WAEC, NECO).

**Method:** GET
**URL:** https://future-academy.test/api/v1/config/exam-types
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Exam types retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "JAMB",
      "slug": "jamb",
      "exam_format": "multi_subject"
    }
  ]
}
```

#### GET /api/v1/config/years
Get available years for filtering questions.

**Method:** GET
**URL:** https://future-academy.test/api/v1/config/years
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Years retrieved successfully",
  "data": [2024, 2023, 2022, 2021, 2020]
}
```

#### GET /api/v1/config/mock-formats
Get mock exam configuration.

**Method:** GET
**URL:** https://future-academy.test/api/v1/config/mock-formats
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Mock formats retrieved successfully",
  "data": {
    "jamb": {
      "question_count": 60,
      "duration_minutes": 120
    }
  }
}
```

### Analytics Endpoints

#### GET /api/v1/analytics/overview
Get user overview statistics (total quizzes, average score, study streak).

**Method:** GET
**URL:** https://future-academy.test/api/v1/analytics/overview
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Analytics overview retrieved successfully",
  "data": {
    "total_quizzes": 25,
    "average_score": 78.5,
    "total_time_spent": 5400,
    "study_streak": 7
  }
}
```

#### GET /api/v1/analytics/subject-performance
Get performance breakdown by subject.

**Method:** GET
**URL:** https://future-academy.test/api/v1/analytics/subject-performance
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Subject performance retrieved successfully",
  "data": [
    {
      "subject": {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH"
      },
      "total_attempts": 10,
      "average_score": 85.5,
      "total_time_spent_seconds": 1800
    }
  ]
}
```

#### GET /api/v1/analytics/quiz-history
Get recent quiz attempts (with optional limit parameter).

**Method:** GET
**URL:** https://future-academy.test/api/v1/analytics/quiz-history?limit=10
**Headers:** Authorization: Bearer YOUR_TOKEN
**Query Parameters:** limit (optional, default: 10)

**Response:**
```json
{
  "message": "Quiz history retrieved successfully",
  "data": [
    {
      "id": 1,
      "subject": {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH"
      },
      "quiz": {
        "id": 1,
        "title": "Mathematics Quiz 1"
      },
      "score_percentage": 85,
      "passed": true,
      "time_taken_seconds": 1800,
      "completed_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

#### GET /api/v1/analytics/study-streak
Get study streak data (consecutive days with activity).

**Method:** GET
**URL:** https://future-academy.test/api/v1/analytics/study-streak
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Study streak retrieved successfully",
  "data": {
    "current_streak": 7,
    "last_activity_date": "2024-01-15T10:30:00Z"
  }
}
```

### Lesson Endpoints

#### GET /api/v1/lessons?subject_id={id}
Get lessons for a subject with user progress.

**Method:** GET
**URL:** https://future-academy.test/api/v1/lessons?subject_id=1
**Headers:** Authorization: Bearer YOUR_TOKEN
**Query Parameters:** subject_id (required)

**Response:**
```json
{
  "message": "Lessons retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Introduction to Calculus",
      "description": "Learn the basics of calculus",
      "duration_seconds": 1800,
      "thumbnail_url": "https://example.com/thumb.jpg",
      "order": 1,
      "is_completed": false,
      "progress_percentage": 30,
      "current_time_seconds": 540,
      "time_spent_seconds": 540
    }
  ]
}
```

#### GET /api/v1/lessons/{id}
Get lesson details with video URL and progress.

**Method:** GET
**URL:** https://future-academy.test/api/v1/lessons/1
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Lesson details retrieved successfully",
  "data": {
    "id": 1,
    "title": "Introduction to Calculus",
    "description": "Learn the basics of calculus",
    "video_url": "https://example.com/video.mp4",
    "duration_seconds": 1800,
    "thumbnail_url": "https://example.com/thumb.jpg",
    "order": 1,
    "subject": {
      "id": 1,
      "name": "Mathematics",
      "code": "MATH",
      "slug": "mathematics"
    },
    "progress": {
      "is_completed": false,
      "progress_percentage": 30,
      "current_time_seconds": 540,
      "time_spent_seconds": 540
    }
  }
}
```

#### POST /api/v1/lessons/{id}/progress
Update lesson progress (current_time, progress_percentage, time_spent).

**Method:** POST
**URL:** https://future-academy.test/api/v1/lessons/1/progress
**Headers:** Authorization: Bearer YOUR_TOKEN
**Body:**
```json
{
  "current_time_seconds": 540,
  "progress_percentage": 30,
  "time_spent_seconds": 540,
  "is_completed": false
}
```

**Response:**
```json
{
  "message": "Lesson progress updated successfully",
  "data": {
    "lesson_id": 1,
    "progress_percentage": 30,
    "is_completed": false
  }
}
```

#### POST /api/v1/lessons/{id}/complete
Mark lesson as completed.

**Method:** POST
**URL:** https://future-academy.test/api/v1/lessons/1/complete
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Lesson marked as completed successfully",
  "data": {
    "lesson_id": 1,
    "is_completed": true,
    "completed_at": "2024-01-15T10:30:00Z"
  }
}
```

### Quiz Endpoints

#### GET /api/v1/quizzes?subject_id={id}&type={type}
Get available quizzes with optional filters.

**Method:** GET
**URL:** https://future-academy.test/api/v1/quizzes?subject_id=1&type=practice
**Headers:** Authorization: Bearer YOUR_TOKEN
**Query Parameters:** subject_id (optional), type (optional)

**Response:**
```json
{
  "message": "Quizzes retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Mathematics Quiz 1",
      "description": "Test your math skills",
      "type": "practice",
      "duration_minutes": 30,
      "question_count": 20,
      "is_free": true,
      "subject": {
        "id": 1,
        "name": "Mathematics",
        "code": "MATH"
      }
    }
  ]
}
```

#### GET /api/v1/quizzes/{id}
Get quiz details with questions and options.

**Method:** GET
**URL:** https://future-academy.test/api/v1/quizzes/1
**Headers:** Authorization: Bearer YOUR_TOKEN

**Response:**
```json
{
  "message": "Quiz details retrieved successfully",
  "data": {
    "id": 1,
    "title": "Mathematics Quiz 1",
    "description": "Test your math skills",
    "type": "practice",
    "duration_minutes": 30,
    "question_count": 20,
    "subject": {
      "id": 1,
      "name": "Mathematics",
      "code": "MATH"
    },
    "questions": [
      {
        "id": 1,
        "question_text": "What is 2 + 2?",
        "question_image": null,
        "explanation": "2 + 2 = 4",
        "options": [
          {
            "id": 1,
            "label": "A",
            "option_text": "3",
            "option_image": null
          },
          {
            "id": 2,
            "label": "B",
            "option_text": "4",
            "option_image": null
          }
        ]
      }
    ]
  }
}
```

#### POST /api/v1/quizzes/{id}/start
Create a new quiz attempt with optional question count and shuffle.

**Method:** POST
**URL:** https://future-academy.test/api/v1/quizzes/1/start
**Path Parameters:** `id` = quiz ID from the `quizzes` table
**Headers:** `Content-Type: application/json`, `Authorization: Bearer YOUR_TOKEN`
**Body** (optional; both fields use the quiz's defaults if omitted):
```json
{
  "question_count": 20,
  "shuffle": true
}
```
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `question_count` | integer | no | `min: 1`, `max: 100` |
| `shuffle` | boolean | no | Randomizes question order |

**Response:**
```json
{
  "message": "Quiz started successfully",
  "data": {
    "attempt_id": 1,
    "quiz_id": 1,
    "total_questions": 20,
    "question_order": [1, 5, 3, 8, 2],
    "started_at": "2024-01-15T10:30:00Z"
  }
}
```

#### POST /api/v1/quiz-attempts/{id}/submit
Submit quiz answers and calculate score.

**Method:** POST
**URL:** https://future-academy.test/api/v1/quiz-attempts/1/submit
**Path Parameters:** `id` = `quiz_attempts.id` from the start response
**Headers:** `Content-Type: application/json`, `Authorization: Bearer YOUR_TOKEN`
**Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "option_id": 2,
      "time_spent_seconds": 30
    }
  ]
}
```
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `answers` | array | yes | `min: 1` item |
| `answers.*.question_id` | integer | yes | Must exist in `questions` table |
| `answers.*.option_id` | integer | yes | Must exist in `options` table |
| `answers.*.time_spent_seconds` | integer | no | `min: 0`, defaults to `0` |

**Validation Errors:**
- `Answers array is required.` — `answers` is missing or not an array
- `At least one answer is required.` — `answers` is empty
- `Question ID is required for each answer.` — `question_id` is missing
- `One or more questions do not exist.` — a `question_id` does not exist in the `questions` table
- `Option ID is required for each answer.` — `option_id` is missing
- `One or more options do not exist.` — an `option_id` does not exist in the `options` table
- `Time spent must be an integer.` — `time_spent_seconds` is not numeric
- `Time spent must be at least 0.` — `time_spent_seconds` is negative

**Response:**
```json
{
  "message": "Quiz submitted successfully",
  "data": {
    "attempt_id": 1,
    "score_percentage": 80,
    "correct_answers": 16,
    "total_questions": 20,
    "passed": true,
    "time_taken_seconds": 1800
  }
}
```

#### GET /api/v1/quiz-attempts/{id}/results
Get attempt results with detailed answer breakdown.

**Method:** GET
**URL:** https://future-academy.test/api/v1/quiz-attempts/1/results
**Path Parameters:** `id` = `quiz_attempts.id`
**Headers:** `Content-Type: application/json`, `Authorization: Bearer YOUR_TOKEN`

**Response:**
```json
{
  "message": "Quiz results retrieved successfully",
  "data": {
    "id": 1,
    "quiz": {
      "id": 1,
      "title": "Mathematics Quiz 1"
    },
    "subject": {
      "id": 1,
      "name": "Mathematics"
    },
    "score_percentage": 80,
    "correct_answers": 16,
    "total_questions": 20,
    "passed": true,
    "time_taken_seconds": 1800,
    "started_at": "2024-01-15T10:30:00Z",
    "completed_at": "2024-01-15T11:00:00Z",
    "answers": [
      {
        "question_id": 1,
        "question_text": "What is 2 + 2?",
        "selected_option_id": 2,
        "selected_option_label": "B",
        "is_correct": true,
        "explanation": "2 + 2 = 4",
        "time_spent_seconds": 30
      }
    ]
  }
}
```
