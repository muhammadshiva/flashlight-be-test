# Work Order & POS System Refactoring Documentation

## Overview

This document describes the refactoring of the existing system to support two separate client platforms:

1. **Self Ordering Kiosk** - Work Order system for customers to place wash orders
2. **Point of Sales (POS)** - Transaction system for cashiers to process payments

## Architecture Changes

### Database Schema

#### Work Orders Table (`work_orders`)

-   **Purpose**: Store customer orders from self-ordering kiosk
-   **Key Fields**:
    -   `order_number`: Unique order identifier (WO-YYYYMMDD-XXX)
    -   `customer_id`: Customer reference
    -   `customer_vehicle_id`: Vehicle reference
    -   `total_price`: Order total amount
    -   `status`: Order status (pending, confirmed, in_progress, ready_for_pickup, completed, cancelled)
    -   `queue_number`: Daily queue number for order processing
    -   `order_date`: When order was placed
    -   `special_instructions`: Customer special requests

#### POS Transactions Table (`pos_transactions`)

-   **Purpose**: Store payment transactions from cashier POS system
-   **Key Fields**:
    -   `transaction_number`: Unique transaction identifier (POS-YYYYMMDD-XXX)
    -   `work_order_id`: Optional link to work order (if payment for kiosk order)
    -   `customer_id`: Customer reference
    -   `user_id`: Cashier who processed transaction
    -   `shift_id`: Shift reference
    -   `subtotal`: Amount before tax/discount
    -   `tax_amount`: Tax amount
    -   `discount_amount`: Discount applied
    -   `total_amount`: Final amount
    -   `payment_method`: cash, qris, transfer, e_wallet
    -   `amount_paid`: Amount customer paid
    -   `change_amount`: Change given

#### Pivot Tables

-   `work_order_products`: Links work orders to products with quantities
-   `pos_transaction_products`: Links POS transactions to products with quantities

### Models

#### WorkOrder Model

-   **Location**: `app/Models/WorkOrder.php`
-   **Key Features**:
    -   Status management methods
    -   Queue number generation
    -   Order number generation
    -   Payment status checking
    -   Relationship to POSTransaction

#### POSTransaction Model

-   **Location**: `app/Models/POSTransaction.php`
-   **Key Features**:
    -   Transaction number generation
    -   Payment method validation
    -   Status management
    -   Relationship to WorkOrder

### API Endpoints

#### Work Order API (`/api/work-orders`)

-   `GET /api/work-orders` - List work orders with filters
-   `POST /api/work-orders` - Create new work order
-   `GET /api/work-orders/{id}` - Get work order details
-   `PUT /api/work-orders/{id}` - Update work order
-   `DELETE /api/work-orders/{id}` - Delete work order
-   `POST /api/work-orders/{id}/confirm` - Confirm work order
-   `POST /api/work-orders/{id}/cancel` - Cancel work order
-   `GET /api/work-orders/queue` - Get daily queue information
-   `GET /api/work-orders/customer/{customerId}` - Get customer work orders

#### POS Transaction API (`/api/pos-transactions`)

-   `GET /api/pos-transactions` - List POS transactions with filters
-   `POST /api/pos-transactions` - Create new transaction (direct sale)
-   `GET /api/pos-transactions/{id}` - Get transaction details
-   `PUT /api/pos-transactions/{id}` - Update transaction
-   `DELETE /api/pos-transactions/{id}` - Delete transaction
-   `POST /api/pos-transactions/work-order/{workOrderId}/payment` - Process work order payment
-   `GET /api/pos-transactions/customer/{customerId}` - Get customer transactions
-   `GET /api/pos-transactions/sales-report` - Get daily sales report

### Filament Admin Resources

#### WorkOrderResource

-   **Location**: `app/Filament/Resources/WorkOrderResource.php`
-   **Features**:
    -   Complete CRUD operations
    -   Status management
    -   Queue tracking
    -   Customer and product relationships
    -   Detailed view with order information

#### POSTransactionResource

-   **Location**: `app/Filament/Resources/POSTransactionResource.php`
-   **Features**:
    -   Transaction management
    -   Payment method tracking
    -   Sales reporting
    -   Work order integration
    -   Cashier performance tracking

## Workflow

### Self Ordering Kiosk Workflow

1. **Customer Places Order**:

    - Customer selects vehicle and products
    - System generates work order with queue number
    - Status: `pending`

2. **Order Confirmation**:

    - Staff confirms order
    - Status: `confirmed`
    - Customer receives queue number

3. **Service Processing**:

    - Staff starts service
    - Status: `in_progress`

4. **Service Completion**:

    - Service finished
    - Status: `ready_for_pickup`

5. **Payment & Completion**:
    - Customer pays at POS
    - POS transaction created with work order reference
    - Work order status: `completed`

### Direct POS Sale Workflow

1. **Direct Sale**:
    - Cashier creates transaction without work order
    - Customer selects products directly at counter
    - Payment processed immediately
    - Status: `completed`

## Key Benefits

### Separation of Concerns

-   **Work Orders**: Focus on service ordering and queue management
-   **POS Transactions**: Focus on payment processing and sales tracking

### Better Analytics

-   Track kiosk vs direct sales performance
-   Monitor queue efficiency
-   Analyze payment method preferences
-   Generate detailed sales reports

### Improved User Experience

-   Customers can place orders and get queue numbers
-   Cashiers have dedicated POS interface
-   Clear separation between ordering and payment

### Enhanced Reporting

-   Work order performance metrics
-   Sales by payment method
-   Cashier performance tracking
-   Queue management analytics

## Migration Notes

### Database Migration

Run the following migrations in order:

1. `create_work_orders_table`
2. `create_work_order_products_table`
3. `create_pos_transactions_table`
4. `create_pos_transaction_products_table`

### Backward Compatibility

-   Legacy `wash_transactions` table and API endpoints are maintained
-   Existing functionality continues to work
-   Gradual migration to new system possible

## Configuration

### Environment Variables

No additional environment variables required. The system uses existing database configuration.

### Permissions

Ensure proper role-based access:

-   **Kiosk**: Access to work order creation endpoints
-   **Cashier**: Access to POS transaction endpoints
-   **Admin**: Full access to both systems via Filament

## Testing

### API Testing

Use the following endpoints for testing:

```bash
# Create work order
POST /api/work-orders
{
    "customer_id": 1,
    "customer_vehicle_id": 1,
    "order_date": "2024-01-01 10:00:00",
    "products": [
        {"product_id": 1, "quantity": 1}
    ]
}

# Process work order payment
POST /api/pos-transactions/work-order/1/payment
{
    "user_id": 1,
    "payment_method": "cash",
    "amount_paid": 50000
}
```

### Database Testing

Verify relationships and constraints work correctly:

-   Work order can exist without POS transaction
-   POS transaction can exist without work order
-   Products are properly linked to both systems

## Future Enhancements

### Planned Features

1. **Real-time Queue Updates**: WebSocket integration for live queue status
2. **Mobile App Integration**: API endpoints for mobile applications
3. **Advanced Analytics**: Business intelligence dashboard
4. **Loyalty Program**: Integration with customer rewards system

### Performance Optimizations

1. **Database Indexing**: Optimize queries for large datasets
2. **Caching**: Implement Redis for frequently accessed data
3. **Queue Management**: Optimize queue number generation

## Support

For technical support or questions about this refactoring:

1. Check the API documentation in Postman/Swagger
2. Review the Filament admin interface
3. Examine the model relationships and methods
4. Test the workflow using the provided endpoints

---

**Created**: August 2024  
**Version**: 1.0  
**Last Updated**: August 2024
