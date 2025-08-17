# Flashlight Backend API Documentation

## Base URL

```
http://localhost:8000/api
```

## Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

---

## 1. Authentication Endpoints

### 1.1 User Registration

**POST** `/auth/register`

**Payload:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+6281234567890",
    "user_type": "customer"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+6281234567890",
            "user_type": "customer",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

### 1.2 User Login

**POST** `/auth/login`

**Payload:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+6281234567890",
            "user_type": "customer"
        },
        "token": "1|abc123def456..."
    }
}
```

### 1.3 Login with FCM Token

**POST** `/auth/login-with-fcm`

**Payload:**

```json
{
    "email": "john@example.com",
    "password": "password123",
    "fcm_token": "fcm_token_here",
    "device_id": "device_123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+6281234567890",
            "user_type": "customer"
        },
        "token": "1|abc123def456..."
    }
}
```

### 1.4 Get FCM Token

**GET** `/auth/fcm-token`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "fcm_token": "fcm_token_here",
        "device_id": "device_123"
    }
}
```

### 1.5 Logout

**POST** `/auth/logout`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### 1.6 Device Logout

**POST** `/auth/device-logout`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "device_id": "device_123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Device logged out successfully"
}
```

### 1.7 Refresh Token

**POST** `/auth/refresh-token`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "token": "2|new_token_here..."
    }
}
```

---

## 2. User Management Endpoints

### 2.1 Get All Users

**GET** `/users`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+6281234567890",
            "user_type": "customer",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 2.2 Create User

**POST** `/users`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+6281234567891",
    "user_type": "staff"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "phone": "+6281234567891",
        "user_type": "staff",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 2.3 Get User by ID

**GET** `/users/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+6281234567890",
        "user_type": "customer",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 2.4 Update User

**PUT** `/users/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "John Updated",
    "phone": "+6281234567899"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 1,
        "name": "John Updated",
        "email": "john@example.com",
        "phone": "+6281234567899",
        "user_type": "customer",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 2.5 Delete User

**DELETE** `/users/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

### 2.6 Get User Profile

**GET** `/users/profile`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+6281234567890",
        "user_type": "customer"
    }
}
```

### 2.7 Update Profile

**PUT** `/users/profile`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "John Updated",
    "phone": "+6281234567899"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 1,
        "name": "John Updated",
        "phone": "+6281234567899"
    }
}
```

### 2.8 Update Password

**PUT** `/users/password`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "current_password": "oldpassword",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Password updated successfully"
}
```

### 2.9 Update FCM Token

**PATCH** `/users/{id}/fcm-token`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "fcm_token": "new_fcm_token_here",
    "device_id": "device_123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "FCM token updated successfully"
}
```

### 2.10 Get User by Phone Number

**GET** `/users/phone/{phoneNumber}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+6281234567890",
        "user_type": "customer"
    }
}
```

---

## 3. Customer Management Endpoints

### 3.1 Get All Customers

**GET** `/customers`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "membership_type_id": 1,
            "membership_expiry": "2024-12-31",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "user": {
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "+6281234567890"
            },
            "membership_type": {
                "name": "Basic",
                "price": 100000
            }
        }
    ]
}
```

### 3.2 Create Customer

