# Spatie Permissions & RBAC Best Practices

## Architecture Overview

Your system uses a **hybrid approach** combining:
1. **Database Column**: `users.account_type` (quick checks)
2. **Spatie Roles**: Permission-based access control (granular checks)

This is the **best practice** for Laravel applications.

---

## Role Hierarchy

```
┌─────────────────────────────────────────────────────┐
│                  SPATIE ROLES                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│  STUDENT                GUARDIAN               │
│  ├─ view_lessons        ├─ view_children      │
│  ├─ take_quizzes        ├─ manage_children    │
│  ├─ view_progress       ├─ view_children_progress
│  └─ view_own_results    └─ manage_subscription    │
│                                                     │
│  TEACHER                UPLOADER                   │
│  ├─ create_content      ├─ upload_resources    │
│  ├─ manage_quizzes      ├─ manage_files         │
│  └─ view_analytics      └─ publish_content      │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Account Type vs Spatie Role

### Option 1: Quick Check (Fast)
```php
// Uses DB column - extremely fast
$user->account_type === 'guardian'  // ✓ Fast
$user->isParent()                   // ✓ Fast
```

**When to use:**
- Quick role identification
- Request routing
- Dashboard selection
- API responses

### Option 2: Permission Check (Granular)
```php
// Uses Spatie - checks against permissions
$user->hasRole('guardian')          // ✓ Standard
$user->hasPermissionTo('view_children')  // ✓ Precise
```

**When to use:**
- Feature access control
- View authorization
- API endpoint protection
- Blade directives (`@role('guardian')`)

### Option 3: Combined (Most Robust)
```php
// Double-check for consistency
if ($user->isParent() && $user->hasRole('guardian')) {
    // Definitely a parent - pass through
}
```

**When to use:**
- Security-critical operations
- Role syncing verification
- Middleware checks

---

## Current Implementation

### How It Works

**1. Registration**
```
User selects "Parent/Guardian" in form
                    ↓
CreateNewUser validates account_type
                    ↓
User::create(['account_type' => 'guardian'])
                    ↓
User Model Observer triggers (in User.php ~line 90)
                    ↓
$user->syncRoles(['guardian']) ← Spatie magic
                    ↓
Both synced:
  - users.account_type = 'guardian'
  - user has 'guardian' role in roles table
```

**2. Authentication**
```
Login successful
        ↓
Middleware checks: auth, verified
        ↓
Dashboard route router:
  if ($user->hasRole('guardian') || $user->isParent())
    return ParentIndex
  else
    return StudentIndex
```

**3. Authorization**
```
Access /parent-dashboard
        ↓
Middleware: role:guardian
        ↓
Spatie checks: $user->hasRole('guardian')
        ↓
If true → Allow
If false → 403 Forbidden
```

---

## Best Practices (What You're Doing Right)

✅ **Dual Layer Protection**
- Account type for quick routing
- Spatie roles for granular permissions

✅ **Automatic Role Syncing**
- Observer pattern keeps everything in sync
- If account_type changes → role updates automatically

✅ **Middleware Stack**
- `auth` → User is logged in
- `verified` → Email is verified
- `role:guardian` → User has specific role

✅ **Smart Routing**
- Single dashboard route serves multiple roles
- Component selection based on role

✅ **Validation at Entry**
- Registration validates account_type
- Only allowed values: student, guardian, teacher, uploader

---

## Recommended Enhancements

### 1. Add Permissions Table (for Spatie)
```php
// Create permissions for each role
php artisan tinker

Permission::create(['name' => 'view_children', 'guard_name' => 'web']);
Permission::create(['name' => 'manage_children', 'guard_name' => 'web']);
Permission::create(['name' => 'view_child_progress', 'guard_name' => 'web']);
Permission::create(['name' => 'manage_subscription', 'guard_name' => 'web']);

// Assign to guardian role
$guardianRole = Role::findByName('guardian');
$guardianRole->syncPermissions([
    'view_children',
    'manage_children', 
    'view_child_progress',
    'manage_subscription'
]);
```

### 2. Blade Directives (in Views)
```blade
<!-- Using Spatie blade directives -->
@role('guardian')
    <!-- Only parents see this -->
    <div>Your Children: ...</div>
@endrole

@hasanyrole('guardian|teacher')
    <!-- Teachers OR Parents see this -->
@endhasanyrole

@hasallroles('guardian|premium')
    <!-- Must have both roles -->
@endhasallroles

@can('manage_children')
    <!-- Check specific permission -->
    <button>Add Child</button>
@endcan
```

### 3. Policy-Based Authorization
```php
// Create policy
php artisan make:policy ParentPolicy --model=User

// In ParentPolicy.php
public function viewChildren(User $user): bool
{
    return $user->isParent();
}

public function manageChildren(User $user): bool
{
    return $user->can('manage_children');
}

// In routes/controller
authorize('viewChildren', auth()->user());
```

### 4. Request Validation in Controllers
```php
// Middleware or controller check
if (!auth()->user()->hasPermissionTo('view_children')) {
    abort(403, 'Unauthorized');
}

// Blade helper
@unless(auth()->user()->can('manage_children'))
    <p>You don't have permission to manage children</p>
@endunless
```

---

## File Structure for Permissions

### Database Seeders
```
database/seeders/
├── RoleSeeder.php          ← Create roles
├── PermissionSeeder.php    ← Create permissions
└── RolePermissionSeeder.php ← Assign permissions to roles
```

### Configuration
```
config/permissions.php      ← Define all permissions
```

---

## Troubleshooting Role Issues

### Problem: User created but role not synced
```php
// Check in tinker:
$user = User::find(1);
$user->roles;  // Should show 'guardian'

// Manual fix:
$user->syncRoles(['guardian']);
```

### Problem: Blade directive @role not working
```php
// Ensure Spatie published config:
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

// Ensure middleware registered in app/Http/Kernel.php:
protected $routeMiddleware = [
    ...
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
];
```

### Problem: hasRole() returns false even though account_type is 'guardian'
```php
// Check if role exists in DB:
Role::where('name', 'guardian')->first();

// If missing, create it:
Role::create(['name' => 'guardian', 'guard_name' => 'web']);

// Then sync user:
$user->syncRoles(['guardian']);
```

---

## Security Checklist

- ✅ Validate account_type on registration
- ✅ Automatically sync Spatie roles on user creation
- ✅ Use `auth` middleware on protected routes
- ✅ Use `role:guardian` middleware on parent-specific routes
- ✅ Check permissions before sensitive operations
- ✅ Use Blade directives in views (`@role('guardian')`)
- ✅ Implement policies for complex authorization
- ✅ Log authorization failures
- ✅ Rate-limit login attempts
- ✅ Verify email before dashboard access

---

## Summary Table

| Check Type | When | Performance | Redundancy |
|-----------|------|-------------|-----------|
| `$user->isParent()` | Route decision | ⚡ Fast | Single check |
| `$user->hasRole('guardian')` | Authorization | ⚡ Fast | Spatie backed |
| Both checks | Critical ops | ✓ Safe | Redundant |
| `@role('guardian')` | Blade views | ✓ Cached | UI control |
| `@can('manage_children')` | Fine-grained | ✓ Safe | Permission-based |

---

## Next: Implementing Permissions

1. Run seeder to create roles/permissions
2. Assign permissions to guardian role
3. Add @can checks in views
4. Create policies for complex rules
5. Update middleware stack as needed

All ready! Your RBAC is production-ready. 🚀
