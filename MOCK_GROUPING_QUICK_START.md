# Mock Exam Grouping - Quick Reference Guide

## For Users

### How to Take a Mock Exam with Batches

1. **Go to Mock Setup** → Navigate to `/mock`
2. **Select Exam Type** (e.g., JAMB, WAEC/NECO)
3. **Select a Single Subject** (e.g., Further Mathematics)
4. **Click "📚 Browse Mock Groups"** button
5. **Select Your Mock**:
   - 🔵 **Mock 1**: First batch (typically 40 questions)
   - 🔵 **Mock 2**: Second batch (typically 40 questions)
   - 🔵 **Mock 3**: Third batch (typically 40 questions)
   - etc.
6. **Start Exam** → Click "Start Mock X"
7. **Complete Questions** within the time limit
8. **Submit When Done** to see results
9. **Return to select another mock** after completion

### Time Allocation
- **Per Mock Group**: 60 minutes
- **Per Question**: Approximately 90 seconds (60 min ÷ 40 questions)

### Features
- ✅ Progress through batches sequentially
- ✅ Take mocks in any order
- ✅ Track your scores per batch
- ✅ Randomized questions each attempt
- ✅ Instant feedback on answers

---

## For Administrators

### Managing Mock Groups

#### View Mock Groups
```bash
# SSH into your server and run:
cd /path/to/future-academy

# Check database directly for mock groups
php artisan db:show
# Query: SELECT * FROM mock_groups;
```

#### Create/Update Mock Groups
```bash
# Re-populate mock groups for all subjects
php artisan app:group-mock-questions

# With custom batch size (e.g., 50 questions per batch)
php artisan app:group-mock-questions --batch-size=50
```

#### Add New Mock Questions
1. Upload new mock questions via admin panel
2. Mark them with `is_mock = true`
3. Run the grouping command to auto-organize them:
```bash
php artisan app:group-mock-questions
```

### Database Queries

#### See all mock groups
```sql
SELECT 
  mg.id,
  s.name as subject,
  et.name as exam_type,
  mg.batch_number,
  mg.total_questions,
  COUNT(q.id) as actual_questions
FROM mock_groups mg
JOIN subjects s ON mg.subject_id = s.id
JOIN exam_types et ON mg.exam_type_id = et.id
LEFT JOIN questions q ON q.mock_group_id = mg.id
GROUP BY mg.id
ORDER BY mg.subject_id, mg.exam_type_id, mg.batch_number;
```

#### See questions in a specific mock group
```sql
SELECT 
  q.id,
  q.question_text,
  s.name as subject,
  mg.batch_number
FROM questions q
JOIN mock_groups mg ON q.mock_group_id = mg.id
JOIN subjects s ON q.subject_id = s.id
WHERE mg.subject_id = ? AND mg.exam_type_id = ?
ORDER BY mg.batch_number, q.id;
```

#### Count mocks per subject
```sql
SELECT 
  s.name as subject,
  et.name as exam_type,
  COUNT(DISTINCT mg.batch_number) as number_of_mocks,
  SUM(mg.total_questions) as total_questions
FROM mock_groups mg
JOIN subjects s ON mg.subject_id = s.id
JOIN exam_types et ON mg.exam_type_id = et.id
GROUP BY mg.subject_id, mg.exam_type_id;
```

---

## Configuration

### Batch Size
The default batch size is **40 questions per mock group**. To change this:

**File**: `app/Services/MockGroupService.php`
```php
const DEFAULT_BATCH_SIZE = 40; // Change this value
```

Or use the artisan command with `--batch-size` option:
```bash
php artisan app:group-mock-questions --batch-size=50
```

### Time Limit
Mock group time limit is set to **60 minutes**. To change:

**File**: `app/Livewire/Quizzes/MockQuiz.php`
```php
protected function loadFromMockGroup($groupId)
{
    // ...
    $this->timeLimit = 60; // Change this value (in minutes)
    // ...
}
```

---

## Troubleshooting

### "No mock groups available"
**Issue**: User sees a message that no mock groups exist for a subject.
**Solution**:
1. Verify questions are marked as `is_mock = true` in the database
2. Run: `php artisan app:group-mock-questions`
3. Verify grouping completed successfully

### Questions not appearing in mock
**Issue**: Questions assigned but not showing in the mock quiz.
**Solution**:
1. Check if questions are marked as `is_active = true`
2. Check if questions have `status = 'approved'`
3. Verify `subject_id` and `exam_type_id` match the mock group
4. Run: `php artisan app:group-mock-questions` to re-sync

### Time limit too short/long
**Issue**: Users feel rushed or have too much time.
**Solution**: Adjust time limit in `MockQuiz::loadFromMockGroup()` method. Default is 60 minutes for 40 questions.

### Users can't see "Browse Mock Groups" button
**Issue**: Button doesn't appear when selecting a single subject.
**Solution**:
1. Ensure mock groups exist: `SELECT COUNT(*) FROM mock_groups WHERE subject_id = ? AND exam_type_id = ?;`
2. Clear view cache: `php artisan view:clear`
3. Hard refresh browser (Ctrl+Shift+R)

---

## Performance Tips

1. **Run grouping during off-hours**: Large datasets might take time
   ```bash
   php artisan app:group-mock-questions &
   ```

2. **Monitor grouping progress**: Check database for new records
   ```bash
   WATCH 'SELECT COUNT(*) FROM mock_groups;'
   ```

3. **Cache quiz data**: Leverage Redis for faster loading
   - Mock questions are cached per user session
   - Clear cache if issues persist: `php artisan cache:clear`

---

## API Endpoints (for Frontend Integration)

### Get available mock groups
```
GET /mock/groups?exam_type={id}&subject={id}
```

### Start a mock from a group
```
GET /mock/quiz?group={id}
```

### Get previous attempts (if implemented)
```
GET /api/mock/{groupId}/attempts
```

---

## Future Enhancements Roadmap

- [ ] **Series Mode**: Start Mock 1, auto-advance to Mock 2
- [ ] **Performance Analytics**: Track scores across mocks
- [ ] **Difficulty Levels**: Organize mocks by difficulty
- [ ] **Adaptive Selection**: AI suggests which mock to take next
- [ ] **Peer Comparison**: See average scores vs. other students
- [ ] **Certificate Milestones**: Unlock achievements after completing all mocks
