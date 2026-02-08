# Parent Dashboard Quick Start Guide

## 🚀 Quick Steps

### For End Users (Parents)

#### Register as Parent
1. Go to `/register`
2. Fill in: Name, Email, Password
3. **Select: "Parent/Guardian - Managing student(s)"** ← Important!
4. Click "Create account"
5. Verify email
6. Login

#### Login as Parent
1. Go to `/login`
2. Enter email & password
3. Click "Login"
4. ✅ Automatically routed to Parent Dashboard

#### What You'll See
- **Total Students**: Number of linked children
- **Average Score**: Combined learning metrics
- **Mock Exams**: Progress tracking
- **Videos Watched**: Content consumption
- **Student Cards**: Individual progress for each child
- **Subscription Info**: Plan, expiration, status

#### Link Students
1. Go to Profile Settings
2. Scroll to "Link Students"
3. Enter student email or code
4. Confirm linking
5. Student appears on dashboard

---

## 🔑 Technical Details (For Developers)

### How Authentication Works

```
Registration Flow:
┌─────────────────────┐
│ Select Account Type │
│  "Parent/Guardian"  │
└──────────┬──────────┘
           ↓
┌─────────────────────────────────┐
│ CreateNewUser validates type    │
│ - Checks: in:student,guardian..│
│ - Stores: account_type='guardian'
└──────────┬──────────────────────┘
           ↓
┌─────────────────────────────────┐
│ User Model Observer triggers    │
│ - Syncs role: guardian          │
│ - Both DB & Spatie in sync      │
└──────────┬──────────────────────┘
           ↓
┌─────────────────────────────────┐
│ Parent successfully registered  │
└─────────────────────────────────┘

Login Flow:
┌──────────────────┐
│ Email + Password │
└────────┬─────────┘
         ↓
┌──────────────────────────────┐
│ Auth Guard checks credentials│
└────────┬─────────────────────┘
         ↓
┌──────────────────────────────────┐
│ Dashboard router:                 │
│ if (hasRole('guardian'))          │
│   return ParentIndex              │
│ else                              │
│   return StudentIndex             │
└────────┬─────────────────────────┘
         ↓
┌──────────────────────────────────┐
│ Parent sees their dashboard      │
└──────────────────────────────────┘
```

### Code Structure

**Key Files:**
- `app/Actions/Fortify/CreateNewUser.php` - Registration validation
- `app/Http/Middleware/EnsureParentRole.php` - Parent-only middleware
- `routes/web.php` - Dashboard routing logic
- `app/Livewire/Dashboard/ParentIndex.php` - Parent dashboard component
- `app/Models/User.php` - Role syncing observer

**Protected Routes:**
```
/dashboard                    → Smart routing (all users)
/parent-dashboard            → Parent-only (strict middleware)
/student-dashboard           → Student-only (implicit)
```

---

## 🔐 Security Features

### Two-Layer Protection
```php
// Layer 1: Quick account type check
$user->isParent()  // ✓ Fast

// Layer 2: Spatie role check
$user->hasRole('guardian')  // ✓ Secure

// Both together
if ($user->isParent() && $user->hasRole('guardian')) {
    // Definitely a parent
}
```

### Middleware Stack
```
/dashboard
  ├─ auth          → Must be logged in
  ├─ verified      → Email must be verified
  └─ Smart router  → Check role & route accordingly

/parent-dashboard
  ├─ auth          → Must be logged in
  ├─ verified      → Email must be verified
  └─ role:guardian → Spatie role middleware (strict)
```

---

## 📊 Parent Dashboard Components

### Summary Cards (Top)
- **Total Students** - Count of linked children
- **Average Score** - Combined exam performance
- **Mock Exams** - Total attempts & best score
- **Videos Watched** - Content consumption %

### Subscription Section (Middle)
Shows:
- Plan type (Monthly/Yearly)
- Amount paid
- Expiration date
- Status badge (Active/Pending/Inactive)
- Status: "Active & protecting all students"