**POST** `/customers`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "user_id": 2,
    "membership_type_id": 1,
    "membership_expiry": "2024-12-31"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Customer created successfully",
    "data": {
        "id": 2,
        "user_id": 2,
        "membership_type_id": 1,
        "membership_expiry": "2024-12-31",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 3.3 Get Customer by ID

**GET** `/customers/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "membership_type_id": 1,
        "membership_expiry": "2024-12-31",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 3.4 Update Customer

**PUT** `/customers/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "membership_type_id": 2,
    "membership_expiry": "2025-12-31"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer updated successfully",
    "data": {
        "id": 1,
        "membership_type_id": 2,
        "membership_expiry": "2025-12-31",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 3.5 Delete Customer

**DELETE** `/customers/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer deleted successfully"
}
```

### 3.6 Restore Customer

**POST** `/customers/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer restored successfully"
}
```

---

## 4. Membership Management Endpoints

### 4.1 Get All Memberships

**GET** `/memberships`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Basic",
            "description": "Basic membership",
            "price": 100000,
            "duration_days": 30,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 4.2 Create Membership

**POST** `/memberships`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Premium",
    "description": "Premium membership",
    "price": 200000,
    "duration_days": 60
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Membership created successfully",
    "data": {
        "id": 2,
        "name": "Premium",
        "description": "Premium membership",
        "price": 200000,
        "duration_days": 60,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

---

## 4. Product Category Management Endpoints

### 4.1 Get All Product Categories

**GET** `/product-categories`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Car Wash Services",
            "description": "Automotive cleaning services",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 4.2 Create Product Category

**POST** `/product-categories`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Detailing Services",
    "description": "Professional car detailing services"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Product category created successfully",
    "data": {
        "id": 2,
        "name": "Detailing Services",
        "description": "Professional car detailing services",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 4.3 Get Product Category by ID

**GET** `/product-categories/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Car Wash Services",
        "description": "Automotive cleaning services",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 4.4 Update Product Category

**PUT** `/product-categories/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Updated Car Wash Services",
    "description": "Updated automotive cleaning services"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product category updated successfully",
    "data": {
        "id": 1,
        "name": "Updated Car Wash Services",
        "description": "Updated automotive cleaning services",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 4.5 Delete Product Category

**DELETE** `/product-categories/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product category deleted successfully"
}
```

### 4.6 Restore Product Category

**POST** `/product-categories/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product category restored successfully"
}
```

---

## 5. Product Management Endpoints

### 4.1 Get All Products

**GET** `/products`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Car Wash Basic",
            "description": "Basic car wash service",
            "price": 50000,
            "category_id": 1,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 5.2 Create Product

**POST** `/products`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Car Wash Premium",
    "description": "Premium car wash service",
    "price": 75000,
    "category_id": 1
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "id": 2,
        "name": "Car Wash Premium",
        "description": "Premium car wash service",
        "price": 75000,
        "category_id": 1,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 5.3 Get Product by ID

**GET** `/products/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Car Wash Basic",
        "description": "Basic car wash service",
        "price": 50000,
        "category_id": 1,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 5.4 Update Product

**PUT** `/products/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "name": "Updated Car Wash Basic",
    "price": 55000
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product updated successfully",
    "data": {
        "id": 1,
        "name": "Updated Car Wash Basic",
        "price": 55000,
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 5.5 Delete Product

**DELETE** `/products/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

### 5.6 Restore Product

**POST** `/products/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Product restored successfully"
}
```

---

## 6. Vehicle Management Endpoints

### 6.1 Get All Vehicles

**GET** `/vehicles`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "brand": "Toyota",
            "model": "Avanza",
            "year": 2020,
            "color": "White",
            "license_plate": "B 1234 ABC",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 6.2 Create Vehicle

**POST** `/vehicles`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "brand": "Honda",
    "model": "Brio",
    "year": 2021,
    "color": "Red",
    "license_plate": "B 5678 DEF"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Vehicle created successfully",
    "data": {
        "id": 2,
        "brand": "Honda",
        "model": "Brio",
        "year": 2021,
        "color": "Red",
        "license_plate": "B 5678 DEF",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 6.3 Get Vehicle by ID

**GET** `/vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "brand": "Toyota",
        "model": "Avanza",
        "year": 2020,
        "color": "White",
        "license_plate": "B 1234 ABC",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 6.4 Update Vehicle

**PUT** `/vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "color": "Black",
    "year": 2021
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Vehicle updated successfully",
    "data": {
        "id": 1,
        "color": "Black",
        "year": 2021,
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 6.5 Delete Vehicle

**DELETE** `/vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Vehicle deleted successfully"
}
```

### 6.6 Restore Vehicle

**POST** `/vehicles/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Vehicle restored successfully"
}
```

---

## 7. Customer Vehicle Management Endpoints

### 7.1 Get All Customer Vehicles

**GET** `/customer-vehicles`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer_id": 1,
            "vehicle_id": 1,
            "is_primary": true,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "customer": {
                "id": 1,
                "name": "John Doe"
            },
            "vehicle": {
                "id": 1,
                "brand": "Toyota",
                "model": "Avanza"
            }
        }
    ]
}
```

### 7.2 Get Customer Vehicles by Customer ID

**GET** `/customer-vehicles/customer/{customerId}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "vehicle_id": 1,
            "is_primary": true,
            "vehicle": {
                "brand": "Toyota",
                "model": "Avanza",
                "license_plate": "B 1234 ABC"
            }
        }
    ]
}
```

### 7.3 Get Customer Vehicles by Vehicle ID

**GET** `/customer-vehicles/vehicle/{vehicleId}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer_id": 1,
            "is_primary": true,
            "customer": {
                "name": "John Doe",
                "phone": "+6281234567890"
            }
        }
    ]
}
```

### 7.4 Get Customer Vehicle by License Plate

**GET** `/customer-vehicles/license-plate/{licensePlate}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer_id": 1,
        "vehicle_id": 1,
        "is_primary": true,
        "customer": {
            "name": "John Doe"
        },
        "vehicle": {
            "brand": "Toyota",
            "model": "Avanza"
        }
    }
}
```

### 7.5 Create Customer Vehicle

**POST** `/customer-vehicles`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "customer_id": 1,
    "vehicle_id": 2,
    "is_primary": false
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Customer vehicle created successfully",
    "data": {
        "id": 2,
        "customer_id": 1,
        "vehicle_id": 2,
        "is_primary": false,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 7.6 Get Customer Vehicle by ID

**GET** `/customer-vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "customer_id": 1,
        "vehicle_id": 1,
        "is_primary": true,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 7.7 Update Customer Vehicle

**PUT** `/customer-vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "is_primary": false
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer vehicle updated successfully",
    "data": {
        "id": 1,
        "is_primary": false,
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 7.8 Delete Customer Vehicle

**DELETE** `/customer-vehicles/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer vehicle deleted successfully"
}
```

### 7.9 Restore Customer Vehicle

**POST** `/customer-vehicles/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Customer vehicle restored successfully"
}
```

---

## 8. Staff Management Endpoints

### 8.1 Get All Staff

**GET** `/staff`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "position": "Cashier",
            "hire_date": "2024-01-01",
            "salary": 3000000,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "user": {
                "name": "Jane Doe",
                "email": "jane@example.com"
            }
        }
    ]
}
```

### 8.2 Create Staff

**POST** `/staff`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "user_id": 3,
    "position": "Washer",
    "hire_date": "2024-01-15",
    "salary": 2500000
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Staff created successfully",
    "data": {
        "id": 2,
        "user_id": 3,
        "position": "Washer",
        "hire_date": "2024-01-15",
        "salary": 2500000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 8.3 Get Staff by ID

**GET** `/staff/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 2,
        "position": "Cashier",
        "hire_date": "2024-01-01",
        "salary": 3000000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 8.4 Update Staff

**PUT** `/staff/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "position": "Senior Cashier",
    "salary": 3500000
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Staff updated successfully",
    "data": {
        "id": 1,
        "position": "Senior Cashier",
        "salary": 3500000,
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 8.5 Delete Staff

**DELETE** `/staff/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Staff deleted successfully"
}
```

### 8.6 Restore Staff

**POST** `/staff/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Staff restored successfully"
}
```

---

## 9. Work Order Endpoints (Self Ordering Kiosk)

### 9.1 Get All Work Orders

**GET** `/work-orders`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer_id": 1,
            "status": "pending",
            "total_price": 150000,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 9.2 Create Work Order

**POST** `/work-orders`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "customer_id": 1,
    "products": [
        {
            "product_id": 1,
            "quantity": 2
        },
        {
            "product_id": 2,
            "quantity": 1
        }
    ]
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Work order created successfully",
    "data": {
        "id": 2,
        "customer_id": 1,
        "status": "pending",
        "total_price": 175000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 9.3 Get Work Order Queue

**GET** `/work-orders/queue`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer_id": 1,
            "status": "pending",
            "queue_number": "WO-001",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 9.4 Confirm Work Order

**POST** `/work-orders/{workOrder}/confirm`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Work order confirmed and wash transaction created",
    "data": {
        "work_order": {
            "id": 1,
            "status": "confirmed"
        },
        "wash_transaction": {
            "id": 1,
            "transaction_number": "WT-001"
        }
    }
}
```

