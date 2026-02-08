# Parent-Student Subscription: Code Implementation Guide

## Current System (Working) ✅

Your system already has:

```php
// User Model - Parent/Student Linking
public function children()
{
    return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
        ->withPivot('is_active', 'linked_at')
        ->wherePivot('is_active', true);
}

public function parents()
{
    return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
        ->withPivot('is_active', 'linked_at')
        ->wherePivot('is_active', true);
}

// User Model - Subscription Access
public function currentSubscription()
{
    return $this->hasOne(Subscription::class)
        ->where('status', 'active')
        ->latest('ends_at');
}

public function hasActiveSubscription(): bool
{
    $subscription = $this->currentSubscription;
    return $subscription && $subscription->ends_at && now()->lt($subscription->ends_at);
}
```

---

## Approach 1: Student Checks Parent's Subscription (Simple)

**Use this if:** Parents can view children but only students subscribe

### 1. Update User Model

```php
// app/Models/User.php

/**
 * Check if student has access (own plan OR parent's plan)
 */
public function hasContentAccess(): bool
{
    // Own subscription takes priority
    if ($this->hasActiveSubscription()) {
        return true;
    }
    
    // Check parent's subscription
    return $this->parents()
        ->whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                  ->where('is_active', true)
                  ->where('ends_at', '>', now());
        })
        ->exists();
}

/**
 * Get which subscription grants access
 */
public function getAccessSource(): ?string
{
    if ($this->hasActiveSubscription()) {
        return 'personal';  // Own plan
    }
    
    if ($this->parents()
        ->whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                  ->where('is_active', true)
                  ->where('ends_at', '>', now());
        })
        ->exists()) {
        return 'parent';    // Parent's plan
    }
    
    if ($this->onTrial()) {
        return 'trial';
    }
    
    return null;
}

/**
 * Get parent's subscription if student is using it
 */
public function getParentSubscription(): ?Subscription
{
    if ($this->hasActiveSubscription()) {
        return null;  // Using own
    }
    
    return $this->parents()
        ->with('subscriptions')
        ->get()
        ->flatMap(function ($parent) {
            return $parent->subscriptions()
                ->where('status', 'active')
                ->where('is_active', true)
                ->where('ends_at', '>', now())
                ->get();
        })
        ->first();
}
```

### 2. Create Middleware

```php
// app/Http/Middleware/CheckContentAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckContentAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Allow teachers/admins
        if ($user->isTeacher() || $user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }
        
        // Check student access
        if ($user->hasContentAccess()) {
            // Track which source is being used
            session(['access_source' => $user->getAccessSource()]);
            return $next($request);
        }
        
        // No access
        return redirect()->route('payment.pricing')
            ->with('message', 'Please subscribe to access this content.');
    }
}
```

### 3. Update Routes

```php
// routes/web.php

Route::middleware(['auth', 'verified', 'check-content-access'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    // ... all protected content routes
});
```

### 4. Update Dashboard Views

```blade
{{-- resources/views/dashboard.blade.php --}}

<div class="access-status">
    @if (session('access_source') === 'personal')
        <div class="badge badge-primary">
            ✓ Personal Subscription Active
            <span class="text-sm">Until {{ auth()->user()->currentSubscription->ends_at->format('M d, Y') }}</span>
        </div>
    
    @elseif (session('access_source') === 'parent')
        <div class="badge badge-info">
            ✓ Access via Parent's Subscription
            <span class="text-sm">
                Parent: {{ auth()->user()->parents->first()->name }}
                Until {{ auth()->user()->getParentSubscription()->ends_at->format('M d, Y') }}
            </span>
        </div>
        <button class="btn btn-sm btn-outline">Get Your Own Plan</button>
    
    @elseif (session('access_source') === 'trial')
        <div class="badge badge-warning">
            ⏱ Free Trial Active
            <span class="text-sm">Until {{ auth()->user()->trial_ends_at->format('M d, Y') }}</span>
        </div>
        <button class="btn btn-sm btn-primary">Subscribe Now</button>
    @endif
</div>
```

---

## Approach 2: Family Subscription (Advanced)

**Use this if:** Parents can buy one plan for multiple children

### 1. Create Family Subscription Migration