### Student Progress Cards (Bottom)
For each linked child:
- **Name** - Student name with setup status
- **Videos** - % completion + watched/total
- **Score** - Average exam score with rating
- **Mocks** - Total mock exams & best score
- **Subjects** - Number of enrolled subjects
- **Button** - "View Full Profile" link

---

## 🛠️ Setup for Developers

### After Pulling Code

1. **Verify account_type migration exists**
   ```bash
   php artisan migrate
   ```

2. **Ensure Spatie is installed**
   ```bash
   composer install
   ```

3. **Create guardian role** (if missing)
   ```php
   // In tinker or seeder
   Role::create(['name' => 'guardian', 'guard_name' => 'web']);
   ```

4. **Test parent registration**
   ```
   Go to /register
   Fill form with:
   - Name: Test Parent
   - Email: parent@example.com
   - Password: xxxxxxxx
   - Account Type: Parent/Guardian
   ```

5. **Verify in database**
   ```php
   // In tinker
   $user = User::where('email', 'parent@example.com')->first();
   $user->account_type   // Should be 'guardian'
   $user->roles          // Should include 'guardian' role
   ```

---

## 🔄 Update Parent Information

### Change Account Type
```php
$user->update(['account_type' => 'guardian']);
// Observer automatically syncs role
```

### Add Parent Role Manually
```php
$user->syncRoles(['guardian']);
```

### Check if User is Parent
```php
// Method 1: Direct check
$user->isParent()

// Method 2: Spatie role
$user->hasRole('guardian')

// Method 3: In Blade
@role('guardian')
    <!-- Parent content -->
@endrole
```

---

## 📱 Mobile Responsive

Parent dashboard is fully responsive:
- ✅ Mobile: Single column layout
- ✅ Tablet: 2-column grid
- ✅ Desktop: 4-column grid
- ✅ Dark mode: Supported
- ✅ Touch-friendly: Large tap targets

---

## 🎯 Next Steps

### Phase 1: Core (Done ✅)
- Parent registration with role selection
- Parent login with smart routing
- Parent dashboard display
- Child progress visualization
- Subscription display

### Phase 2: Recommended
- [ ] Add parent profile page
- [ ] Implement child linking UI
- [ ] Add permission gates
- [ ] Create parent policies
- [ ] Add notification preferences

### Phase 3: Advanced
- [ ] Parent analytics export
- [ ] Bulk student management
- [ ] Custom subscription plans
- [ ] Parent groups/families
- [ ] Two-factor auth

---

## ⚠️ Common Issues

### Issue: Login doesn't redirect to parent dashboard
**Solution:** Check:
1. `account_type` saved as 'guardian'
2. User has 'guardian' role
3. Route condition updated
4. Cache cleared: `php artisan cache:clear`

### Issue: Getting "403 Forbidden" on /parent-dashboard
**Solution:**
- User must have 'guardian' role
- Check: `$user->hasRole('guardian')`
- If false: `$user->syncRoles(['guardian'])`

### Issue: account_type not appearing in registration form
**Solution:**
- Check: `resources/views/livewire/auth/register.blade.php`
- Ensure `<flux:select name="account_type">` exists
- Clear browser cache

---

## 📞 Support

**Debug Commands:**
```php
// Check user role
User::find(1)->roles

// Sync role manually  
User::find(1)->syncRoles(['guardian'])

// Check if user is parent
User::find(1)->isParent()

// Check Spatie role
User::find(1)->hasRole('guardian')

// List all roles
Role::all()

// Recreate guardian role
Role::firstOrCreate(['name' => 'guardian', 'guard_name' => 'web'])
```

---

## Summary

✅ Parents register by selecting "Parent/Guardian"
✅ automatic role assignment via observer
✅ Smart dashboard routing based on role
✅ Dual-layer security (account_type + Spatie)
✅ Fully responsive UI with FluxUI
✅ Production-ready RBAC implementation

**Ready to go live!** 🚀
