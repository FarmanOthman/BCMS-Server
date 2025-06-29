# Makes API Technical Implementation

## Architecture Overview
The Makes API follows Laravel's RESTful resource pattern with custom authentication and role-based authorization.

## Components

### 1. Model Layer
**File:** `app/Models/Make.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Make extends EloquentModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
    ];

    // Relationships
    public function models()
    {
        return $this->hasMany(Model::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
```

**Features:**
- UUID primary keys via `HasUuids` trait
- Mass assignment protection with `$fillable`
- Factory support for testing
- Relationships to models and cars

### 2. Controller Layer
**File:** `app/Http/Controllers/Api/MakeController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Make;
use App\Http\Requests\StoreMakeRequest;
use App\Http\Requests\UpdateMakeRequest;

class MakeController extends Controller
{
    public function index()
    {
        return Make::orderBy('name')->get();
    }

    public function store(StoreMakeRequest $request)
    {
        $make = Make::create($request->validated());
        return response()->json($make, 201);
    }

    public function show(Make $make)
    {
        return $make;
    }

    public function update(UpdateMakeRequest $request, Make $make)
    {
        $make->update($request->validated());
        return response()->json($make);
    }

    public function destroy(Make $make)
    {
        $make->delete();
        return response()->json(null, 204);
    }
}
```

**Features:**
- RESTful CRUD operations
- Route model binding for automatic model resolution
- Form request validation
- Proper HTTP status codes
- JSON responses

### 3. Validation Layer
**Files:** 
- `app/Http/Requests/StoreMakeRequest.php`
- `app/Http/Requests/UpdateMakeRequest.php`

**Validation Rules:**
```php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:makes',
    ];
}
```

### 4. Middleware Layer
**File:** `app/Http/Middleware/CheckRole.php`

```php
public function handle(Request $request, Closure $next, ...$roles): Response
{
    $token = $request->bearerToken();
    
    if (!$token) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
    
    try {
        // Decode access token
        $tokenData = json_decode(base64_decode($token), true);
        
        if (!$tokenData || !isset($tokenData['user_id'])) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        // Check if token is expired
        if (isset($tokenData['exp']) && $tokenData['exp'] < time()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        // Get user from database
        $user = DB::table('users')->where('id', $tokenData['user_id'])->first();
        
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 401);
        }
        
        // Check if the user has the required role
        if (!$user->role || !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        return $next($request);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Invalid token.'], 401);
    }
}
```

**Features:**
- Token-based authentication
- Role-based authorization
- Direct database access (no external API)
- Proper error handling and status codes

### 5. Route Configuration
**File:** `routes/api.php`

```php
// API resources for Makes, accessible to Manager and User roles
Route::apiResource('/makes', MakeController::class)->middleware(['role:Manager,User']);
```

**Generated Routes:**
- `GET /bcms/makes` → `MakeController@index`
- `POST /bcms/makes` → `MakeController@store`
- `GET /bcms/makes/{make}` → `MakeController@show`
- `PUT /bcms/makes/{make}` → `MakeController@update`
- `DELETE /bcms/makes/{make}` → `MakeController@destroy`

### 6. Database Layer
**Migration:** `database/migrations/2025_06_12_160042_create_makes_table.php`

```php
Schema::create('makes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();
    $table->timestamps();
});
```

**Factory:** `database/factories/MakeFactory.php`

```php
public function definition()
{
    return [
        'name' => fake()->company(),
    ];
}
```

### 7. Test Layer
**File:** `tests/Feature/Api/MakeApiTest.php`

**Test Strategy:**
- Direct database user creation (no mocking)
- Real authentication flow via `/bcms/auth/signin`
- Comprehensive CRUD testing for both Manager and User roles
- Security testing for unauthenticated access
- Validation testing

**Test Setup:**
```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create test users directly in database
    foreach ([$this->manager, $this->user] as $account) {
        $userId = (string) \Illuminate\Support\Str::uuid();
        
        DB::table('users')->updateOrInsert(
            ['email' => $account['email']], 
            [
                'id' => $userId,
                'email' => $account['email'],
                'name' => $account['name'],
                'role' => $account['role'],
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
```

## Security Implementation

### Authentication Flow
1. User signs in via `/bcms/auth/signin`
2. System validates credentials against database
3. System generates base64-encoded JWT-like token
4. Token contains user ID and expiration time
5. Client includes token in `Authorization: Bearer {token}` header

### Authorization Flow
1. `CheckRole` middleware extracts Bearer token
2. Token is decoded and validated
3. User is retrieved from database using token's user ID
4. User's role is checked against required roles
5. Request proceeds if authorized, returns 403 if not

### Database Security
- Direct database operations only (no external API calls)
- Prepared statements via Eloquent ORM
- UUID primary keys for better security
- Role-based access control

## Performance Considerations

### Database Operations
- **Indexing:** Unique index on `name` field
- **Queries:** Simple Eloquent operations with minimal overhead
- **Relationships:** Lazy loading for related models
- **Pagination:** Can be added if needed for large datasets

### Caching Strategy
- **Route Caching:** Laravel route caching enabled
- **Query Caching:** Can be implemented for frequently accessed data
- **Response Caching:** API responses can be cached if needed

### Error Handling
- **Validation Errors:** 422 status with detailed field errors
- **Authentication Errors:** 401 status for invalid/missing tokens
- **Authorization Errors:** 403 status for insufficient permissions
- **Not Found Errors:** 404 status for missing resources
- **Server Errors:** 500 status with logged details

## Testing Strategy

### Test Coverage
- **Unit Tests:** Model validation and relationships
- **Feature Tests:** Full API endpoint testing
- **Integration Tests:** Database operations and middleware
- **Security Tests:** Authentication and authorization

### Test Data Management
- **Factory Pattern:** Consistent test data generation
- **Database Transactions:** Automatic rollback after each test
- **Isolation:** Each test runs in a clean database state

### Continuous Integration
- **Automated Testing:** All tests run on code changes
- **Database Migrations:** Fresh database for each test run
- **Code Quality:** Linting and static analysis

## Deployment Considerations

### Environment Configuration
- **Database:** PostgreSQL/MySQL with proper indexing
- **Caching:** Redis for session and cache storage
- **Logging:** Structured logging for API requests and errors
- **Monitoring:** Application performance monitoring

### Scalability
- **Horizontal Scaling:** Stateless API design supports multiple instances
- **Database Optimization:** Query optimization and connection pooling
- **Load Balancing:** Nginx/HAProxy for request distribution
- **CDN:** Static asset delivery optimization

## Future Enhancements

### API Versioning
- **Route Prefixes:** `/api/v1/makes`, `/api/v2/makes`
- **Header-based:** Accept version in request headers
- **Backward Compatibility:** Maintain older versions during transition

### Advanced Features
- **Search and Filtering:** Query parameter support
- **Pagination:** Limit/offset or cursor-based pagination
- **Sorting:** Multiple field sorting options
- **Rate Limiting:** API usage throttling
- **API Documentation:** OpenAPI/Swagger integration
