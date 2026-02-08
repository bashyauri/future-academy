# Parent Dashboard Authentication & Registration Guide

## Overview
Parents (Guardians) can register, login, and access a dedicated dashboard showing their linked children's progress. The system uses **Spatie Laravel Permissions** for robust RBAC (Role-Based Access Control).

---

## How to Register as a Parent

### Step 1: Go to Registration
Navigate to: `/register`

### Step 2: Fill the Form
```
Name:           Enter your full name
Email:          Your email address
Password:       Create a strong password
Confirm Pwd:    Re-enter password
I am a...:      Select "Parent/Guardian - Managing student(s)"
```

### Step 3: Account Type Selection
The registration form presents three options:
- **Student** - For learners taking exams
- **Parent/Guardian** - For managing student(s) ✅ *Choose this*
- **Teacher** - For content creators

### Step 4: Submit
Click "Create account" → Email verification sent → Verify email → Login

---

## How to Login to Parent Dashboard

### Step 1: Go to Login
Navigate to: `/login`

### Step 2: Enter Credentials
```
Email:    Your registered email
Password: Your password
```

### Step 3: After Login
**Smart Routing:**
- If you registered as **Parent/Guardian** → Auto-redirected to **Parent Dashboard** ✓
- If you registered as **Student** → Auto-redirected to **Student Dashboard**
- If you registered as **Teacher** → Auto-redirected to **Teacher Dashboard**

### Alternative: Direct Parent Dashboard Access
Parents can directly access: `/parent-dashboard`
- This route has **explicit role protection** (`role:guardian` middleware)
- Non-parents will see: *403 Forbidden*

---

## Technical Implementation (Role-Based Access)

### 1. Registration Flow
```
User Registration Form
        ↓
CreateNewUser Action (validates account_type)
        ↓
User::create(['account_type' => 'guardian', ...])
        ↓
User Model Observer (syncs Spatie role)
        ↓
User gets role: 'guardian' (Spatie)
```

### 2. Authentication Flow
```
Login → Auth Guard
        ↓
User Retrieved from DB (with account_type & roles)
        ↓
Redirect to /dashboard
        ↓
Smart Router:
  - hasRole('guardian') → ParentIndex Component
  - else → StudentIndex Component
        ↓
Parent sees: Children progress, subscriptions, analytics
```

### 3. Role-Based Middleware Stack

**Authorization with Spatie:**
```php
// Routes protected by explicit role check
Route::get('/parent-dashboard', ParentIndex::class)
    ->middleware(['auth', 'verified', 'role:guardian']);
```

**Two-layer protection:**
1. **account_type**: Quick check on User model (`$user->isParent()`)
2. **Spatie Role**: Database-backed role check (`$user->hasRole('guardian')`)

---

## Security Features

### ✅ Best Practices Implemented

1. **Dual Role Verification**
   - Account type in `users.account_type` column
   - Spatie role in `roles` + `role_user` tables
   - Both checked for consistency

2. **Automatic Role Syncing**
   - User model observers keep roles in sync with account_type
   - If account_type changes → roles automatically update

3. **Middleware Protection**
   - Auth routes: `middleware(['auth', 'verified'])`
   - Parent routes: `middleware(['auth', 'verified', 'role:guardian'])`
   - Spatie: `role:guardian` middleware checks permissions

4. **Smart Routing**
   - Single `/dashboard` route serves all user types
   - Intelligent component selection based on role
   - Fallback: If role missing → defaults to student dashboard

5. **Account Type Validation**
   - Registration form only allows: `student`, `guardian`, `teacher`, `uploader`
   - Invalid types rejected at validation layer

---

## User Workflows

### Scenario 1: Parent Registering for First Time
```
1. Click "Create account"
2. Fill form with account_type = "parent/guardian"
3. Email verified
4. Login with credentials
5. Auto-redirected to parent dashboard
6. Sees "No Linked Students Yet"
7. Can manage students in profile
```

