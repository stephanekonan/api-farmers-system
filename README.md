# API Farmers System

A comprehensive Laravel API for managing farmers, transactions, debts, and repayments in agricultural credit management.

## Features

- **Authentication & Authorization**: Secure API with Sanctum tokens and role-based access
- **Farmer Management**: Complete farmer profiles with credit limits and tracking
- **Transaction System**: Sales and purchase transactions with item management
- **Debt Management**: Track outstanding debts with payment schedules
- **Repayment Tracking**: Monitor and manage debt repayments
- **Product Catalog**: Categorized product management
- **User Management**: Role-based user administration

## Tech Stack

- **Backend**: Laravel 11.x
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL (configurable)
- **API Documentation**: RESTful API design
- **Testing**: PHPUnit

## Installation

1. Clone the repository
```bash
git clone <repository-url>
cd api-farmers-system
```

2. Install dependencies
```bash
composer install
npm install
```

3. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env` file
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=farmers_system
DB_USERNAME=root
DB_PASSWORD=
```

5. Run migrations and seeders
```bash
php artisan migrate
php artisan db:seed
```

6. Start development server
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user
- `GET /api/auth/sessions` - List active sessions

### Farmers Management
- `GET /api/farmers` - List all farmers
- `POST /api/farmers` - Create new farmer
- `GET /api/farmers/{id}` - Get farmer details
- `PUT /api/farmers/{id}` - Update farmer
- `DELETE /api/farmers/{id}` - Delete farmer
- `GET /api/farmers/{id}/financial-summary` - Get financial summary
- `GET /api/farmers/{id}/debt-summary` - Get debt summary

### Transactions
- `GET /api/transactions` - List transactions
- `POST /api/transactions` - Create transaction
- `GET /api/transactions/{id}` - Get transaction details
- `GET /api/transactions/farmer/{farmerId}` - Get farmer transactions
- `GET /api/transactions/statistics` - Transaction statistics

### Debts
- `GET /api/debts` - List all debts
- `POST /api/debts` - Create new debt
- `GET /api/debts/outstanding` - Get outstanding debts
- `GET /api/debts/overdue` - Get overdue debts
- `POST /api/debts/{id}/payment` - Add payment to debt

### Repayments
- `GET /api/repayments` - List repayments
- `POST /api/repayments` - Create repayment
- `GET /api/repayments/farmer/{farmerId}` - Get farmer repayments
- `GET /api/repayments/statistics` - Repayment statistics

### Products
- `GET /api/products` - List products
- `POST /api/products` - Create product
- `GET /api/products/category/{categoryId}` - Get products by category
- `GET /api/products/active` - Get active products

### Categories
- `GET /api/categories` - List categories
- `POST /api/categories` - Create category
- `GET /api/categories/{id}/products` - Get category products
- `GET /api/categories/root/root` - Get root categories

## Models

### Core Models
- **Farmer**: Farmer profiles with credit management
- **Transaction**: Sales/purchase transactions
- **Debt**: Outstanding debt tracking
- **Repayment**: Debt repayment records
- **Product**: Product catalog items
- **Category**: Hierarchical product categories
- **User**: System users with roles

### Key Relationships
- Farmer has many Transactions, Debts, Repayments
- Transaction has many TransactionItems
- Category has hierarchical parent-child relationships
- Product belongs to Category

## Security

- API authentication via Laravel Sanctum tokens
- Role-based access control
- Input validation and sanitization
- SQL injection prevention through Eloquent ORM
- CORS configuration for cross-origin requests

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test --filter FarmerTest
php artisan test --filter TransactionTest
```

## Database Schema

The system uses the following main tables:
- `farmers` - Farmer information
- `transactions` - Transaction records
- `debts` - Debt tracking
- `repayments` - Repayment records
- `products` - Product catalog
- `categories` - Product categories
- `users` - System users

## Development

### Code Style
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write comprehensive tests for new features

### API Standards
- RESTful API design
- Consistent response format
- Proper HTTP status codes
- Error handling with meaningful messages

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
