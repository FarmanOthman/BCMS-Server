# Models API Technical Implementation

## Architecture Overview

The Models API is built using Laravel framework with DB-only access, eliminating external dependencies like Supabase for data operations. The implementation follows RESTful principles and uses standard Laravel patterns.

## Components

### 1. Controller: `ModelController`

**Location:** `app/Http/Controllers/Api/ModelController.php`

**Key Features:**
- Resource controller with standard CRUD operations
- Eager loading of make relationships
- Proper HTTP status codes
- Uses form request validation

**Methods:**
```php
public function index()    // GET /bcms/models
public function store()    // POST /bcms/models  
public function show()     // GET /bcms/models/{id}
public function update()   // PUT /bcms/models/{id}
public function destroy()  // DELETE /bcms/models/{id}
```

**Relationship Loading:**
```php
// Eager load make relationship with optimized ordering
return CarModel::with('make')->orderBy(function ($query) {
    $query->select('name')
        ->from('makes')
        ->whereColumn('makes.id', 'models.make_id');
})->orderBy('name')->get();
```

### 2. Model: `Model`

**Location:** `app/Models/Model.php`

**Key Features:**
- UUID primary keys using `HasUuids` trait
- Factory support with `HasFactory` trait
- Proper Eloquent relationships
- Mass assignment protection

**Relationships:**
```php
public function make(): BelongsTo
{
    return $this->belongsTo(Make::class);
}

public function cars(): HasMany
{
    return $this->hasMany(Car::class);
}
```

**Fillable Fields:**
```php
protected $fillable = [
    'make_id',
    'name',
];
```

### 3. Validation: Form Requests

#### StoreModelRequest
**Location:** `app/Http/Requests/StoreModelRequest.php`

**Validation Rules:**
```php
return [
    'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('models')->where(function ($query) {
            return $query->where('make_id', $this->input('make_id'));
        }),
    ],
    'make_id' => 'required|uuid|exists:makes,id',
];
```

#### UpdateModelRequest
**Location:** `app/Http/Requests/UpdateModelRequest.php`

**Key Features:**
- Uses `sometimes` for optional updates
- Handles unique validation with ignore for current model
- Proper UUID and existence validation

### 4. Routes

**Location:** `routes/api.php`

```php
Route::middleware(['auth:api', 'role:Manager,User'])->group(function () {
    Route::apiResource('models', ModelController::class);
});
```

**Generated Routes:**
- `GET /bcms/models` → `index()`
- `POST /bcms/models` → `store()`
- `GET /bcms/models/{model}` → `show()`
- `PUT /bcms/models/{model}` → `update()`
- `DELETE /bcms/models/{model}` → `destroy()`

## Database Schema

### Models Table

```sql
CREATE TABLE models (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    make_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (make_id) REFERENCES makes(id),
    UNIQUE(make_id, name) -- Ensure unique model names per make
);
```

### Key Constraints
1. **Foreign Key:** `make_id` must exist in `makes` table
2. **Unique Constraint:** Model name must be unique within each make
3. **UUID Primary Key:** For consistency with other tables

## Authentication & Authorization

### Middleware Stack
1. **auth:api** - JWT token validation using database
2. **role:Manager,User** - Both roles have full access to models

### Token Validation Process
```php
// CheckRole middleware validates token against database
$user = DB::table('users')
    ->where('id', $payload['sub'])
    ->where('email', $payload['email'])
    ->first();
```

### Permission Matrix

| Role | GET | POST | PUT | DELETE |
|------|-----|------|-----|--------|
| Manager | ✅ | ✅ | ✅ | ✅ |
| User | ✅ | ✅ | ✅ | ✅ |
| Guest | ❌ | ❌ | ❌ | ❌ |

## Data Flow

### Create Model Flow
1. **Request** → Middleware authentication
2. **Validation** → StoreModelRequest rules
3. **Database** → Check make exists, unique name per make
4. **Creation** → Model::create() with validated data
5. **Response** → Return model with make relationship

### Query Optimization
- **Eager Loading:** Always load make relationship to prevent N+1 queries
- **Ordering:** Sort by make name first, then model name
- **Indexing:** Database indexes on make_id and name fields

## Error Handling