### Scenario 2: Parent Logging In
```
1. Navigate to /login
2. Enter email/password
3. System checks:
   - account_type = 'guardian' ✓
   - hasRole('guardian') ✓
4. Dashboard route checks role
5. Returns ParentIndex component
6. Shows: Children, progress, subscription
```

### Scenario 3: Non-Parent Accessing Parent Route
```
1. Student tries: /parent-dashboard
2. Middleware checks: role:guardian
3. User doesn't have role → 403 Forbidden
4. Alternative: /dashboard → Shows student dashboard (smart routing)
```

---

## File References

### Key Files Modified:
- **app/Actions/Fortify/CreateNewUser.php** - Validates & stores account_type
- **app/Http/Middleware/EnsureParentRole.php** - Custom parent role middleware
- **routes/web.php** - Role-based route definitions
- **resources/views/livewire/auth/register.blade.php** - Registration form with account type selector
- **app/Livewire/Dashboard/ParentIndex.php** - Parent dashboard component
- **app/Models/User.php** - isParent() method, role syncing observers

### Role Sync Location:
- **app/Models/User.php** (lines ~90-110)
  - Observer syncs account_type to Spatie roles on create/update
  - Ensures dual-layer protection always works

---

## Database Schema

### users table
```sql
- id
- name
- email
- password
- account_type  ← 'student' | 'guardian' | 'teacher' | 'uploader'
- email_verified_at
- trial_ends_at
- created_at
- updated_at
```

### roles table (Spatie)
```sql
- id
- name  ← 'student' | 'guardian' | 'teacher' | 'uploader'
- guard_name
- created_at
- updated_at
```

### role_user table (Spatie)
```sql
- role_id
- model_id  ← user.id
- model_type  ← 'App\Models\User'
```

### parent_student table (Many-to-Many)
```sql
- parent_id
- student_id
- is_active
- linked_at
- created_at
- updated_at
```

---

## API Reference

### Check if User is Parent
```php
$user = auth()->user();

// Option 1: Account type
if ($user->isParent()) { ... }

// Option 2: Spatie role
if ($user->hasRole('guardian')) { ... }

// Option 3: Combined (redundant check)
if ($user->isParent() || $user->hasRole('guardian')) { ... }
```

### Get Parent's Children
```php
$parent = auth()->user();
$children = $parent->children();  // Livewire relationship
// Returns: Collection of linked students with progress
```

### Route Protection
```php
// Explicit parent-only route
Route::get('/parent-only', fn() => view(...))
    ->middleware('role:guardian');

// Smart routing (allows both, shows appropriate component)
Route::get('/dashboard', function() {
    if (auth()->user()->isParent()) {
        return view('parent-dashboard');
    }
    return view('student-dashboard');
});
```

---

## Troubleshooting

### Issue: Parent logs in but sees student dashboard
**Solution:** Check that `account_type` is saved as 'guardian'
```php
// In tinker:
$user = User::find(1);
dd($user->account_type, $user->roles);
```

### Issue: hasRole('guardian') returns false
**Solution:** Run role sync command or trigger user update
```php
// Manual sync:
$user->syncRoles(['guardian']);

// Or trigger observer:
$user->account_type = 'guardian';
$user->save();
```

### Issue: Cannot access parent dashboard (403)
**Solution:** Check middleware chain
1. User authenticated? → `auth` middleware
2. Email verified? → `verified` middleware
3. Has 'guardian' role? → `role:guardian` middleware
```php
// Debug:
auth()->user()->hasRole('guardian')  // Must be true
```

---

## Summary

✅ **Parent Registration:** Select "Parent/Guardian" during signup
✅ **Parent Login:** Regular login → Auto-routed to parent dashboard
✅ **Automatic Role Assignment:** account_type → Spatie role syncing
✅ **Dual Protection:** Account type + Spatie roles
✅ **Smart Routing:** Single dashboard serves all roles
✅ **Explicit Routes:** /parent-dashboard for strict access
✅ **RBAC Ready:** Full Spatie permissions integration

**Next Steps:**
1. Create parent profile UI for linking students
2. Add permission gates: `view_children`, `manage_children`
3. Implement child linking form
4. Add subscription management for parents
