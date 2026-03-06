# APIFAST - Services API

Backend API built with Laravel 12 for managing services with OTP authentication and Trusted Device support.

## Prerequisites

- PHP ^8.2
- SQLite (or your preferred database)
- Composer

## Installation

1. Clone the repository.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Setup environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the server:
   ```bash
   php artisan serve
   ```

## Authentication Flow

1. **Login**: POST `/api/login` with `email` and `password`.
   - Returns a success message and sends an OTP to your email.
2. **Verify OTP**: POST `/api/verify-otp` with `email`, `otp`, and optional `remember` (boolean).
   - If `remember` is true, the device is registered as trusted for 30 days.
   - Returns a Sanctum `access_token`.

## API Endpoints

### Services CRUD (Protected by Sanctum)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/services` | List all services for the authenticated user. |
| POST | `/api/services` | Create a new service. Requires `name`, `description`, and `foto_persona` (Base64). |
| GET | `/api/services/{id}` | Show details of a specific service. |
| PUT/PATCH | `/api/services/{id}`| Update a service. |
| DELETE | `/api/services/{id}` | Soft delete a service. |

## Custom Validation

The project includes a custom validation rule `base64image` to ensure that the `foto_persona` field contains a valid Base64 encoded image.
