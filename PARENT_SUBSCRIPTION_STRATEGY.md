# Parent/Guardian-Student-Subscription Linking Strategy

## Current State ✅
You already have a solid parent-student relationship:
```php
// User.php
public function children()      // Parent → Students
public function parents()       // Student → Parents  
// Through: parent_student pivot table
```

**Table: parent_student**
- parent_id (guardian user_id)
- student_id (student user_id)
- is_active
- linked_at
- created_at, updated_at

---

## Subscription Hierarchy: Two Approaches

### 🎯 **RECOMMENDED: Family/Household Subscriptions**

**Best for**: Parents managing multiple children or shared family plans

```
┌─────────────────────────────────────────────┐
│        Family/Household Account             │
│         (Parent/Guardian User)              │
│                                              │
│    Monthly Subscription ₦2,000              │
│    └─ Active (covers 3 children)            │
└─────────────────────────────────────────────┘
         │
    ┌────┴────┬──────────┬──────────┐
    │         │          │          │
┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐
│Child1│ │Child2│ │Child3│ │Child4│
│      │ │      │ │      │ │      │
│ Can  │ │ Can  │ │ Can  │ │Cannot│
│Access│ │Access│ │Access│ │Access│
└──────┘ └──────┘ └──────┘ └──────┘
                           (No Link)
```

**Implementation:**
```php
// In Subscription model
public function user()      // Parent/Guardian
{
    return $this->belongsTo(User::class);
}

// In User model
public function subscriptions()  // For guardians
{
    return $this->hasMany(Subscription::class);
}

// Check if student can access
public function canAccessWithGuardianSubscription(): bool
{
    return $this->parents()
        ->whereHas('subscriptions', function ($q) {
            $q->where('status', 'active')
              ->where('is_active', true)
              ->where('ends_at', '>', now());
        })
        ->exists();
}
```

**Pros:**
- ✅ One subscription covers multiple children
- ✅ Parent controls access for all linked students
- ✅ Simple billing (one payment for family)
- ✅ Easy to add/remove children
- ✅ **RECOMMENDED for B2B or family subscriptions**

**Cons:**
- All children share same plan tier
- Can't have different plans per child

---

### 📱 **ALTERNATIVE: Individual Student Subscriptions**

**Best for**: Each student has their own subscription (more flexible)

```
┌─────────────────────────────────────────────┐
│         Parent/Guardian Account             │
│         (User account_type: guardian)       │
│                                              │
│    Account created for managing children    │
└─────────────────────────────────────────────┘
         │
    ┌────┴────┬──────────┐
    │         │          │
┌──────────┐ ┌──────────┐ ┌──────────┐
│ Student1 │ │ Student2 │ │ Student3 │
│          │ │          │ │          │
│ Monthly  │ │ Yearly   │ │ No Plan  │
│ ₦2,000   │ │ ₦12,000  │ │          │
└──────────┘ └──────────┘ └──────────┘
```

**Implementation:**
```php
// In Subscription model (current approach)
public function user()      // Student with their own subscription
{
    return $this->belongsTo(User::class);
}

// Parent sees children's subscriptions
public function childrenSubscriptions()
{
    return Subscription::whereIn('user_id', 
        $this->children()->pluck('id')
    )->get();
}
```

**Pros:**
- ✅ Each child can have different plan
- ✅ Individual billing tracking
- ✅ Current system already supports this

**Cons:**
- Multiple payments if managing many children
- More complex billing/invoicing

---

## 🏆 **RECOMMENDATION: Hybrid Model**

**Support BOTH approaches:**

### Database Changes Needed
```php
// Add to subscriptions table migration:
'family_subscription' => 'boolean', // defaults to false
'parent_id' => 'nullable' // if family subscription, link to parent
```

### Implementation

**1. Create Family Subscription Migration**
```php
Schema::create('family_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')          // Parent/Guardian
        ->constrained('users')
        ->onDelete('cascade');
    $table->string('plan')->default('monthly');      // monthly, yearly
    $table->integer('max_students')->default(5);     // how many children allowed
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending');    // pending, active, cancelled
    $table->boolean('is_active')->default(false);
    $table->string('reference')->nullable();         // Paystack reference
    $table->string('subscription_code')->nullable(); // Paystack subscription code
    $table->string('authorization_code')->nullable();
    $table->string('email_token')->nullable();
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamp('next_billing_date')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'reference']);
});
```

**2. Update User Model**
```php
public function familySubscriptions()
{
    // For guardians: their own family subscriptions
    return $this->hasMany(FamilySubscription::class, 'user_id');
}

public function hasActiveFamilySubscription(): bool
{
    return $this->familySubscriptions()
        ->where('status', 'active')
        ->where('is_active', true)
        ->where('ends_at', '>', now())
        ->exists();
}

public function canAccessContent(): bool
{
    // Student has own subscription
    if ($this->hasActiveSubscription()) {
        return true;
    }
    
    // OR linked parent has family subscription
    if ($this->parents()->count() > 0) {
        return $this->parents()
            ->whereHas('familySubscriptions', function ($q) {
                $q->active();
            })
            ->exists();
    }
    
    // OR free trial
    return $this->onTrial();
}
```