### Validation Errors (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name has already been taken."],
    "make_id": ["The selected make id is invalid."]
  }
}
```

### Authentication Errors (401)
```json
{
  "message": "Unauthenticated."
}
```

### Not Found Errors (404)
```json
{
  "message": "No query results for model [uuid]"
}
```

## Testing Architecture

### Test Class: `ModelApiTest`
**Location:** `tests/Feature/Api/ModelApiTest.php`

**Key Features:**
- DB-only user creation (no external API calls)
- Real JWT authentication flow
- Comprehensive CRUD testing
- Relationship verification
- Validation testing

### Test Data Setup
```php
// Direct database user creation
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
```

### Helper Methods
```php
// Create model with associated make
private function createModelWithMake($modelName = 'Test Model')
{
    $make = Make::factory()->create();
    return Model::factory()->create([
        'name' => $modelName,
        'make_id' => $make->id
    ]);
}

// Get real authentication token
protected function getAccessToken(array $account): string
{
    $response = $this->postJson('/bcms/auth/signin', [
        'email' => $account['email'],
        'password' => $account['password'],
    ]);
    return $response->json('access_token');
}
```

## Performance Considerations

### Database Optimization
- **Indexes:** Composite index on (make_id, name) for unique constraint and fast lookups
- **Foreign Keys:** Indexed foreign key relationships
- **UUID Performance:** Using PostgreSQL native UUID type

### Query Optimization
- **Eager Loading:** Prevent N+1 queries with `with('make')`
- **Selective Loading:** Only load required fields when appropriate
- **Ordering:** Database-level sorting for better performance

### Caching Strategy
- **Model Caching:** Consider caching model lists with Redis
- **Relationship Caching:** Cache make relationships for frequently accessed data
- **Cache Invalidation:** Clear caches on model/make updates

## Security Features

### Input Validation
- **UUID Validation:** Strict UUID format checking
- **Length Limits:** Maximum 255 characters for names
- **Existence Checks:** Verify make exists before creating model

### SQL Injection Prevention
- **Eloquent ORM:** Built-in parameter binding
- **Prepared Statements:** All queries use prepared statements
- **Mass Assignment Protection:** Only specified fields are fillable

### Authentication Security
- **JWT Tokens:** Stateless authentication
- **Database Validation:** Token payload validated against database
- **Role-based Access:** Middleware enforces role requirements

## API Versioning

### Current Version
- **Version:** v1 (implicit)
- **Base URL:** `/bcms/models`
- **Content-Type:** `application/json`

### Future Considerations
- **Explicit Versioning:** Consider `/v1/bcms/models` for future versions
- **Backward Compatibility:** Maintain support for existing clients
- **Deprecation Strategy:** Gradual migration path for breaking changes

## Monitoring & Logging

### Application Logging
```php
// Log model operations
Log::info('Model created', ['model_id' => $model->id, 'user_id' => auth()->id()]);
Log::warning('Model creation failed', ['validation_errors' => $errors]);
```

### Performance Monitoring
- **Query Time:** Monitor database query performance
- **Response Time:** Track API response times
- **Error Rates:** Monitor 4xx and 5xx error rates

## Dependencies

### PHP Packages
- **Laravel Framework:** ^11.0
- **Laravel Sanctum/Passport:** JWT authentication
- **PostgreSQL:** Database driver

### External Dependencies
- **None:** Fully DB-based implementation
- **Removed:** Supabase API dependencies

## Deployment Notes

### Environment Configuration
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=bcms
DB_USERNAME=username
DB_PASSWORD=password

JWT_SECRET=your-jwt-secret
JWT_TTL=60
```

### Database Migrations
```bash
php artisan migrate
php artisan db:seed --class=MakeSeeder
php artisan db:seed --class=ModelSeeder
```

### Production Considerations
- **Database Indexes:** Ensure proper indexing in production
- **Connection Pooling:** Configure PostgreSQL connection pooling
- **Rate Limiting:** Implement API rate limiting
- **Monitoring:** Set up application performance monitoring

---

*Last updated: June 29, 2025*  
*Framework: Laravel 11*  
*Database: PostgreSQL*  
*Authentication: JWT (DB-based)*
