# TakeQuiz - Testing Guide

## Quick Start Testing

### 1. Basic Functionality Test (5 minutes)

**Steps:**
1. Navigate to any quiz that uses TakeQuiz component
2. Click "Start Quiz"
3. Select an answer - **Verify instant feedback (< 5ms)**
4. Click "Next" - **Verify instant navigation**
5. Click a question number in sidebar - **Verify instant jump**
6. Wait 10 seconds - **Verify auto-save (no visible delay)**
7. Click "Submit Quiz" - **Verify results appear**

**Expected Results:**
✅ Answer selection instant (green/red highlighting)  
✅ Navigation instant (no loading state)  
✅ Progress counter updates immediately  
✅ No lag or delay during quiz  
✅ Results show correct score  

---

## Detailed Testing Scenarios

### Test 1: Answer Selection Performance

**Objective:** Verify instant client-side feedback

**Steps:**
1. Start quiz
2. Open browser DevTools → Network tab
3. Select answer to Question 1
4. **Verify:** No network request fired
5. **Verify:** Green border appears instantly (< 5ms)
6. **Verify:** Progress counter increments immediately

**Pass Criteria:**
- ✅ No Livewire XHR request on answer click
- ✅ Visual feedback instant
- ✅ Console shows no errors

---

### Test 2: Cache-Only Auto-Save

**Objective:** Verify database not hit during quiz

**Steps:**
1. Enable query logging:
   ```php
   // Add to TakeQuiz.php temporarily
   DB::listen(function($query) {
       \Log::info('DB Query: ' . $query->sql);
   });
   ```
2. Start quiz
3. Answer 3 questions
4. Wait 10 seconds (auto-save triggers)
5. Check logs: `storage/logs/laravel.log`

**Pass Criteria:**
- ✅ No INSERT queries to `quiz_answers` table during quiz
- ✅ Only cache operations logged
- ✅ No database writes until submit

---

### Test 3: Navigation Speed

**Objective:** Verify client-side navigation

**Steps:**
1. Start quiz with 10+ questions
2. Answer question 1
3. Click "Next" button rapidly 5 times
4. **Verify:** Questions change instantly each time
5. Click question grid numbers randomly
6. **Verify:** Jumps to question instantly

**Pass Criteria:**
- ✅ No loading state during navigation
- ✅ Question content changes < 5ms
- ✅ Current question highlighted correctly in sidebar

---

### Test 4: Submit and Database Persistence

**Objective:** Verify answers save to DB on submit only

**Steps:**
1. Start quiz
2. Answer 5 questions
3. Check database:
   ```sql
   SELECT COUNT(*) FROM quiz_answers WHERE quiz_attempt_id = <attempt_id>;
   -- Should be 0 during quiz
   ```
4. Click "Submit Quiz"
5. Check database again:
   ```sql
   SELECT COUNT(*) FROM quiz_answers WHERE quiz_attempt_id = <attempt_id>;
   -- Should be 5 after submit
   ```

**Pass Criteria:**
- ✅ 0 answers in DB during quiz
- ✅ All answers in DB after submit
- ✅ Correct answer IDs stored
- ✅ Score calculated correctly

---

### Test 5: Exit and Save

**Objective:** Verify answers save on exit

**Steps:**
1. Start quiz
2. Answer 3 questions
3. Check database (should be 0 answers)
4. Click "Exit Quiz"
5. Check database (should have 3 answers)
6. Check attempt status:
   ```sql
   SELECT status FROM quiz_attempts WHERE id = <attempt_id>;
   -- Should be 'abandoned'
   ```

**Pass Criteria:**
- ✅ Answers saved to DB on exit
- ✅ Attempt marked as 'abandoned'
- ✅ Cache cleared

---

### Test 6: Browser Refresh Recovery

**Objective:** Verify state restored from cache

**Steps:**
1. Start quiz
2. Answer 5 questions
3. Note current question number
4. Press F5 (browser refresh)
5. **Verify:** Quiz resumes at same question
6. **Verify:** All 5 answers still selected

**Pass Criteria:**
- ✅ Current question index restored
- ✅ Previous answers preserved
- ✅ Progress counter shows correct count
- ✅ Can continue quiz seamlessly

---

### Test 7: Timer Functionality (Timed Quizzes)