**3. Update FamilySubscription Model**
```php
class FamilySubscription extends Model
{
    protected $guarded = [];
    
    public function guardian()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('is_active', true)
            ->where('ends_at', '>', now());
    }
    
    public function getConnectedStudents()
    {
        return $this->guardian->children;
    }
    
    public function getStudentCount()
    {
        return $this->guardian->children->count();
    }
    
    public function isWithinLimit(): bool
    {
        return $this->getStudentCount() <= $this->max_students;
    }
}
```

---

## Access Control Pattern

```php
// In middleware or policy

class CanAccessContent
{
    public function handle($request, $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect('login');
        }
        
        // Check access (in order of preference)
        if ($user->hasActiveSubscription()) {
            return $next($request);  // Individual plan
        }
        
        if ($user->parents()
            ->whereHas('familySubscriptions', fn($q) => $q->active())
            ->exists()) {
            return $next($request);  // Parent's family plan
        }
        
        if ($user->onTrial()) {
            return $next($request);  // Free trial
        }
        
        return redirect('payment.pricing')
            ->with('message', 'Please subscribe to access this content');
    }
}
```

---

## Subscription Payment Flow

### Family Subscription Checkout
```
Parent clicks "Buy for Family"
    ↓
Initialize payment (for parent email)
    ↓
Success → Create FamilySubscription record
    ↓
Parent can now see dashboard with:
- Active family plan
- Number of children using it
- All children's progress
```

### Individual Subscription (Current)
```
Student clicks "Subscribe"
    ↓
Initialize payment
    ↓
Success → Create Subscription record (user_id = student)
    ↓
Student can access content
```

---

## Handling Scenarios

### Scenario 1: Parent with Family Plan + Student with Individual Plan
**Expected**: Student uses own plan (individual takes precedence)

```php
public function canAccessContent(): bool
{
    // Check in order of cost (most specific first)
    if ($this->hasActiveSubscription()) {
        return true;  // Own plan takes precedence
    }
    
    if ($this->parents()
        ->whereHas('familySubscriptions', fn($q) => $q->active())
        ->exists()) {
        return true;  // Fallback to parent's plan
    }
    
    return $this->onTrial();
}

// Track which subscription is being used
public function getActiveSubscriptionSource(): string
{
    if ($this->hasActiveSubscription()) {
        return 'personal';
    }
    
    if ($this->parents()
        ->whereHas('familySubscriptions', fn($q) => $q->active())
        ->exists()) {
        return 'family';
    }
    
    return 'trial';
}
```

### Scenario 2: Parent Removes Child
```php
// Remove link
$parent->children()->detach($childId);

// Child loses access to family plan
// But keeps own subscription if they have one
```

### Scenario 3: Parent's Plan Expires
```
Parent's family subscription ends
    ↓
Webhook updates: is_active = false
    ↓
All linked children lose access (unless they have personal plan)
    ↓
Show "Subscribe" button on their dashboard
```

---

## Dashboard Changes

### For Parents
```
Family Plan Dashboard
├── Current Plan: Monthly ₦2,000
├── Status: Active
├── Children Using This Plan: 3/5
├── Billing Date: Next charge on Mar 1, 2026
├── Actions: [Manage Plan] [View Receipt] [Cancel]
└── Linked Children:
    ├── Child 1 - Active - Using Plan
    ├── Child 2 - Active - Using Plan
    └── Child 3 - Inactive
```

### For Students
```
Access Status
├── Plan: Through Parent (Family)
├── Parent: John Doe
├── Active Until: Mar 1, 2026
└── [View Parent's Plan Details]

OR

Access Status
├── Plan: Personal Subscription
├── Active Until: Mar 1, 2026
└── [Manage Plan]
```

---

## Implementation Priority

### Phase 1 (MVP): Current System ✅
- Individual student subscriptions (already working)
- Parent-student linking (already working)
- Check parent subscription in middleware

### Phase 2 (Recommended)
- Add FamilySubscription model
- Create family plan checkout flow
- Update access control to check both

### Phase 3 (Optional)
- Bulk student management for schools
- Family plan pricing tiers
- Usage analytics per child

---

## Database Query Examples

**Get all content-accessible users (including those with parent plans):**
```php
// Students with own subscription
$individualSubscribers = User::where('account_type', 'student')
    ->whereHas('subscriptions', fn($q) => $q->active())
    ->get();

// Students with parent's family plan
$familyPlanUsers = User::where('account_type', 'student')
    ->whereHas('parents', fn($q) => 
        $q->whereHas('familySubscriptions', fn($q2) => $q2->active())
    )
    ->get();

// Combine
$allAccessible = $individualSubscribers->merge($familyPlanUsers);
```

---

## Recommended Approach for Your System

**Go with HYBRID model:**

1. **Keep existing Individual Subscription** (working great with Paystack)
2. **Add FamilySubscription** for parents wanting to manage multiple children
3. **Update canAccessContent()** to check both paths
4. **Update dashboard** to show which source is being used

**Why:**
- ✅ Backward compatible with current system
- ✅ Supports both use cases
- ✅ Flexible for different customer types
- ✅ Schools can offer family plans
- ✅ Individual students can subscribe too
- ✅ Minimal database changes

---

## Quick Start Checklist

- [ ] Review this document with your use case
- [ ] Decide: Individual only, Family only, or Hybrid?
- [ ] If Hybrid: Create FamilySubscription migration
- [ ] Update canAccessContent() middleware
- [ ] Test both subscription paths
- [ ] Update dashboard UI
- [ ] Update Paystack payment flow for family plans
