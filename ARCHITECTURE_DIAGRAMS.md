# Integration Architecture & Flow Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client (Web/API)                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │  Laravel Router  │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼────┐          ┌────▼────┐         ┌────▼────┐
   │ Web      │          │  MCP    │         │Integration
   │Routes   │          │Routes   │         │Routes
   └────┬────┘          └────┬────┘         └────┬─────┘
        │                    │                    │
   ┌────▼──────────┐    ┌────▼────────────┐ ┌────▼──────────┐
   │ Livewire      │    │ McpController   │ │ Integration   │
   │Components    │    │ (Middleware)    │ │ Controller    │
   └────┬──────────┘    └────┬────────────┘ └────┬──────────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │
                ┌────────────▼───────────────┐
                │ IntegrationService        │
                │ (Unified Interface)       │
                └────────────┬──────────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
      ┌─────▼────┐    ┌─────▼─────┐    ┌────▼──────┐
      │ McpServer │    │ QueryOpt   │    │ Database  │
      │           │    │ imizer    │    │ Caching   │
      │ -Tools   │    │            │    │           │
      │ -Resources│   │ -Filters   │    │ -Caching  │
      └─────┬────┘    │ -Sorts     │    │ -TTL      │
            │         │ -Fields    │    └───────────┘
            │         └─────┬──────┘
            │               │
      ┌─────▼───────────────▼────────────┐
      │   File System & Database          │
      │   - Application Code              │
      │   - Models & Data                 │
      │   - Cache Storage                 │
      └──────────────────────────────────┘
```

---

## Request Flow Diagram

### MCP Tool Request Flow
```
Client Request (POST /mcp/call-tool)
    │
    ├─► McpAuth Middleware
    │   ├─► Check enabled status
    │   ├─► Verify host
    │   └─► Validate auth (if enabled)
    │
    └──► McpController::callTool()
        │
        ├─► Validate request
        │   ├─► tool: required
        │   └─► arguments: array
        │
        ├─► Route to correct tool
        │   ├─► list_files
        │   ├─► read_file
        │   ├─► get_project_info
        │   └─► analyze_code (future)
        │
        ├─► McpServer::*(tool, args)
        │   ├─► Check permissions
        │   ├─► Validate paths
        │   └─► Execute operation
        │
        ├─► Log activity
        │   └─► McpServer::log()
        │
        └──► Return JSON Response
            ├─► status: success|error
            ├─► tool: name
            └─► result: data
```

### Query Optimization Flow
```
Controller/Service
    │
    └──► QueryOptimizer::optimize(
            query, filters, sorts, fields, includes
         )
        │
        ├─► Create QueryBuilder instance
        │
        ├─► Apply filters
        │   └─► allowedFilters()
        │
        ├─► Apply sorts
        │   └─► allowedSorts()
        │
        ├─► Apply field selection
        │   └─► allowedFields()
        │
        ├─► Apply eager loading
        │   └─► allowedIncludes()
        │
        └──► Return Optimized QueryBuilder
            │
            ├─► Option A: Get results
            │   └─► QueryOptimizer::get()
            │
            ├─► Option B: Paginate
            │   └─► QueryOptimizer::paginate()
            │
            └─► Option C: Cache results
                └─► DatabaseCaching::remember()
```

### Caching Flow
```
Service Layer
    │
    └──► DatabaseCaching::remember(
            key, callback, ttl
         )
        │
        ├─► Check cache
        │   └─► cache()->has(key)?
        │
        ├─► If cached: Return cached result
        │
        └─► If not cached:
            ├─► Execute callback
            │   └─► Run query/operation
            │
            ├─► Store in cache
            │   └─► cache()->put(key, result, ttl)
            │
            └─► Return result
```

---

## Component Interaction Diagram

```
┌──────────────────────────────────────────────────┐
│          Business Logic Layer                     │
│  ┌──────────────────────────────────────────┐   │
│  │ Livewire Components / Controllers        │   │
│  │ - QuizController                         │   │
│  │ - StudentDashboard                       │   │
│  │ - etc.                                   │   │
│  └──────────────┬───────────────────────────┘   │
└─────────────────┼────────────────────────────────┘
                  │
    ┌─────────────┼──────────────┐
    │             │              │
    │    ┌────────▼────────┐    │
    │    │ IntegrationService│  │
    │    │ - Health Metrics  │  │
    │    │ - Recommendations │  │
    │    └────────┬────────┘    │
    │             │              │
    ├─────┬───────┼────────┬────┤
    │     │       │        │    │
    │ ┌───▼──┐ ┌──▼──┐ ┌──▼──┐ │
    │ │ MCP  │ │Query │ │Cache │ │
    │ │Server│ │Opt   │ │Layer │ │
    │ └──────┘ └──────┘ └──────┘ │
    │                              │
    └──────────────┬───────────────┘
                   │
         ┌─────────▼─────────┐
         │ Data Access Layer │
         │ ┌───────────────┐ │
         │ │ File System   │ │
         │ ├───────────────┤ │
         │ │ Database      │ │
         │ ├───────────────┤ │
         │ │ Cache Store   │ │
         │ └───────────────┘ │
         └───────────────────┘
