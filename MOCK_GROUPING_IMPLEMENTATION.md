# Mock Exam Grouping Implementation Summary

## Overview
Implemented a frontend-based mock question grouping system that allows users to browse and select individual mock exam batches (Mock 1, Mock 2, etc.) for a single subject, where each batch contains 40 questions by default.

## Architecture

### Models
- **MockGroup** (`app/Models/MockGroup.php`)
  - Represents a batch of mock questions for a subject-exam combination
  - Fields: `subject_id`, `exam_type_id`, `batch_number`, `total_questions`
  - Relationships: `subject()`, `examType()`, `questions()`

### Database Changes
1. **mock_groups table** (`2026_01_15_102606_create_mock_groups_table.php`)
   - Stores mock group metadata
   - Unique constraint on (subject_id, exam_type_id, batch_number)
   - Foreign keys with cascade delete

2. **questions table** (`2026_01_15_102640_add_mock_group_id_to_questions_table.php`)
   - Added `mock_group_id` column to link questions to mock groups
   - Nullable to maintain backward compatibility

### Services
- **MockGroupService** (`app/Services/MockGroupService.php`)
  - `groupMockQuestions()`: Groups mock questions into batches of 40
  - `getMockGroups()`: Retrieves all groups for a subject-exam combo
  - `getMockGroupByBatchNumber()`: Fetches a specific batch
  - `getGroupQuestions()`: Gets all questions in a mock group
  - `getNextGroup()`, `hasNextGroup()`, `getFirstGroup()`: Navigation helpers
  - **Batch Size**: Configurable, defaults to 40 questions per mock

### Commands
- **GroupMockQuestions** (`app/Console/Commands/GroupMockQuestions.php`)
  - Artisan command to populate mock groups for existing questions
  - `php artisan app:group-mock-questions [--batch-size=40]`
  - Command execution result: Successfully grouped 200 mock questions into 3 mock groups

### Frontend Components

#### MockGroupSelection (`app/Livewire/Quizzes/MockGroupSelection.php`)
- New Livewire component for browsing mock groups
- Route: `GET /mock/groups?exam_type={id}&subject={id}`
- Displays available mock batches in a grid layout
- Users can click "Start Mock X" to begin that specific batch
- Falls back to traditional mock setup if no groups exist

#### MockSetup (Updated) (`app/Livewire/Quizzes/MockSetup.php`)
- Added `selectSingleSubject()` method
- When a single subject is selected, users see a "📚 Browse Mock Groups" button
- Redirects to mock group selection page if groups exist
- Falls back to traditional session-based mock if no groups exist

#### MockQuiz (Updated) (`app/Livewire/Quizzes/MockQuiz.php`)
- Added support for `group` query parameter: `GET /mock/quiz?group={id}`
- New property: `$currentMockGroup` to track the active group
- `loadFromMockGroup()` method to load questions from a specific mock group
- 60-minute time limit for mock groups (configurable)
- Maintains backward compatibility with session-based mocks

### Views
1. **mock-group-selection.blade.php** (`resources/views/livewire/quizzes/mock-group-selection.blade.php`)
   - Grid layout showing available mock batches
   - "Start Mock X" button for each group
   - Displays question count per batch
   - Back button to return to setup

2. **mock-setup.blade.php** (Updated)
   - Conditional "📚 Browse Mock Groups" button when single subject is selected
   - Only appears if mock groups are available for the selected subject-exam combo

### Routes
- Added new route: `GET /mock/groups` → `MockGroupSelection` component
- Registered in `routes/web.php` under authenticated middleware

## User Flow

### Traditional Multi-Subject Mock (Unchanged)
1. User selects exam type
2. Selects 1-4 subjects
3. Clicks "Start Mock Exam"
4. Creates MockSession
5. Launches MockQuiz with all subjects

### New Single-Subject Mock Groups Flow
1. User selects exam type
2. Selects 1 subject
3. System checks if mock groups exist for this subject
4. Clicks "📚 Browse Mock Groups" button
5. Navigates to MockGroupSelection page
6. Sees available batches (Mock 1, Mock 2, Mock 3, etc.)
7. Clicks "Start Mock X"
8. Launches MockQuiz with only questions from that specific mock group
9. Can return to select another mock group after completing

## Database Population

### Current Status
- **Further Mathematics (JAMB)**:  3 mock groups (120 questions total)
- **Further Mathematics (WAEC/NECO)**: 3 mock groups (120 questions total)
- **Total**: 200 mock questions grouped into 6 mock batches

### How to Re-populate
```bash
php artisan app:group-mock-questions
# or with custom batch size:
php artisan app:group-mock-questions --batch-size=50
```

## Configuration

### Batch Size
- Default: 40 questions per mock group
- Configurable via `MockGroupService::DEFAULT_BATCH_SIZE`
- Or pass `--batch-size` parameter to artisan command

### Time Limit
- Mock groups: 60 minutes (1 question per ~90 seconds for 40 questions)
- Configurable in `MockQuiz::loadFromMockGroup()` method

## Next Steps / Future Enhancements

1. **Progress Tracking**: Track user progress through mock groups
2. **Mock Series**: Allow users to start Mock 1, then seamlessly continue to Mock 2
3. **Analytics**: Track performance across mock groups
4. **Recommendations**: Suggest next mock based on performance
5. **Adaptive Batching**: Auto-create batches based on difficulty level
6. **Previous Attempts**: Display mock group history and scores

## Testing Checklist

- ✅ Models created and migrations applied
- ✅ MockGroupService logic implemented
- ✅ Artisan command creates groups successfully
- ✅ MockGroupSelection component displays groups
- ✅ MockSetup shows "Browse Mock Groups" for single subjects
- ✅ MockQuiz loads questions from mock groups
- ✅ Routes configured correctly
- ✅ No syntax errors in PHP files

## Files Modified/Created

### Created
- `app/Models/MockGroup.php`
- `app/Services/MockGroupService.php`
- `app/Console/Commands/GroupMockQuestions.php`
- `app/Livewire/Quizzes/MockGroupSelection.php`
- `resources/views/livewire/quizzes/mock-group-selection.blade.php`
- `database/migrations/2026_01_15_102606_create_mock_groups_table.php`
- `database/migrations/2026_01_15_102640_add_mock_group_id_to_questions_table.php`

### Modified
- `app/Models/Question.php` - Added mock_group_id relationship
- `app/Livewire/Quizzes/MockSetup.php` - Added selectSingleSubject() method
- `app/Livewire/Quizzes/MockQuiz.php` - Added mock group support
- `resources/views/livewire/quizzes/mock-setup.blade.php` - Added Browse Groups button
- `routes/web.php` - Added mock.group-selection route

## Performance Considerations

- Mock groups are pre-computed via artisan command (no real-time calculation)
- Questions are fetched from database with caching support
- Grid layout allows quick visual scanning of available batches
- Single-subject selection reduces cognitive load compared to multi-subject setup

## Backward Compatibility

✅ All changes are backward compatible:
- Session-based mocks still work as before
- mock_group_id is nullable, allowing existing questions to work
- Traditional multi-subject flow unchanged
- Mock groups are an optional feature for single subjects
