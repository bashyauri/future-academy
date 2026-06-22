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