---

## 10. POS Transaction Endpoints

### 10.1 Get All POS Transactions

**GET** `/pos-transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "customer_id": 1,
            "total_amount": 100000,
            "payment_method": "cash",
            "status": "completed",
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 10.2 Create POS Transaction

**POST** `/pos-transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "customer_id": 1,
    "products": [
        {
            "product_id": 1,
            "quantity": 1,
            "price": 50000
        }
    ],
    "payment_method": "cash",
    "total_amount": 50000
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "POS transaction created successfully",
    "data": {
        "id": 2,
        "customer_id": 1,
        "total_amount": 50000,
        "payment_method": "cash",
        "status": "completed",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 10.3 Process Wash Transaction Payment

**POST** `/pos-transactions/wash-transaction/{washTransaction}/payment`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "payment_method": "cash",
    "amount_paid": 100000,
    "shift_id": 1
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Payment processed successfully",
    "data": {
        "payment_id": 1,
        "status": "completed",
        "transaction_number": "PT-001"
    }
}
```

---

## 11. Wash Transaction Endpoints

### 11.1 Get All Wash Transactions

**GET** `/wash-transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "transaction_number": "WT-001",
            "customer_id": 1,
            "status": "pending",
            "total_price": 100000,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 11.2 Create Wash Transaction

