# BCMS API Postman Collection - Getting Started Guide

## Where to Start

### Step 1: Set Up Your Environment
1. **Open Postman**
2. **Create a new Environment** called "BCMS Local"
3. **Add these environment variables:**
   ```
   BASE_URL: http://localhost:8000
   ACCESS_TOKEN: (leave empty - will be set automatically)
   REFRESH_TOKEN: (leave empty - will be set automatically)
   USER_ID: (leave empty - will be set automatically)
   MANAGER_EMAIL: manager@example.com
   MANAGER_PASSWORD: password123
   USER_EMAIL: user@example.com
   USER_PASSWORD: password123
   ```

### Step 2: Create the Collection Structure
1. **Create a new Collection** called "BCMS API"
2. **Create these folders** in order:
   - 1. Authentication
   - 2. User Management
   - 3. Car Management
   - 4. Make Management
   - 5. Model Management
   - 6. Buyer Management
   - 7. Sale Management
   - 8. Daily Reports
   - 9. Monthly Reports
   - 10. Yearly Reports
   - 11. Finance Records

### Step 3: Start with Authentication (Most Important!)
**Begin with the Authentication folder - this is crucial!**

#### 3.1 Create "Sign In" Request
- **Method:** POST
- **URL:** `{{BASE_URL}}/bcms/auth/signin`
- **Headers:** `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "email": "{{MANAGER_EMAIL}}",
    "password": "{{MANAGER_PASSWORD}}"
}
```

**Add this Test Script:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has access token", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.access_token).to.exist;
    pm.environment.set("ACCESS_TOKEN", jsonData.access_token);
    pm.environment.set("REFRESH_TOKEN", jsonData.refresh_token);
    pm.environment.set("USER_ID", jsonData.user.id);
});
```

#### 3.2 Test the Sign In
1. **Select your "BCMS Local" environment**
2. **Send the Sign In request**
3. **Check that:**
   - Status code is 200
   - You get an access token
   - Environment variables are set automatically

### Step 4: Add Collection Pre-request Script
**This is important for automatic authorization!**

1. **Go to your collection settings**
2. **Add this Pre-request Script:**
```javascript
if (pm.environment.get("ACCESS_TOKEN")) {
    pm.request.headers.add({
        key: "Authorization",
        value: "Bearer " + pm.environment.get("ACCESS_TOKEN")
    });
}
```

### Step 5: Test Public Endpoints First
**These don't need authentication:**

#### 5.1 Create "List Cars" Request
- **Method:** GET
- **URL:** `{{BASE_URL}}/bcms/cars`
- **No headers needed**

#### 5.2 Create "Get Car" Request
- **Method:** GET
- **URL:** `{{BASE_URL}}/bcms/cars/1`
- **No headers needed**

### Step 6: Test Protected Endpoints
**Now test endpoints that need authentication:**

#### 6.1 Create "Get Current User" Request
- **Method:** GET
- **URL:** `{{BASE_URL}}/bcms/auth/user`
- **Headers:** (automatically added by pre-request script)

#### 6.2 Create "List Makes" Request
- **Method:** GET
- **URL:** `{{BASE_URL}}/bcms/makes`
- **Headers:** (automatically added by pre-request script)

### Step 7: Test Manager-Only Endpoints
**These need Manager role:**

#### 7.1 Create "List Users" Request
- **Method:** GET
- **URL:** `{{BASE_URL}}/bcms/users`
- **Headers:** (automatically added by pre-request script)

### Step 8: Test CRUD Operations
**Start with simple operations:**

#### 8.1 Create "Create Make" Request
- **Method:** POST
- **URL:** `{{BASE_URL}}/bcms/makes`
- **Headers:** `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "name": "Toyota",
    "description": "Japanese car manufacturer"
}
```

## Recommended Testing Order

### Phase 1: Authentication & Basic Setup
1. ✅ Sign In (get tokens)
2. ✅ Get Current User
3. ✅ List Cars (public)
4. ✅ Get Car (public)

### Phase 2: Basic CRUD Operations
1. ✅ List Makes
2. ✅ Create Make
3. ✅ Get Make
4. ✅ Update Make
5. ✅ Delete Make

### Phase 3: User Management (Manager Only)
1. ✅ List Users
2. ✅ Create User
3. ✅ Get User
4. ✅ Update User
5. ✅ Delete User

### Phase 4: Car Management
1. ✅ Create Car
2. ✅ Update Car
3. ✅ Delete Car

### Phase 5: Advanced Features
1. ✅ Buyer Management
2. ✅ Sale Management
3. ✅ Reports
4. ✅ Finance Records

## Common Issues & Solutions

### Issue 1: "No bearer token provided"
**Solution:** Make sure you've run the Sign In request first and the ACCESS_TOKEN is set in your environment.

### Issue 2: "Unauthorized" (403)
**Solution:** Check if you're using a Manager account for Manager-only endpoints.

### Issue 3: "Invalid token"
**Solution:** Your token might have expired. Run the Sign In request again to get a new token.

### Issue 4: "Connection refused"
**Solution:** Make sure your Laravel server is running on `http://localhost:8000`

## Quick Test Checklist

Before testing all endpoints, verify these basics:

- [ ] Laravel server is running
- [ ] Database is connected
- [ ] Environment variables are set
- [ ] Sign In works and sets tokens
- [ ] Public endpoints work without tokens
- [ ] Protected endpoints work with tokens

## Next Steps

Once you've completed the basic setup:

1. **Add all remaining endpoints** following the patterns in the main documentation
2. **Test error cases** (invalid tokens, wrong permissions, etc.)
3. **Create test data** for comprehensive testing
4. **Set up automated testing** with Postman's collection runner

## Tips for Success

1. **Start with authentication** - everything else depends on it
2. **Test one endpoint at a time** - don't rush
3. **Use the pre-request script** - it saves time
4. **Check environment variables** - they're crucial
5. **Test both success and error cases** - be thorough

This approach will give you a solid foundation for testing your entire BCMS API! 