**Objective:** Verify timer works with optimization

**Steps:**
1. Create a timed quiz (5 minutes)
2. Start quiz
3. Answer 2 questions
4. Wait until timer reaches 0
5. **Verify:** Quiz auto-submits
6. **Verify:** Answers saved to database
7. Check attempt:
   ```sql
   SELECT status FROM quiz_attempts WHERE id = <attempt_id>;
   -- Should be 'timed_out'
   ```

**Pass Criteria:**
- ✅ Timer counts down correctly
- ✅ Auto-submit at 0 seconds
- ✅ Status marked 'timed_out'
- ✅ Score calculated for answered questions

---

### Test 8: Alpine.js State Consistency

**Objective:** Verify Alpine.js state syncs with Livewire

**Steps:**
1. Start quiz
2. Open browser console
3. Run:
   ```javascript
   // Check Alpine state
   Alpine.$data(document.querySelector('[x-data]'))
   ```
4. **Verify:** `questions` array populated
5. **Verify:** `answers` object updates on selection
6. Select answer in UI
7. Run command again
8. **Verify:** `answers` object reflects selection

**Pass Criteria:**
- ✅ Alpine state initialized correctly
- ✅ `answers` object updates on selection
- ✅ `currentQuestionIndex` matches UI
- ✅ No console errors

---

### Test 9: Performance Comparison

**Objective:** Measure actual performance improvement

**Setup:**
```javascript
// Add to browser console
let startTime, endTime;

// Hook into click event
document.querySelector('[x-data]').addEventListener('click', (e) => {
    if (e.target.closest('button')?.textContent.includes('option')) {
        startTime = performance.now();
        requestAnimationFrame(() => {
            endTime = performance.now();
            console.log('Feedback time:', endTime - startTime, 'ms');
        });
    }
});
```

**Steps:**
1. Start quiz
2. Click answer option
3. Check console for timing

**Pass Criteria:**
- ✅ Feedback time < 5ms
- ✅ Significantly faster than 100ms (old Livewire)

---

### Test 10: Edge Cases

**Test 10a: No Answers Submitted**
1. Start quiz
2. Don't answer any questions
3. Click "Submit Quiz"
4. **Verify:** Results show 0% score
5. **Verify:** No errors

**Test 10b: All Answers Submitted**
1. Start quiz
2. Answer all questions
3. **Verify:** Submit button appears on last question
4. Submit quiz
5. **Verify:** Correct score calculated

**Test 10c: Network Interruption**
1. Start quiz
2. Answer 3 questions
3. Disable network (DevTools → Network → Offline)
4. Answer 2 more questions
5. **Verify:** UI still works (Alpine.js client-side)
6. Re-enable network
7. Wait 10 seconds (auto-save)
8. Submit quiz
9. **Verify:** All 5 answers saved

**Pass Criteria:**
- ✅ All edge cases handled gracefully
- ✅ No JavaScript errors
- ✅ Data integrity maintained

---

## Performance Benchmarks

Expected performance metrics:

| Metric | Target | Method |
|--------|--------|--------|
| Answer selection | < 5ms | Browser console timing |
| Question navigation | < 5ms | Browser console timing |
| Progress update | < 1ms | Visual observation |
| Auto-save | No UI lag | Cache write only |
| Submit time | < 500ms | Database write batch |

---

## Automated Testing

### Laravel Feature Test

