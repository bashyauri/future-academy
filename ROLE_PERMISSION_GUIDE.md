# Role & Permission Management System

## ✅ What's Implemented

I've created a complete **Role and Permission Management System** for your Filament v4 admin panel using **Spatie Laravel Permission**. This follows the standard Laravel/Spatie patterns.

---

## 📦 Features

### 1. **Role Management** (`/admin/roles`)

-   ✅ Create, edit, delete custom roles
-   ✅ Assign multiple permissions to each role
-   ✅ View role details with permission counts
-   ✅ Protected system roles (cannot delete: super-admin, admin, teacher, uploader, guardian, student)
-   ✅ Color-coded badges for different roles
-   ✅ Bulk actions for super-admins

### 2. **Permission Management** (`/admin/permissions`)

-   ✅ Create, edit, delete custom permissions
-   ✅ Assign permissions to multiple roles at once
-   ✅ View which roles have each permission
-   ✅ Protected core permissions (cannot delete essential ones)
-   ✅ Search and filter capabilities
-   ✅ Permission descriptions for documentation

### 3. **User Management** (`/admin/users`)

-   ✅ Assign users to roles
-   ✅ View user roles and permissions
-   ✅ Account type syncs with primary role
-   ✅ Multi-role support per user

---

## 🎨 UI Features

### Role Resource:

-   **Form Sections:**
    -   🏷️ Role Details (name, guard)
    -   🔒 Permissions (checkbox list with descriptions)
-   **Table Columns:**
    -   Role name with badge
    -   Permission count
    -   User count
    -   Full permission list (toggleable)
    -   Created date

### Permission Resource:

-   **Form Sections:**
    -   🔑 Permission Details (name, guard, description)
    -   🛡️ Assign to Roles (checkbox list with descriptions)
-   **Table Columns:**
    -   Permission name with badge
    -   Assigned roles (color-coded)
    -   Role count
    -   Created date

---

## 🚀 How to Use

### Creating a New Role:

1. Navigate to `/admin/roles`
2. Click **"Create New Role"**
3. Enter role name (e.g., `content-manager`)
4. Select permissions to assign
5. Click **Save**

### Creating a New Permission:

1. Navigate to `/admin/permissions`
2. Click **"Create New Permission"**
3. Enter permission name (e.g., `manage content`)
4. Optionally add description
5. Select roles that should have this permission
6. Click **Save**

### Assigning Permissions to Existing Role:

1. Go to `/admin/roles`
2. Click **Edit** on any role
3. Check/uncheck permissions
4. Click **Save**

### Assigning Roles to Users:

1. Go to `/admin/users`
2. Click **Edit** on any user
3. In "Role & Permissions" section:
    - Set **Primary Role (Account Type)**
    - Add **Additional Roles** if needed
4. Click **Save**

---

## 🔐 Access Control

### Role Resource:

-   **View**: `admin` and `super-admin`
-   **Create/Edit/Delete**: `super-admin` only

### Permission Resource:

-   **All operations**: `super-admin` only

### Protected Items:

-   **System Roles**: Cannot be deleted (super-admin, admin, teacher, uploader, guardian, student)
-   **Core Permissions**: Cannot be deleted (manage users, upload questions, view stats, etc.)

---

## 📋 Pre-seeded Data

Your database already has:

### Roles:

-   ✅ super-admin
-   ✅ admin
-   ✅ teacher
-   ✅ uploader
-   ✅ guardian
-   ✅ student

### Permissions:

-   ✅ manage users
-   ✅ upload questions
-   ✅ manage questions
-   ✅ view stats
-   ✅ approve guardians
-   ✅ manage subscriptions
-   ✅ manage videos

### Default User:

-   **Email**: `super@admin.com`
-   **Password**: `password`
-   **Role**: super-admin

---

## 💻 Code Usage Examples

### Check Permission in Code:

```php
// In controllers or anywhere
if (auth()->user()->can('manage users')) {
    // User has permission
}

// Check role
if (auth()->user()->hasRole('admin')) {
    // User is admin
}

// Check any role
if (auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
    // User is admin or super-admin
}
```

### In Filament Resources:

```php
public static function canViewAny(): bool
{
    return auth()->user()?->can('manage users') ?? false;
}

public static function canCreate(): bool
{
    return auth()->user()?->hasRole('super-admin') ?? false;
}
```

### In Blade Views:

```blade
@can('manage users')
    <button>Manage Users</button>
@endcan

@role('admin')
    <p>Admin only content</p>
@endrole
```

### In Routes (web.php):

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', ...);
});

Route::middleware(['auth', 'permission:manage users'])->group(function () {
    Route::resource('users', UserController::class);
});
```

---

## 🎯 Standard Spatie Pattern

This implementation follows the **official Spatie Laravel Permission** package patterns:

1. **Database Tables** (created by Spatie migration):

    - `roles` - stores roles
    - `permissions` - stores permissions
    - `model_has_roles` - pivot for user-role
    - `model_has_permissions` - pivot for user-permission
    - `role_has_permissions` - pivot for role-permission

2. **Standard Methods**:

    - `assignRole()` - assign role to user
    - `syncRoles()` - sync roles (replaces existing)
    - `givePermissionTo()` - give permission to user/role
    - `syncPermissions()` - sync permissions
    - `hasRole()` - check if has role
    - `can()` - check if has permission

3. **Relationships** (automatic):
    - User → roles (many-to-many)
    - User → permissions (many-to-many)
    - Role → permissions (many-to-many)

---

## 📁 File Structure

```
app/Filament/Resources/
├── RoleResource.php
│   ├── Schemas/
│   │   └── RoleForm.php
│   ├── Tables/
│   │   └── RolesTable.php
│   └── Pages/
│       ├── ListRoles.php
│       ├── CreateRole.php
│       └── EditRole.php
├── PermissionResource.php
│   ├── Schemas/
│   │   └── PermissionForm.php
│   ├── Tables/
│   │   └── PermissionsTable.php
│   └── Pages/
│       ├── ListPermissions.php
│       ├── CreatePermission.php
│       └── EditPermission.php
└── Users/
    └── UserResource.php (already includes role assignment)
```

---

## ✨ Next Steps

You can now:

1. ✅ **Create custom roles** for specific user groups
2. ✅ **Define granular permissions** for your LMS features
3. ✅ **Assign permissions to roles** via the admin panel
4. ✅ **Protect routes and resources** using permissions
5. ✅ **Build role-specific dashboards** for teacher/guardian/student

Everything is production-ready and follows Laravel best practices! 🚀
