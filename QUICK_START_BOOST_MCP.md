# Laravel Boost & MCP - Quick Start

## 🎯 5-Minute Setup

### 1. Verify Installation
```bash
cd c:\laragon\www\future-academy

# Check Spatie QueryBuilder is installed
composer show spatie/laravel-query-builder
```

### 2. Test the Integration
```bash
# Run the integration test
php artisan integration:test --verbose
```

### 3. Test API Endpoints
```bash
# In another terminal, start the server if not running
php artisan serve

# Test health endpoint
curl http://localhost:8000/integration/health

# Test MCP server
curl -X POST http://localhost:8000/mcp/initialize

# Get recommendations
curl http://localhost:8000/integration/recommendations
```

---

## 💡 Common Usage Patterns

### Pattern 1: Optimize a Query
```php
<?php
use App\Services\PerformanceBoost\QueryOptimizer;
use App\Models\Quiz;

// In your controller/service
$quizzes = QueryOptimizer::optimize(
    Quiz::query(),
    allowedFilters: ['subject', 'difficulty', 'exam_type'],
    allowedSorts: ['created_at', 'title', 'difficulty'],
    allowedFields: ['id', 'title', 'subject', 'difficulty'],
    allowedIncludes: ['questions', 'author']
);

// Get paginated results
$paginated = QueryOptimizer::paginate($quizzes, perPage: 20);

return response()->json($paginated);
```

### Pattern 2: Cache Expensive Operations
```php
<?php
use App\Services\PerformanceBoost\DatabaseCaching;
use App\Models\Category;

// Cache all categories for 1 hour
$categories = DatabaseCaching::remember('all_categories', function () {
    return Category::with('subjects')->get();
}, 3600);

// Later, invalidate the cache
// DatabaseCaching::forget('all_categories');
```

### Pattern 3: Optimize Relationships
```php
<?php
use App\Services\PerformanceBoost\LazyLoadHelper;
use App\Models\User;

// For list views - use select + with + withCount
$users = LazyLoadHelper::optimizeForList(
    User::where('role', 'student')->query(),
    with: ['profile', 'subscriptions'],
    withCount: ['quizzes', 'articlesWatched'],
    select: ['id', 'name', 'email', 'status']
);

$paginated = $users->paginate(15);
```

### Pattern 4: Use Query Builder Macros
```php
<?php
// The optimized macro simplifies everything
$quizzes = Quiz::optimized(
    filters: ['subject', 'difficulty'],
    sorts: ['created_at'],
    fields: ['id', 'title', 'subject'],
    includes: ['questions']
)->paginate(15);

// The caching macro
$categories = Category::rememberFor('categories', minutes: 60)->get();
```

---

## 📊 Integration Service Methods

```php
<?php
use App\Services\IntegrationService;

$integration = app(IntegrationService::class);

// Get health status
$health = $integration->getHealthMetrics();
// Returns: app info, cache status, monitoring status, MCP status

// Get project info
$stats = $integration->getProjectStats();
// Returns: project name, Laravel version, model count, route count

// Get recommendations
$recommendations = $integration->getRecommendations();
// Returns: array of optimization suggestions
```

---

## 🔧 MCP Server Usage

### Initialize (Required First)
```bash
curl -X POST http://localhost:8000/mcp/initialize
```

### List Files
```bash
curl -X POST http://localhost:8000/mcp/call-tool \
  -H "Content-Type: application/json" \
  -d '{
    "tool": "list_files",
    "arguments": {"directory": "app"}
  }'
```

### Read a File
```bash
curl -X POST http://localhost:8000/mcp/call-tool \
  -H "Content-Type: application/json" \
  -d '{
    "tool": "read_file",
    "arguments": {
      "path": "app/Models/Quiz.php",
      "start_line": 1,
      "end_line": 50
    }
  }'
```

### Get Project Info
```bash
curl -X POST http://localhost:8000/mcp/call-tool \
  -H "Content-Type: application/json" \
  -d '{
    "tool": "get_project_info",
    "arguments": {}
  }'
```

---

## 🎓 Real-World Example

### Scenario: List All Quizzes with Optimization

**Bad Way (Before):**
```php
// Loads everything, slow, N+1 queries
$quizzes = Quiz::all();
```

**Good Way (After):**
```php
use App\Services\PerformanceBoost\QueryOptimizer;
use App\Services\PerformanceBoost\DatabaseCaching;

// Cache the optimized query
$quizzes = DatabaseCaching::remember('all_quizzes_paginated', function () {
    return QueryOptimizer::optimize(
        Quiz::where('status', 'published'),
        allowedFilters: ['difficulty', 'subject', 'exam_type'],
        allowedSorts: ['created_at', 'popularity'],
        allowedFields: ['id', 'title', 'difficulty', 'subject'],
        allowedIncludes: ['author', 'questions']
    )->with(['author:id,name', 'questions:id,quiz_id'])
     ->withCount('attempts')
     ->paginate(15);
}, 3600); // Cache for 1 hour

return response()->json($quizzes);
```

---

## ⚙️ Environment Configuration

### Development (.env)
```env
MCP_SERVER_ENABLED=true
MCP_REQUIRE_AUTH=false
BOOST_CACHE_DRIVER=file
BOOST_MONITORING=false
```

### Production (.env)
```env
MCP_SERVER_ENABLED=true
MCP_REQUIRE_AUTH=true
MCP_ALLOWED_HOSTS=your-server.com
BOOST_CACHE_DRIVER=redis
BOOST_MONITORING=true
```

---

## 📈 Monitoring

### Check Health
```bash
curl http://localhost:8000/integration/health
```

### View Recommendations
```bash
curl http://localhost:8000/integration/recommendations
```

### Check Slow Queries (if monitoring enabled)
```bash
tail -f storage/logs/performance.log
```

---

## 🐛 Troubleshooting

### MCP Returns 403
- Check `.env` for `MCP_ALLOWED_HOSTS`
- Default is: `localhost,127.0.0.1`

### Cache Not Working
- Verify cache driver: `BOOST_CACHE_DRIVER=file` or `redis`
- Check permissions on `storage/framework/cache`

### Query Still Slow
- Run: `php artisan integration:test --verbose`
- Check recommendations output
- Enable monitoring: `BOOST_MONITORING=true`

---

## 📚 Full Documentation

See [LARAVEL_BOOST_MCP_GUIDE.md](./LARAVEL_BOOST_MCP_GUIDE.md) for complete reference.

---

## 🚀 You're Ready!

Start using performance optimization and MCP in your code right now. The integration is fully set up and ready to go.

```bash
# Verify everything works
php artisan integration:test --verbose

# Check the health endpoint
curl http://localhost:8000/integration/health
```

Happy coding! 🎉