```php
// database/migrations/YYYY_MM_DD_000001_create_family_subscriptions_table.php

Schema::create('family_subscriptions', function (Blueprint $table) {
    $table->id();
    
    // Parent/Guardian
    $table->foreignId('user_id')
        ->constrained('users')
        ->onDelete('cascade');
    
    // Plan details
    $table->string('plan')->default('monthly');  // monthly, yearly
    $table->integer('max_children')->default(5);
    $table->decimal('amount', 10, 2);
    
    // Status
    $table->string('status')->default('pending');
    $table->boolean('is_active')->default(false);
    
    // Paystack integration
    $table->string('reference')->nullable();
    $table->string('subscription_code')->nullable();
    $table->string('authorization_code')->nullable();
    $table->string('email_token')->nullable();
    
    // Billing dates
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamp('next_billing_date')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    
    // Timestamps
    $table->timestamps();
    
    // Indexes
    $table->unique(['user_id', 'reference']);
    $table->index('status');
    $table->index('is_active');
});
```

### 2. Create FamilySubscription Model

```php
// app/Models/FamilySubscription.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FamilySubscription extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    /**
     * Relationship: Parent/Guardian
     */
    public function guardian()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get connected children through parent
     */
    public function children()
    {
        return $this->guardian->children;
    }
    
    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('is_active', true)
            ->where('ends_at', '>', now());
    }
    
    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active
            && $this->status === 'active'
            && $this->ends_at
            && now()->lt($this->ends_at);
    }
    
    /**
     * Get number of children
     */
    public function getChildCount(): int
    {
        return $this->children()->count();
    }
    
    /**
     * Check if can add more children
     */
    public function canAddChild(): bool
    {
        return $this->getChildCount() < $this->max_children;
    }
    
    /**
     * Get available slots
     */
    public function getAvailableSlots(): int
    {
        return $this->max_children - $this->getChildCount();
    }
}
```

### 3. Update User Model

```php
// app/Models/User.php

/**
 * Guardian's family subscriptions
 */
public function familySubscriptions()
{
    return $this->hasMany(FamilySubscription::class);
}

/**
 * Get active family subscription
 */
public function activeFamilySubscription()
{
    return $this->familySubscriptions()
        ->active()
        ->latest('ends_at')
        ->first();
}

/**
 * Updated access check for hybrid model
 */
public function hasContentAccess(): bool
{
    // Check own subscription first
    if ($this->hasActiveSubscription()) {
        return true;
    }
    
    // Check parent's family subscription
    if ($this->parents()
        ->whereHas('familySubscriptions', function ($query) {
            $query->active();
        })
        ->exists()) {
        return true;
    }
    
    // Check trial
    return $this->onTrial();
}

/**
 * Get access source (personal, family, or trial)
 */
public function getAccessSource(): ?string
{
    if ($this->hasActiveSubscription()) {
        return 'personal';
    }
    
    if ($this->parents()
        ->whereHas('familySubscriptions', function ($query) {
            $query->active();
        })
        ->exists()) {
        return 'family';
    }
    
    if ($this->onTrial()) {
        return 'trial';
    }
    
    return null;
}

/**
 * Get the subscription being used (personal or parent's family)
 */
public function getActiveSubscription(): ?Model
{
    // Return personal subscription
    if ($personalSub = $this->currentSubscription) {
        return $personalSub;
    }
    
    // Return parent's family subscription
    return $this->parents()
        ->with('familySubscriptions')
        ->get()
        ->flatMap(function ($parent) {
            return $parent->familySubscriptions->filter->isActive();
        })
        ->first();
}
```

### 4. Update Middleware

```php
// app/Http/Middleware/CheckContentAccess.php

public function handle(Request $request, Closure $next)
{
    $user = $request->user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    if ($user->isTeacher() || $user->hasRole(['admin', 'super-admin'])) {
        return $next($request);
    }
    
    if (!$user->hasContentAccess()) {
        return redirect()->route('payment.pricing')
            ->with('message', 'Please subscribe to access content.');
    }
    
    // Store access info in session
    session([
        'access_source' => $user->getAccessSource(),
        'active_subscription' => $user->getActiveSubscription(),
    ]);
    
    return $next($request);
}
```

---

## Testing Code

