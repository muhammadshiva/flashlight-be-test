# Postman Setup Guide for Flashlight API

## Quick Start

### 1. Import Postman Collection

1. Open Postman
2. Click "Import" button
3. Select the `Flashlight_API_Collection.postman_collection.json` file
4. The collection will be imported with all endpoints organized by category

### 2. Set Environment Variables

1. In Postman, click on the collection name "Flashlight Backend API"
2. Go to "Variables" tab
3. Set the following variables:
    - `base_url`: `http://localhost:8000/api` (or your server URL)
    - `token`: Leave empty initially

### 3. Authentication Flow

1. **First, test the registration endpoint:**

    - Use "Authentication > Register" endpoint
    - Modify the payload with your test data
    - Send the request

2. **Then login to get a token:**

    - Use "Authentication > Login" endpoint
    - Use the credentials from registration
    - Copy the `token` from the response

3. **Set the token variable:**
    - Go back to collection variables
    - Set `token` value to the token you received
    - All subsequent requests will automatically include the Bearer token

### 4. Test Endpoints

-   Start with basic CRUD operations (Users, Products)
-   Test business logic (Work Orders, Wash Transactions)
-   Verify error handling with invalid data

## Collection Structure

The collection is organized into logical groups:

-   **Authentication**: Register, login, logout, FCM token management
-   **Customers**: Customer management and membership tracking
-   **Users**: CRUD operations for user management
-   **Memberships**: Membership type management
-   **Product Categories**: Product category management
-   **Products**: Product management and pricing
-   **Vehicles**: Vehicle information management
-   **Customer Vehicles**: Customer-vehicle relationship management
-   **Staff**: Staff management and employment details
-   **Work Orders**: Self-ordering kiosk functionality
-   **Wash Transactions**: Service management
-   **POS Transactions**: Point of sales operations
-   **Shifts**: Staff shift management
-   **Legacy Transactions**: Backward compatibility endpoints
-   **FCM**: Push notification management

## Testing Tips

1. **Start with public endpoints** (register/login) before testing protected ones
2. **Use realistic test data** that matches your database schema
3. **Test error scenarios** by sending invalid data
4. **Check response status codes** and error messages
5. **Verify data consistency** across related endpoints

## Common Issues

1. **401 Unauthorized**: Check if token is set correctly
2. **404 Not Found**: Verify the endpoint URL and base_url variable
3. **422 Validation Error**: Check the request payload format
4. **500 Server Error**: Check server logs for detailed error information

## API Documentation

Refer to `API_DOCUMENTATION.md` for:

-   Complete endpoint details
-   Request/response examples
-   Error response formats
-   Business logic explanations

## Next Steps

1. Test all endpoints systematically
2. Create test scenarios for your specific use cases
3. Set up automated testing if needed
4. Document any custom business rules or validations