Create: `tests/Feature/TakeQuizOptimizationTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class TakeQuizOptimizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function answers_are_cached_not_saved_to_db_during_quiz()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()
            ->has(Question::factory()->count(5))
            ->create();

        $this->actingAs($user);

        $component = Livewire::test('quizzes.take-quiz', ['id' => $quiz->id])
            ->call('startQuiz');

        $attempt = $component->get('attempt');
        $firstQuestion = $component->get('questions')[0];

        // Answer question (should only cache)
        $component->call('answerQuestion', $firstQuestion->id, $firstQuestion->options->first()->id);

        // Verify no DB write yet
        $this->assertDatabaseCount('quiz_answers', 0);

        // Verify cache has data
        $cached = cache()->get("quiz_attempt_{$attempt->id}");
        $this->assertNotNull($cached);
        $this->assertArrayHasKey($firstQuestion->id, $cached['answers']);
    }

    /** @test */
    public function answers_save_to_database_on_submit()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()
            ->has(Question::factory()->count(3))
            ->create();

        $this->actingAs($user);

        $component = Livewire::test('quizzes.take-quiz', ['id' => $quiz->id])
            ->call('startQuiz');

        $questions = $component->get('questions');

        // Answer all questions
        foreach ($questions as $question) {
            $component->call('answerQuestion', $question->id, $question->options->first()->id);
        }

        // Verify no DB writes yet
        $this->assertDatabaseCount('quiz_answers', 0);

        // Submit quiz
        $component->call('submitQuiz');

        // Verify DB now has all answers
        $this->assertDatabaseCount('quiz_answers', 3);
    }

    /** @test */
    public function autosave_interval_is_10_seconds()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->has(Question::factory())->create();

        $this->actingAs($user);

        $component = Livewire::test('quizzes.take-quiz', ['id' => $quiz->id]);

        $this->assertEquals(10, $component->get('autoSaveInterval'));
    }
}
```

**Run tests:**
```bash
php artisan test --filter=TakeQuizOptimizationTest
```

---

## Browser Testing Checklist

Test in multiple browsers:

### Chrome/Edge
- [ ] Answer selection works
- [ ] Navigation instant
- [ ] Auto-save no lag
- [ ] Submit saves correctly

### Firefox
- [ ] Same as Chrome tests
- [ ] Alpine.js compatibility verified

### Safari (if available)
- [ ] Same as Chrome tests
- [ ] No webkit-specific issues

### Mobile Browsers
- [ ] Touch interactions work
- [ ] Answer selection responsive
- [ ] Sidebar hidden on mobile
- [ ] Submit button accessible

---

## Monitoring in Production

### Key Metrics to Watch

1. **Answer Selection Time**
   - Add timing to Alpine.js:
   ```javascript
   selectAnswer(questionId, optionId) {
       const start = performance.now();
       this.answers[questionId] = optionId;
       const end = performance.now();
       if (end - start > 10) {
           console.warn('Slow answer selection:', end - start, 'ms');
       }
       // ... rest of method
   }
   ```

2. **Database Load**
   - Monitor `quiz_answers` INSERT queries
   - Should drop ~95% during quiz times
   - Spike only during submit times

3. **Cache Hit Rate**
   - Monitor Redis cache hits
   - Should increase for `quiz_attempt_*` keys

4. **Error Rate**
   - Monitor JavaScript console errors
   - Should remain at baseline (no increase)

---

## Rollback Procedure

If issues found in production:

### Step 1: Quick Revert
```bash
# Revert files
git checkout HEAD~1 app/Livewire/Quizzes/TakeQuiz.php
git checkout HEAD~1 resources/views/livewire/quizzes/take-quiz.blade.php

# Clear caches
php artisan cache:clear
php artisan view:clear
```

### Step 2: Verify Rollback
- Test quiz functionality
- Verify answers save correctly
- Check no errors in logs

### Step 3: Investigate Issue
- Review error logs
- Check browser console
- Identify root cause

---

## Success Criteria Summary

### Must Pass (Critical)
- ✅ Answer selection < 5ms
- ✅ No DB writes during quiz
- ✅ All answers saved on submit
- ✅ Score calculated correctly
- ✅ No JavaScript errors

### Should Pass (Important)
- ✅ Auto-save every 10 seconds
- ✅ Browser refresh recovery
- ✅ Timer functionality (if applicable)
- ✅ Exit saves answers

### Nice to Have (Optional)
- ✅ Performance metrics logging
- ✅ Graceful error handling
- ✅ Network interruption recovery

---

## Conclusion

**TakeQuiz is optimized and ready for production testing.**

**Next Steps:**
1. Run manual tests (30 minutes)
2. Run automated tests
3. Deploy to staging
4. Monitor for 24 hours
5. Deploy to production

**Support:**
- Documentation: `TAKEQUIZ_OPTIMIZATION_COMPLETE.md`
- All quiz types: `ALL_QUIZ_TYPES_OPTIMIZATION_SUMMARY.md`

---

**Last Updated:** $(date)  
**Version:** 1.0  
**Status:** Ready for testing ✅