```php
// tests/Feature/SubscriptionAccessTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Models\FamilySubscription;

class SubscriptionAccessTest extends TestCase
{
    /** @test */
    public function student_with_own_subscription_can_access()
    {
        $student = User::factory()->create(['account_type' => 'student']);
        
        Subscription::factory()->create([
            'user_id' => $student->id,
            'status' => 'active',
            'is_active' => true,
            'ends_at' => now()->addMonth(),
        ]);
        
        $this->assertTrue($student->hasContentAccess());
        $this->assertEquals('personal', $student->getAccessSource());
    }
    
    /** @test */
    public function student_can_access_via_parent_family_plan()
    {
        $parent = User::factory()->create(['account_type' => 'guardian']);
        $student = User::factory()->create(['account_type' => 'student']);
        
        // Link parent to student
        $parent->children()->attach($student);
        
        // Create family subscription
        FamilySubscription::factory()->create([
            'user_id' => $parent->id,
            'status' => 'active',
            'is_active' => true,
            'ends_at' => now()->addMonth(),
        ]);
        
        $this->assertTrue($student->hasContentAccess());
        $this->assertEquals('family', $student->getAccessSource());
    }
    
    /** @test */
    public function personal_subscription_takes_priority_over_family()
    {
        $parent = User::factory()->create(['account_type' => 'guardian']);
        $student = User::factory()->create(['account_type' => 'student']);
        
        $parent->children()->attach($student);
        
        // Both subscriptions active
        Subscription::factory()->create([
            'user_id' => $student->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);
        
        FamilySubscription::factory()->create([
            'user_id' => $parent->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);
        
        // Personal takes priority
        $this->assertEquals('personal', $student->getAccessSource());
    }
    
    /** @test */
    public function student_without_subscription_cannot_access()
    {
        $student = User::factory()->create(['account_type' => 'student']);
        
        $this->assertFalse($student->hasContentAccess());
        $this->assertNull($student->getAccessSource());
    }
}
```

---

## Dashboard Display

```blade
{{-- resources/views/partials/access-status.blade.php --}}

@php
    $source = auth()->user()->getAccessSource();
    $subscription = auth()->user()->getActiveSubscription();
@endphp

<div class="access-indicator">
    @if ($source === 'personal')
        <div class="alert alert-success">
            <strong>✓ Active Subscription</strong>
            <p>Personal plan - Valid until {{ $subscription->ends_at->format('F d, Y') }}</p>
            <small>Next billing: {{ $subscription->next_billing_date?->format('F d, Y') ?? 'N/A' }}</small>
        </div>
    
    @elseif ($source === 'family')
        <div class="alert alert-info">
            <strong>✓ Access via Family Plan</strong>
            <p>Guardian: {{ auth()->user()->parents->first()->name }}</p>
            <p>Valid until {{ $subscription->ends_at->format('F d, Y') }}</p>
            <a href="{{ route('payment.pricing') }}" class="btn btn-sm btn-outline-primary">
                Get Personal Plan
            </a>
        </div>
    
    @elseif ($source === 'trial')
        <div class="alert alert-warning">
            <strong>⏱ Free Trial Active</strong>
            <p>Trial ends on {{ auth()->user()->trial_ends_at->format('F d, Y') }}</p>
            <a href="{{ route('payment.pricing') }}" class="btn btn-sm btn-primary">
                Subscribe Now
            </a>
        </div>
    
    @else
        <div class="alert alert-danger">
            <strong>❌ No Active Subscription</strong>
            <p>Subscribe to access all content</p>
            <a href="{{ route('payment.pricing') }}" class="btn btn-sm btn-primary">
                Subscribe Now
            </a>
        </div>
    @endif
</div>
```

---

## Quick Implementation Checklist

### Approach 1: Simple Parent Check
- [ ] Add `hasContentAccess()` to User model
- [ ] Add `getAccessSource()` to User model
- [ ] Create `CheckContentAccess` middleware
- [ ] Register middleware in kernel
- [ ] Apply to protected routes
- [ ] Update dashboard views
- [ ] Test with parent + student

### Approach 2: Family Subscription
- [ ] Create migration for `family_subscriptions` table
- [ ] Create `FamilySubscription` model
- [ ] Add relations to `User` model
- [ ] Update `hasContentAccess()` to check family plans
- [ ] Create `CheckContentAccess` middleware v2
- [ ] Create payment flow for family plans
- [ ] Update dashboard
- [ ] Test all scenarios

---

## Recommended Next Step

**Start with Approach 1 (Simple Parent Check):**
- Minimal code changes
- Works with current system
- Test with real users
- Add family plans later if needed

**Then add Approach 2 (Family Subscription) when:**
- Parents request family plans
- You want to offer family discounts
- Multiple children per family is common
