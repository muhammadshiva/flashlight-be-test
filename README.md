<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com/)**
-   **[Tighten Co.](https://tighten.co)**
-   **[WebReinvent](https://webreinvent.com/)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
-   **[Cyber-Duck](https://cyber-duck.co.uk)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Jump24](https://jump24.co.uk)**
-   **[Redberry](https://redberry.international/laravel/)**
-   **[Active Logic](https://activelogic.com)**
-   **[byte5](https://byte5.de)**
-   **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Flashlight Backend

This is the backend application for Flashlight wash management system.

## Features

### Shift Management System

The shift management system allows cashiers to track their work shifts with cash management and transaction monitoring.

#### Database Schema

**Shifts Table:**

-   `id` - Primary key
-   `user_id` - Foreign key to users table (cashier)
-   `start_time` - Shift start timestamp
-   `end_time` - Shift end timestamp (nullable)
-   `initial_cash` - Cash amount at shift start
-   `received_from` - Name of person who gave initial cash
-   `final_cash` - Cash amount at shift end (nullable)
-   `total_sales` - Total sales during shift (auto-calculated)
-   `status` - Enum: active, closed, canceled
-   `created_at` & `updated_at` - Laravel timestamps

**Relationships:**

-   `shifts.user_id` → `users.id` (one-to-many)
-   `shifts.id` → `wash_transactions.shift_id` (one-to-many)

#### API Endpoints

**1. Start Shift - POST /api/shifts/start**

```json
{
    "kas_awal": 100000,
    "diterima_dari": "Manager"
}
```

Response:

```json
{
    "status": "success",
    "message": "Shift started successfully",
    "data": {
        "shift": {
            "id": 1,
            "user_id": 2,
            "start_time": "2025-06-28T12:00:00Z",
            "initial_cash": "100000.00",
            "received_from": "Manager",
            "status": "active",
            "user": {
                "id": 2,
                "name": "John Doe",
                "email": "john@example.com"
            }
        }
    }
}
```

**2. End Shift - POST /api/shifts/end**

```json
{
    "kas_fisik": 150000
}
```

Response:

```json
{
    "status": "success",
    "message": "Shift ended successfully",
    "data": {
        "shift": {
            "id": 1,
            "status": "closed",
            "end_time": "2025-06-28T20:00:00Z",
            "final_cash": "150000.00",
            "total_sales": "45000.00"
        },
        "summary": {
            "initial_cash": "100000.00",
            "total_sales": "45000.00",
            "expected_cash": "145000.00",
            "final_cash": "150000.00",
            "difference": "5000.00",
            "total_transactions": 5,
            "shift_duration": "8 hours ago"
        }
    }
}
```

**3. Get Current Active Shift - GET /api/shifts/current**

**4. Get Shift History - GET /api/shifts/history**

**5. Get Shift Details - GET /api/shifts/{id}**

**6. Get Transactions by Shift - GET /api/transactions?shift_id={shift_id}**

**7. Get Shift Transactions with Pagination - GET /api/shifts/{id}/transactions**

Query Parameters:

-   `page` (optional) - Page number, default: 1
-   `per_page` (optional) - Items per page, default: 10

Response:

```json
{
    "status": "success",
    "message": "Shift transactions retrieved successfully",
    "data": {
        "current_page": 1,
        "per_page": 10,
        "total": 30,
        "transactions": [
            {
                "invoice_number": "TRX-20250628-001",
                "time": "10:00",
                "customer_name": "Muhammad Shiva",
                "items": [
                    {
                        "name": "Clean Motobike",
                        "qty": 1,
                        "price": 42500,
                        "subtotal": 42500
                    },
                    {
                        "name": "Rust Remover",
                        "qty": 2,
                        "price": 12000,
                        "subtotal": 24000
                    },
                    {
                        "name": "French fries",
                        "qty": 1,
                        "price": 9000,
                        "subtotal": 9000
                    }
                ],
                "payment": {
                    "cash": 12500,
                    "debit": 63000
                },
                "total": 75500
            },
            {
                "invoice_number": "TRX-20250628-002",
                "time": "10:30",
                "customer_name": "John Doe",
                "items": [
                    {
                        "name": "Car Wash Premium",
                        "qty": 1,
                        "price": 85000,
                        "subtotal": 85000
                    },
                    {
                        "name": "Wax Treatment",
                        "qty": 1,
                        "price": 35000,
                        "subtotal": 35000
                    }
                ],
                "payment": {
                    "cash": 120000,
                    "debit": 0
                },
                "total": 120000
            }
        ],
        "totals": {
            "cash": 132500,
            "debit": 63000
        }
    }
}
```

Full Example Response:

```json
{
    "status": "success",
    "message": "Shift transactions retrieved successfully",
    "data": {
        "current_page": 1,
        "per_page": 10,
        "total": 1,
        "transactions": [
            {
                "invoice_number": "TRX-20250628-001",
                "time": "10:00",
                "customer_name": "Muhammad Shiva",
                "items": [
                    {
                        "name": "Clean Motobike",
                        "qty": 1,
                        "price": 42500,
                        "subtotal": 42500
                    },
                    {
                        "name": "Rust Remover",
                        "qty": 2,
                        "price": 12000,
                        "subtotal": 24000
                    },
                    {
                        "name": "French fries",
                        "qty": 1,
                        "price": 9000,
                        "subtotal": 9000
                    }
                ],
                "payment": {
                    "cash": 12500,
                    "debit": 63000
                },
                "total": 75500
            }
        ],
        "totals": {
            "cash": 12500,
            "debit": 63000
        }
    }
}
```

#### Business Rules & Validations

1. **Single Active Shift**: Only one active shift allowed per cashier
2. **Required Shift Closure**: Must close current shift before starting new one
3. **Auto Transaction Assignment**: New transactions automatically linked to active shift
4. **Auto Calculations**: Total sales calculated from completed transactions

#### Admin Panel (Filament)

**Access Control:**

-   Only Owner and Admin roles can access shift management
-   Located in "Transaction Management" navigation group

**Features:**

-   View all shifts with filtering by cashier, status, date range
-   Detailed shift information with transaction counts
-   Cash difference calculations (final vs expected)
-   Edit capability for active shifts only
-   Comprehensive reporting and analytics

#### Usage Examples

**Starting a Shift:**

```bash
curl -X POST http://localhost:8000/api/shifts/start \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "kas_awal": 100000,
    "diterima_dari": "Manager"
  }'
```

**Creating Transaction (Auto-assigns to Active Shift):**

```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "customer_vehicle_id": 1,
    "user_id": 2,
    "payment_method": "cash",
    "wash_date": "2025-06-28T14:00:00Z",
    "products": [
      {
        "product_id": 1,
        "quantity": 1
      }
    ]
  }'
```

**Ending a Shift:**

```bash
curl -X POST http://localhost:8000/api/shifts/end \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "kas_fisik": 150000
  }'
```

**Getting Shift Transactions:**

```bash
curl -X GET "http://localhost:8000/api/shifts/1/transactions?page=1&per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy environment file: `cp .env.example .env`
4. Generate application key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Seed database: `php artisan db:seed`
7. Start server: `php artisan serve`

## Requirements

-   PHP 8.1+
-   MySQL 8.0+
-   Composer
-   Laravel 11

## Contributing

Please follow PSR-12 coding standards and write tests for new features.