```

---

## Performance Optimization Path

```
Identify Slow Query
    │
    ▼
Run: php artisan integration:test -v
    │
    ├─► Query Analysis
    │   ├─► Current approach identified
    │   └─► Recommendations generated
    │
    ├─► Get Recommendations
    │   └─► POST /integration/recommendations
    │
    ▼
Choose Optimization
    │
    ├─► Path A: Query Builder
    │   └─► Use QueryOptimizer::optimize()
    │       ├─► Add filters
    │       ├─► Add field selection
    │       └─► Add eager loading
    │
    ├─► Path B: Caching
    │   └─► Use DatabaseCaching::remember()
    │       ├─► Identify cacheable data
    │       └─► Set appropriate TTL
    │
    └─► Path C: Lazy Loading
        └─► Use LazyLoadHelper::optimizeFor*()
            ├─► Select fields
            ├─► Load relationships
            └─► Count relationships

    ▼
Implement & Test
    │
    ├─► Write new query
    ├─► Run integration:test
    └─► Monitor performance

    ▼
Verify improvement
    │
    └─► Monitor via /integration/health
        ├─► Check metrics
        └─► Review logs
```

---

## Data Flow: MCP Request Example

### Real Example: Read a File
```
1. Client sends:
   POST /mcp/call-tool
   {
     "tool": "read_file",
     "arguments": {
       "path": "app/Models/Quiz.php",
       "start_line": 1,
       "end_line": 50
     }
   }

2. Request passes through:
   - RouteMiddleware (standard Laravel)
   - McpAuth::handle()
     ├─► Check if MCP enabled
     ├─► Check host whitelist
     └─► Validate auth token (if required)

3. Routes to McpController::callTool()
   - Validate input
   - Extract tool="read_file"
   - Extract arguments

4. Controller calls:
   McpServer::readFile(
     "app/Models/Quiz.php",
     start_line: 1,
     end_line: 50
   )

5. McpServer checks:
   ├─► Is directory allowed?
   │   └─► Check against whitelist
   ├─► Does file exist?
   ├─► Is file size under limit? (5MB)
   └─► Can we read it?

6. If all checks pass:
   ├─► Read file contents
   ├─► Split into lines
   ├─► Extract requested range
   └─► Log activity

7. Returns JSON:
   {
     "status": "success",
     "file": "app/Models/Quiz.php",
     "start_line": 1,
     "end_line": 50,
     "content": "<?php\n\nnamespace..."
   }
```

---

## Security Checkpoints

```
MCP Request
    │
    ├─► Route Middleware
    │   └─► Check enabled
    │
    ├─► McpAuth Middleware
    │   ├─► Check host whitelist ✓
    │   └─► Check authentication ✓
    │
    ├─► Controller Input Validation
    │   ├─► Tool exists ✓
    │   └─► Arguments valid ✓
    │
    ├─► McpServer Permission Check
    │   ├─► Directory allowed ✓
    │   ├─► File exists ✓
    │   ├─► File size OK ✓
    │   └─► Read-only enforced ✓
    │
    ├─► Execution
    │   └─► Perform safe operation
    │
    └─► Logging
        └─► Record access attempt
```

---

## Integration Testing Points

```
php artisan integration:test
    │
    ├─► Test 1: MCP Server Init
    │   ├─► Load config
    │   ├─► Check tools available
    │   └─► Check resources available
    │
    ├─► Test 2: Project Info
    │   ├─► Get app name
    │   ├─► Get Laravel version
    │   └─► Count models
    │
    ├─► Test 3: Health Metrics
    │   ├─► Check cache status
    │   ├─► Check optimization status
    │   └─► Check monitoring status
    │
    ├─► Test 4: Recommendations
    │   ├─► Check for missing optimizations
    │   ├─► Check security settings
    │   └─► Check performance settings
    │
    └─► Test 5: File Operations
        ├─► List files
        └─► Read file
```

---

## Performance Monitoring Dashboard (Conceptual)

```
┌────────────────────────────────────────────┐
│     Integration Health Dashboard            │
├────────────────────────────────────────────┤
│                                             │
│  Application Status                         │
│  ├─ Name: Future Academy                    │
│  ├─ Environment: production                 │
│  └─ Version: 1.0.0                          │
│                                             │
│  Performance Status                         │
│  ├─ Query Caching: ✓ Enabled                │
│  ├─ Eager Loading: ✓ Configured             │
│  └─ Slow Queries: 2 detected                │
│                                             │
│  MCP Server Status                          │
│  ├─ Status: ✓ Ready                         │
│  ├─ Tools Available: 3                      │
│  └─ Authentication: Enabled                 │
│                                             │
│  Recommendations                            │
│  ├─ Enable Redis for production             │
│  ├─ Optimize lazy loading in UserList       │
│  └─ Configure MCP token                     │
│                                             │
└────────────────────────────────────────────┘
```

---

This architecture provides:
- ✅ Clear separation of concerns
- ✅ Easy testing and debugging
- ✅ Scalable performance optimization
- ✅ Secure AI tool integration
- ✅ Comprehensive monitoring
