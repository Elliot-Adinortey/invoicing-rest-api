# 🚀 Laravel Invoicing REST API

## 📌 Overview

This project is a **production-ready RESTful API** for invoice management, built with the Laravel framework.

It demonstrates real-world backend engineering practices including:

* Clean architecture (Controller → Service → Resource)
* Business rule enforcement
* Inventory tracking with overselling prevention
* Secure authentication
* Transactional data integrity

The system is designed to reflect how invoicing works in **real business environments**, not just basic CRUD operations.

---

## ✨ Features

### 🔐 Authentication

* User registration & login
* Token-based authentication via Laravel Sanctum
* Protected API routes

### 👥 Customer Management

* Create, update, delete, and list customers
* Soft deletes for data safety

### 📦 Product & Inventory

* Product creation with stock tracking
* Prevents overselling using database-level locking
* Stock movement audit trail

### 🧾 Invoice Management

* Create invoices with one or more items
* Automatic total calculation
* Invoice status lifecycle:

  ```txt
  draft → issued → paid → cancelled
  ```
* Business rules enforcement (e.g. cannot delete paid invoices)

### ⚙️ System Design Highlights

* Transaction-safe operations using database transactions
* Row-level locking (`lockForUpdate`) for inventory consistency
* API versioning (`/api/v1`)
* Pagination & filtering support
* Structured API responses via Resources

---

## 🏗️ Tech Stack

* PHP 8+
* Laravel
* Laravel Sanctum
* MySQL / PostgreSQL
* RESTful API architecture

---

## ⚡ Installation

```bash
git clone <your-repo-url>
cd invoicing-api

composer install

cp .env.example .env
php artisan key:generate

# Configure database in .env

php artisan migrate:fresh --seed

php artisan serve
```

---

## 🔑 Test Credentials

```txt
Email: test@example.com
Password: password123
```

---

## 🔐 Authentication

Login to get a token:

```http
POST /api/v1/login
```

Use token for requests:

```txt
Authorization: Bearer YOUR_TOKEN
```

---

## 📡 API Endpoints

### Auth

```txt
POST /api/v1/register
POST /api/v1/login
POST /api/v1/logout
```

---

### Customers

```txt
GET    /api/v1/customers
POST   /api/v1/customers
GET    /api/v1/customers/{id}
PUT    /api/v1/customers/{id}
DELETE /api/v1/customers/{id}
```

---

### Products

```txt
GET    /api/v1/products
POST   /api/v1/products
GET    /api/v1/products/{id}
PUT    /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

---

### Invoices

```txt
GET    /api/v1/invoices
POST   /api/v1/invoices
GET    /api/v1/invoices/{id}
DELETE /api/v1/invoices/{id}
POST   /api/v1/invoices/{id}/mark-paid
```

---

## 🧪 Sample Requests

### Create Invoice

```http
POST /api/v1/invoices
Authorization: Bearer TOKEN
```

```json
{
  "customer_id": 1,
  "issue_date": "2026-04-30",
  "due_date": "2026-05-15",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

---

## 🧠 Business Rules Implemented

* An invoice **must belong to a customer**
* An invoice must contain **at least one item**
* Each item must reference a valid product
* Product stock is validated before invoice creation
* System prevents selling more than available stock
* Product stock is automatically reduced after invoice creation
* Paid invoices cannot be deleted or modified
* Due date must be **after or equal to issue date**

---

## 🔄 Inventory Tracking

Each stock change is recorded in a **stock movements table**, providing:

* Full audit trail
* Historical tracking
* Debugging capability for stock issues

Example:

```txt
Product: Laptop
Before: 10
Sold: 2
After: 8
```

---

## 🔒 Data Integrity & Concurrency

To ensure consistency:

* All invoice operations run inside **database transactions**
* Product rows are locked using:

```php
lockForUpdate()
```

This prevents race conditions in high-concurrency environments.

---

## 📊 Pagination & Filtering

```http
GET /api/v1/invoices?status=issued&page=1
```

---

## 🧪 Running Tests

```bash
php artisan test
```

### Covered Scenarios

* User authentication (register/login)
* Protected route access
* Customer and product creation
* Invoice creation with validation
* Stock validation and deduction
* Prevention of overselling

---

## 🧩 Project Structure

```txt
app/
 ├── Http/
 │   ├── Controllers/
 │   ├── Requests/
 │   └── Resources/
 ├── Models/
 └── Services/
```

### Key Design Decisions

* **Service Layer** handles business logic (InvoiceService)
* **Form Requests** handle validation
* **Resources** standardize API responses
* **Models** focus on relationships and data

---

## 🧠 Design Philosophy

This project prioritizes:

* Clarity over complexity
* Real-world business logic over simple CRUD
* Data integrity over shortcuts
* Maintainability and scalability

---

## 🚀 Future Improvements

* Payment integration
* Invoice PDF generation
* Email notifications
* Multi-currency support
* Role-based access control (RBAC)

---

## 👨‍💻 Author

Developed as part of a backend engineering assessment to demonstrate:

* API design
* System thinking
* Clean code practices
* Real-world problem solving

---

## 📜 License

This project is for technical evaluation purposes.
