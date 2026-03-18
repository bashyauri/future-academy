# Laravel Boost & MCP Integration Guide

This document explains how to use the Laravel Boost performance optimization tools and the Model Context Protocol (MCP) server integration in the Future Academy project.

## Overview

### What's Included

1. **Laravel Boost** - Performance optimization tools:
   - Query Builder optimization with Spatie QueryBuilder
   - Database query caching
   - Lazy loading and eager loading helpers
   - Performance monitoring

2. **MCP Server** - Model Context Protocol integration:
   - AI-friendly API endpoints
   - File browsing and reading
   - Project information access
   - Code analysis capabilities
   - Security-controlled resource access

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# MCP Server Configuration
MCP_SERVER_ENABLED=true
MCP_REQUIRE_AUTH=false          # Set to true in production
MCP_ALLOWED_HOSTS=localhost,127.0.0.1
MCP_READONLY_MODE=true
MCP_LOGGING_ENABLED=true

# Boost Configuration
BOOST_CACHE_DRIVER=file         # or 'redis', 'memcached'
BOOST_MONITORING=false          # Set to true to monitor slow queries
```

## Laravel Boost Usage

### 1. Query Optimization

Optimize database queries with automatic filtering, sorting, and field selection:

```php
use App\Services\PerformanceBoost\QueryOptimizer;
use App\Models\Quiz;

// Build an optimized query
$query = QueryOptimizer::optimize(
    Quiz::query(),
    allowedFilters: ['subject', 'difficulty'],
    allowedSorts: ['created_at', 'title'],
    allowedFields: ['id', 'title', 'subject'],
    allowedIncludes: ['questions', 'author']
);

// Get paginated results
$results = QueryOptimizer::paginate($query, perPage: 15);
```

### 2. Database Caching

Cache query results automatically:

```php
use App\Services\PerformanceBoost\DatabaseCaching;

// Cache a result for 1 hour
$categories = DatabaseCaching::remember('categories', function () {
    return Category::all();
}, 3600);

// Forget cached data
DatabaseCaching::forget('categories');
```

### 3. Lazy Loading Helpers

Optimize relationship loading:

```php
use App\Services\PerformanceBoost\LazyLoadHelper;
use App\Models\User;

// Optimize for list view
$users = LazyLoadHelper::optimizeForList(
    User::query(),
    with: ['profile', 'subscriptions'],
    withCount: ['quizzes', 'articles'],
    select: ['id', 'name', 'email']
);

// Optimize for detail view
$user = LazyLoadHelper::optimizeForShow(
    User::find($id),
    with: ['profile', 'subscriptions', 'enrollments' => fn($q) => $q->limit(10)],
    withCount: ['quizzes', 'articles']
);
```

### 4. Query Builder Macros

Use convenient macros for common optimizations:

```php
// Use the optimized macro
$quizzes = Quiz::optimized(
    filters: ['subject', 'difficulty'],
    sorts: ['created_at'],
    fields: ['id', 'title', 'subject'],
    includes: ['questions']
)->paginate(15);

// Use caching macro
$categories = Category::rememberFor('all_categories', minutes: 60)->get();
```

## MCP Server Usage

### 1. Server Endpoints

#### Initialize Connection
```
POST /mcp/initialize
Response: Server info, available tools & resources
```

#### Call a Tool
```
POST /mcp/call-tool
Body: {
  "tool": "list_files|read_file|get_project_info",
  "arguments": { ... }
}
```

#### List Resources
```
POST /mcp/list-resources
Response: Available resources (models, documentation, code samples)
```

#### Get Server Info
```
GET /mcp/server-info
Response: Detailed server capabilities
```

### 2. Available Tools

#### list_files
List files in a directory:
```json
{
  "tool": "list_files",
  "arguments": {
    "directory": "app"
  }
}
```

#### read_file
Read file contents with line limiting:
```json
{
  "tool": "read_file",
  "arguments": {
    "path": "app/Models/Quiz.php",
    "start_line": 1,
    "end_line": 50
  }
}
```

#### get_project_info
Get comprehensive project information:
```json
{
  "tool": "get_project_info",
  "arguments": {}
}
```

### 3. Available Resources

- **models**: List of application models
- **documentation**: Project markdown files
- **code_samples**: Code examples and patterns

### 4. Security Settings

MCP Server has built-in security controls:

- **Allowed Directories**: Only access to app, database, routes, resources, config, tests
- **File Size Limits**: Max 5MB per file
- **Readonly Mode**: Prevents write operations (enabled by default)
- **Host Whitelist**: Configure allowed hosts in .env
- **Authentication**: Optional token-based authentication

## Integration Service

The `IntegrationService` provides a unified interface:

```php
use App\Services\IntegrationService;

