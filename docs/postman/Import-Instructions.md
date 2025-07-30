# Postman Import Instructions

## Quick Setup Guide

### Step 1: Import the Collection
1. **Open Postman**
2. **Click "Import"** (top left corner)
3. **Select file:** `docs/postman/BCMS-API-Collection.json`
4. **Click "Import"**

### Step 2: Import the Environment
1. **Click "Import"** again
2. **Select file:** `docs/postman/BCMS-Local-Environment.json`
3. **Click "Import"**

### Step 3: Select Environment
1. **Click the environment dropdown** (top right)
2. **Select "BCMS Local Environment"**

### Step 4: Start Testing
1. **Go to "1. Authentication" folder**
2. **Run "Sign In" first** - this sets your tokens automatically
3. **Test other endpoints** - authorization is handled automatically

## Environment Variables Included

### Authentication Variables
- `BASE_URL` - Your API base URL
- `ACCESS_TOKEN` - Automatically set after sign-in
- `REFRESH_TOKEN` - Automatically set after sign-in
- `USER_ID` - Automatically set after sign-in

### Test Credentials
- `MANAGER_EMAIL` - Manager account email
- `MANAGER_PASSWORD` - Manager account password
- `USER_EMAIL` - Regular user email
- `USER_PASSWORD` - Regular user password

### Test Data IDs
- `TEST_CAR_ID` - For testing car operations
- `TEST_MAKE_ID` - For testing make operations
- `TEST_MODEL_ID` - For testing model operations
- `TEST_BUYER_ID` - For testing buyer operations
- `TEST_SALE_ID` - For testing sale operations
- `TEST_FINANCE_RECORD_ID` - For testing finance operations

### Test Dates
- `TEST_DATE` - For daily reports
- `TEST_YEAR` - For yearly reports
- `TEST_MONTH` - For monthly reports

## Production Setup

For production testing:
1. **Import:** `docs/postman/BCMS-Production-Environment.json`
2. **Update the BASE_URL** to your production domain
3. **Update credentials** with real production accounts
4. **Test carefully** - production data is live!

## Security Notes

- **Passwords are marked as "secret"** - they won't be visible in logs
- **Tokens are automatically managed** - no manual token copying needed
- **Environment variables are scoped** - won't affect other collections

## Troubleshooting

### "No environment selected"
- Make sure you've imported the environment file
- Select "BCMS Local Environment" from the dropdown

### "Invalid token"
- Run the "Sign In" request again to get fresh tokens
- Check that your credentials are correct

### "Connection refused"
- Make sure your Laravel server is running on `http://localhost:8000`
- Check that the BASE_URL is correct

### "Unauthorized (403)"
- Make sure you're using a Manager account for Manager-only endpoints
- Check that your user has the correct role

## Ready to Test!

Once imported, you'll have:
- ✅ 47 endpoints ready to test
- ✅ Automatic token management
- ✅ Role-based access control testing
- ✅ Complete CRUD operations
- ✅ Error case handling

Start with authentication and work your way through the folders! 