**POST** `/wash-transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "customer_id": 1,
    "vehicle_id": 1,
    "products": [
        {
            "product_id": 1,
            "quantity": 1
        }
    ],
    "total_price": 50000
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Wash transaction created successfully",
    "data": {
        "id": 2,
        "transaction_number": "WT-002",
        "customer_id": 1,
        "vehicle_id": 1,
        "status": "pending",
        "total_price": 50000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 11.3 Start Service

**POST** `/wash-transactions/{washTransaction}/start-service`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Service started successfully",
    "data": {
        "id": 1,
        "status": "in_progress",
        "started_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 11.4 Complete Service

**POST** `/wash-transactions/{washTransaction}/complete-service`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Service completed successfully",
    "data": {
        "id": 1,
        "status": "completed",
        "completed_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

---

## 12. Shift Management Endpoints

### 12.1 Start Shift

**POST** `/shifts/start`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "user_id": 1,
    "start_time": "2024-01-01T08:00:00"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Shift started successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "start_time": "2024-01-01T08:00:00",
        "status": "active"
    }
}
```

### 12.2 End Shift

**POST** `/shifts/end`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "shift_id": 1,
    "end_time": "2024-01-01T17:00:00"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Shift ended successfully",
    "data": {
        "id": 1,
        "end_time": "2024-01-01T17:00:00",
        "status": "completed"
    }
}
```

### 12.3 Get Current Shift

**GET** `/shifts/current`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "start_time": "2024-01-01T08:00:00",
        "status": "active"
    }
}
```

---

## 13. FCM Management Endpoints

### 13.1 Update FCM Token

**PATCH** `/fcm/token`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "fcm_token": "new_fcm_token_here",
    "device_id": "device_123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "FCM token updated successfully"
}
```

### 13.2 Remove FCM Token

**DELETE** `/fcm/token`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "device_id": "device_123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "FCM token removed successfully"
}
```

### 13.3 Send FCM Notification

**POST** `/fcm/send`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "title": "Notification Title",
    "body": "Notification body message",
    "fcm_token": "fcm_token_here",
    "data": {
        "key1": "value1",
        "key2": "value2"
    }
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Notification sent successfully",
    "data": {
        "message_id": "message_123"
    }
}
```

---

## 15. Legacy Transaction Routes (Backward Compatibility)

### 15.1 Get All Legacy Transactions

**GET** `/transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "transaction_number": "TXN-001",
            "customer_id": 1,
            "status": "completed",
            "total_price": 100000,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 15.2 Get Legacy Transactions by Customer ID