$integration = app(IntegrationService::class);

// Get health metrics
$metrics = $integration->getHealthMetrics();

// Get project statistics
$stats = $integration->getProjectStats();

// Get optimization recommendations
$recommendations = $integration->getRecommendations();

// Optimize query with caching
$data = IntegrationService::optimizeQueryWithCache(
    Quiz::query(),
    'popular_quizzes',
    3600
);
```

## Testing the Integration

### Test Command

Run the integration test command:

```bash
php artisan integration:test --verbose
```

This will:
- Initialize MCP server
- Load project information
- Generate health metrics
- Show recommendations
- Test file operations

### API Endpoints

Test via HTTP:

```bash
# Check system health
curl http://localhost:8000/integration/health

# Get project stats
curl http://localhost:8000/integration/stats

# Get recommendations
curl http://localhost:8000/integration/recommendations

# Initialize MCP
curl -X POST http://localhost:8000/mcp/initialize

# Get MCP server info
curl http://localhost:8000/mcp/server-info
```

## Best Practices

### 1. Always Optimize Large Queries

```php
// Bad - N+1 queries
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->bio;
}

// Good - optimized
$users = LazyLoadHelper::optimizeForList(
    User::query(),
    with: ['profile']
);
```

### 2. Cache Heavy Operations

```php
// Cache expensive computations
$topQuizzes = DatabaseCaching::remember('top_quizzes', function () {
    return Quiz::withCount('attempts')
        ->orderByDesc('attempts_count')
        ->limit(10)
        ->get();
}, 3600);
```

### 3. Use Field Filtering in APIs

```php
// Allow clients to request only needed fields
$query = QueryOptimizer::optimize(
    Quiz::query(),
    allowedFields: ['id', 'title', 'subject', 'difficulty']
);

// Clients can request: ?fields=id,title
```

### 4. Monitor Slow Queries

Enable performance monitoring in .env:

```env
BOOST_MONITORING=true
```

This will log slow queries (>100ms by default) to the performance channel.

### 5. Configure Caching for Production

In production, use Redis or Memcached:

```env
BOOST_CACHE_DRIVER=redis
CACHE_DRIVER=redis
```

## Troubleshooting

### MCP Server Not Responding

1. Verify `MCP_SERVER_ENABLED=true` in .env
2. Check middleware registration in bootstrap/app.php
3. Verify routes are registered in routes/web.php
4. Check logs: `tail -f storage/logs/laravel.log`

### Caching Not Working

1. Verify cache driver is configured
2. Check cache directory permissions (for file driver)
3. Test cache: `php artisan tinker` → `cache()->put('test', 'value')`

### Performance Still Slow

1. Run `php artisan integration:test --verbose`
2. Review recommendations
3. Enable slow query monitoring
4. Check database indexes
5. Use `php artisan query:log` to debug

## Performance Metrics

Monitor your application's performance:

```php
// In your dashboard or monitoring tool
$health = app(IntegrationService::class)->getHealthMetrics();

echo $health['performance']['cache_enabled']; // true/false
echo $health['mcp']['enabled']; // true/false
```

## Next Steps

1. ✅ Test the integration: `php artisan integration:test`
2. ✅ Review MCP endpoints
3. ✅ Implement query optimization in your Livewire components
4. ✅ Enable caching for expensive operations
5. ✅ Configure production settings
6. ✅ Monitor performance metrics

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Enable debug mode in development
3. Use the test command: `php artisan integration:test --verbose`
4. Review recommendations: `GET /integration/recommendations`
