# Ecommerce API

A RESTful e-commerce backend API built with **Laravel 11**, showcasing real-world backend engineering practices including authentication, role-based access control, service layer architecture, database transactions, and automated testing.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| Database | MySQL 8 |
| Cache / Session | Redis |
| Authentication | Laravel Sanctum (Token-based) |
| API Documentation | Swagger UI (l5-swagger) |
| Testing | PHPUnit + Laravel HTTP Tests |
| Containerization | Docker Compose |

## Features

- **User Authentication** — Register, login, logout via Sanctum tokens
- **Role-Based Access Control** — `customer` and `admin` roles with middleware protection
- **Product Management** — CRUD with search, pagination, and stock tracking
- **Category Management** — Hierarchical categories with parent/child support
- **Shopping Cart** — Persistent DB-backed cart per user
- **Order & Checkout** — Cart-to-order with DB Transaction and stock decrement
- **Order State Machine** — Enforced transitions: `pending → paid → shipped → delivered / cancelled`
- **Swagger UI** — Interactive API documentation at `/api/documentation`
- **26 Feature Tests** — Full coverage of auth, products, cart, and order flows

## Getting Started

### Prerequisites

- Docker Desktop
- Git

### Installation

```bash
git clone <your-repo-url> ecommerce-api
cd ecommerce-api
cp .env.example .env
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

The API will be available at **http://localhost:8000**

Swagger UI: **http://localhost:8000/api/documentation**

### Running Tests

```bash
docker-compose exec app php artisan test
```

## API Endpoints

### Auth
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | - | Register new user |
| POST | `/api/auth/login` | - | Login, returns token |
| POST | `/api/auth/logout` | ✓ | Revoke token |
| GET | `/api/auth/me` | ✓ | Get current user |

### Products
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/products` | - | List products (search + paginate) |
| GET | `/api/products/{id}` | - | Get single product |
| POST | `/api/products` | Admin | Create product |
| PUT | `/api/products/{id}` | Admin | Update product |
| DELETE | `/api/products/{id}` | Admin | Delete product |

### Cart
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/cart` | ✓ | View cart |
| POST | `/api/cart/items` | ✓ | Add item |
| PUT | `/api/cart/items/{id}` | ✓ | Update quantity |
| DELETE | `/api/cart/items/{id}` | ✓ | Remove item |
| DELETE | `/api/cart` | ✓ | Clear cart |

### Orders
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/orders` | ✓ | Checkout (cart → order) |
| GET | `/api/orders` | ✓ | List my orders |
| GET | `/api/orders/{id}` | ✓ | Get order detail |
| PATCH | `/api/orders/{id}/status` | Admin | Update order status |

## Default Seeded Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Customer | user@example.com | password |

## Architecture Highlights

### Service Layer
Business logic is encapsulated in `CartService` and `OrderService`, keeping controllers thin and focused on HTTP concerns.

### DB Transaction in Checkout
The checkout process uses `DB::transaction()` with `lockForUpdate()` to prevent race conditions when multiple requests try to purchase the last item simultaneously.

### Order State Machine
The `Order::canTransitionTo()` method enforces valid status transitions, preventing invalid state changes like jumping from `pending` directly to `delivered`.

### Form Requests
All input validation is handled via dedicated `FormRequest` classes, keeping validation logic separate from controllers.

## Project Structure

```
app/
  Http/
    Controllers/Api/    # AuthController, ProductController, CartController, OrderController
    Middleware/         # IsAdmin
    Requests/           # Form validation (Auth, Product)
  Models/               # User, Category, Product, Cart, CartItem, Order, OrderItem
  Services/             # CartService, OrderService
database/
  migrations/           # Sequential numbered migrations
  seeders/              # DatabaseSeeder with test data
routes/
  api.php               # All API routes
tests/
  Feature/              # AuthTest, ProductTest, OrderTest (26 tests)
docker/
  php/Dockerfile
  nginx/default.conf
```