**GET** `/transactions/customer/{customerId}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "transaction_number": "TXN-001",
            "status": "completed",
            "total_price": 100000,
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

### 15.3 Create Legacy Transaction

**POST** `/transactions`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "customer_id": 1,
    "total_price": 100000
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Transaction created successfully",
    "data": {
        "id": 2,
        "transaction_number": "TXN-002",
        "customer_id": 1,
        "total_price": 100000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 15.4 Get Legacy Transaction by ID

**GET** `/transactions/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "transaction_number": "TXN-001",
        "customer_id": 1,
        "status": "completed",
        "total_price": 100000,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 15.5 Update Legacy Transaction

**PUT** `/transactions/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "total_price": 120000
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction updated successfully",
    "data": {
        "id": 1,
        "total_price": 120000,
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 15.6 Restore Legacy Transaction

**POST** `/transactions/{id}/restore`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction restored successfully"
}
```

### 15.7 Complete Legacy Transaction

**POST** `/transactions/{id}/complete`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction completed successfully",
    "data": {
        "id": 1,
        "status": "completed",
        "completed_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 15.8 Cancel Legacy Transaction

**POST** `/transactions/{id}/cancel`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction cancelled successfully",
    "data": {
        "id": 1,
        "status": "cancelled",
        "cancelled_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### 15.9 Delete Legacy Transaction

**DELETE** `/transactions/{id}`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction deleted successfully"
}
```

---

## 16. Additional Utility Endpoints

### 16.1 Get Next Transaction Number

**GET** `/trx-next-number`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "next_number": "WT-002"
    }
}
```

### 16.2 Get Previous Transaction Number

**GET** `/trx-prev-number`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "previous_number": "WT-001"
    }
}
```

---

## 17. Payment Endpoints

### 17.1 Send Transaction Data

**POST** `/payment/send-transaction-data`

**Headers:**

```
Authorization: Bearer {token}
```

**Payload:**

```json
{
    "transaction_id": 1,
    "transaction_type": "wash_transaction",
    "amount": 100000,
    "payment_method": "cash"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Transaction data sent successfully",
    "data": {
        "reference_id": "ref_123"
    }
}
```

### 17.2 Get Ongoing Transaction Data

**GET** `/payment/ongoing-transaction-data`

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "transaction_number": "WT-001",
            "status": "in_progress",
            "amount": 100000
        }
    ]
}
```

---

## Error Responses

### 400 Bad Request

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

### 401 Unauthorized

```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

### 403 Forbidden

```json
{
    "success": false,
    "message": "Access denied"
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "Resource not found"
}
```

### 422 Unprocessable Entity

```json
{
    "success": false,
    "message": "The given data was invalid",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

### 500 Internal Server Error

```json
{
    "success": false,
    "message": "Internal server error"
}
```

---

## Postman Collection Setup

1. **Create a new collection** in Postman
2. **Set collection variables:**

    - `base_url`: `http://localhost:8000/api`
    - `token`: Leave empty initially

3. **Set up authentication:**

    - Use the login endpoint to get a token
    - Set the `token` variable with the response token
    - Use `{{token}}` in Authorization headers

4. **Import this documentation** and test each endpoint systematically

## Testing Flow

1. **Start with authentication endpoints** (register/login)
2. **Test CRUD operations** for each resource
3. **Test business logic** (work orders, payments, etc.)
4. **Verify error handling** with invalid data
5. **Test authorization** with different user types

## Notes

-   All timestamps are in ISO 8601 format
-   Prices are in Indonesian Rupiah (IDR)
-   User types: `customer`, `staff`, `admin`
-   Transaction statuses: `pending`, `in_progress`, `completed`, `cancelled`
-   Payment methods: `cash`, `qris`, `card`
