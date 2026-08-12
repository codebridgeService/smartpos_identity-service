# SmartPOS Identity Service

[![Laravel Framework](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat&logo=php)](https://php.net)
[![JWT Auth](https://img.shields.io/badge/JWT--Auth-2.9-blue?style=flat)](https://github.com/PHP-Open-Source-Saver/jwt-auth)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-100%25%20Completed-brightgreen.svg)]()

**SmartPOS Identity Service** (`smartpos/identity-service`) is the core microservice handling authentication, authorization (RBAC), user session tracking, device security, and POS terminal PIN verification for the SmartPOS platform.

> 📖 **Architecture & System Design:** For in-depth architectural diagrams, database ERD schemas, threat modeling, and long-term technical roadmap, please read [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md).

---

## 🚀 Technologies & Stack

- **Framework:** Laravel 12/13 (PHP 8.3+)
- **Authentication:** JWT (`php-open-source-saver/jwt-auth`) & Sanctum
- **API Documentation:** Dedoc Scramble (OpenAPI / Swagger)
- **Database:** MySQL 8.4
- **Caching & Queue:** Redis 8
- **Containerization:** Docker & Docker Compose

---

## 📂 Service Structure

```text
identity-service/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ForgotPasswordController.php
│   │   │   ├── LoginAttemptController.php
│   │   │   ├── PermissionController.php
│   │   │   ├── RoleController.php
│   │   │   ├── UserController.php
│   │   │   ├── UserDeviceController.php
│   │   │   ├── UserPosPinController.php
│   │   │   ├── UserRoleController.php
│   │   │   └── UserSessionController.php
│   │   └── Middleware/
│   │       ├── CheckPermission.php
│   │       └── CheckRole.php
│   ├── Models/
│   └── Services/
│       └── RbacCacheService.php
├── database/
│   └── migrations/
├── docker/
├── routes/
│   ├── api.php
│   └── api/
│       ├── auth.php
│       ├── devices.php
│       ├── login_attempts.php
│       ├── permissions.php
│       ├── pos_pins.php
│       ├── roles.php
│       ├── sessions.php
│       ├── user_roles.php
│       └── users.php
├── compose.yaml
└── Dockerfile
```

---

## 🛠️ Features

### 🔐 Authentication & Password Recovery
- **User Registration & Login:** Multi-identifier authentication (email, phone, username) with JWT bearer token issuance.
- **Token Refresh & Revocation:** Secure JWT lifecycle management with Redis blacklist.
- **Forgot Password Workflow:** 3-step OTP flow (`send-code`, `verify-code`, `reset-password`).

### 🛡️ Role-Based Access Control (RBAC) & Caching
- **User Management:** Complete user account lifecycle (create, view, update, enable/disable, delete).
- **Roles & Permissions:** Dynamic role creation, permission module grouping, and bulk permission synchronization.
- **Redis RBAC Engine:** High-performance caching of roles and permissions in Redis with automatic cache invalidation.

### 💳 POS Terminal Security
- **Cashier Quick-PIN:** Dedicated hashed PIN creation, update, and quick cashier verification.
- **PIN Lockout:** Automatic account lockout after 5 failed PIN attempts for 15 minutes.

### 📱 Device & Session Management
- **Device Security:** Device registration, trust marking, and hardware blocking.
- **Session Control:** Active session tracking, remote session revocation, and multi-device logout.
- **Security Audit Logs:** Comprehensive logging for all login attempts (success, wrong password, blocked).

---

## 🌐 API Endpoints Reference

All API routes are prefixed with `/api/v1`.

| Module | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Auth** | `POST` | `/api/v1/auth/login` | Authenticate user (email/username/phone) & issue JWT |
| | `POST` | `/api/v1/auth/register` | Register new user account |
| | `POST` | `/api/v1/auth/refresh` | Refresh JWT access token |
| | `POST` | `/api/v1/auth/forgot-password/send-code` | Send OTP verification code |
| | `POST` | `/api/v1/auth/verify-reset-code` | Verify OTP code |
| | `POST` | `/api/v1/auth/reset-password` | Reset password using verified code |
| | `GET` | `/api/v1/auth/me` | Get authenticated user profile with permissions |
| | `POST` | `/api/v1/auth/logout` | Logout user & revoke session |
| **Users** | `GET` | `/api/v1/users` | List paginated users |
| | `POST` | `/api/v1/users` | Create user |
| | `GET` | `/api/v1/users/{uuid}` | View user details |
| | `PUT` | `/api/v1/users/{uuid}` | Update user details / status |
| | `DELETE` | `/api/v1/users/{uuid}` | Soft delete user |
| **Roles & Permissions** | `GET` | `/api/v1/roles` | List roles |
| | `POST` | `/api/v1/roles` | Create role |
| | `PUT` | `/api/v1/roles/{uuid}/permissions` | Bulk sync permissions to role |
| | `GET` | `/api/v1/permissions` | List permissions grouped by module |
| | `POST` | `/api/v1/permissions` | Create permissions in batch |
| | `POST` | `/api/v1/users/{uuid}/roles` | Assign role to user |
| | `DELETE` | `/api/v1/users/{uuid}/roles/{roleUuid}` | Revoke role from user |
| **POS PIN** | `PUT` | `/api/v1/users/{uuid}/pos-pin` | Update user POS PIN |
| | `POST` | `/api/v1/users/{uuid}/pos-pin/verify` | Verify POS PIN |
| **Devices** | `GET` | `/api/v1/devices` | List registered devices |
| | `PATCH` | `/api/v1/devices/{uuid}/trust` | Flag device as trusted |
| | `PATCH` | `/api/v1/devices/{uuid}/block` | Block device |
| **Sessions** | `GET` | `/api/v1/sessions` | List active sessions |
| | `DELETE` | `/api/v1/sessions/{uuid}` | Revoke single session |
| | `DELETE` | `/api/v1/sessions` | Revoke all active sessions |
| **Audit** | `GET` | `/api/v1/login-attempts` | View login audit log |
| **Health** | `GET` | `/up` | Health check endpoint |

---

## ⚡ Getting Started (Local Development)

### Prerequisites
- Docker & Docker Compose
- Composer & PHP 8.3+ (for local CLI runner)

### Setup Instructions

1. **Clone & Navigate:**
   ```bash
   cd identity-service
   ```

2. **Environment Configuration:**
   ```bash
   cp .env.example .env
   ```

3. **Start Docker Containers:**
   ```bash
   docker compose up -d
   ```
   - **App API:** `http://localhost:8001`
   - **MySQL 8.4:** `localhost:3307`
   - **Redis 8:** `localhost:6380`
   - **phpMyAdmin:** `http://localhost:8081`

4. **Initialize Application & Database:**
   ```bash
   composer setup
   # or manually:
   php artisan key:generate
   php artisan jwt:secret
   php artisan migrate
   ```

5. **Run Automated Test Suite:**
   ```bash
   php artisan test
   ```

---

## 📋 Task Backlog (100% Completed ✅)

### PHASE 1 — PROJECT FOUNDATION ✅
- [x] Create identity-service
- [x] Configure Docker (`Dockerfile`, `compose.yaml`)
- [x] Configure MySQL (`mysql:8.4`)
- [x] Configure Redis (`redis:8-alpine`)
- [x] Configure Laravel environment (`.env`, `.env.example`)
- [x] Configure API prefix `/api/v1`
- [x] Configure Scramble API docs (`dedoc/scramble`)
- [x] Configure JSON API responses
- [x] Configure exception/error responses
- [x] Configure UUID generation
- [x] Configure CORS
- [x] Configure rate limiting
- [x] Add `/health` endpoint (`/up`)

### PHASE 2 — USERS ✅
- [x] Create users migration
- [x] Add id, uuid, name, username, email, phone
- [x] Add password, avatar, status
- [x] Add email_verified_at
- [x] Add last_login_at, last_login_ip
- [x] Add timestamps and soft deletes
- [x] Create User model
- [x] Create UserController
- [x] Create UserResource / JSON Serializer
- [x] Create StoreUserRequest validation
- [x] Create UpdateUserRequest validation
- [x] Create user (`POST /api/v1/users`)
- [x] List users (`GET /api/v1/users`)
- [x] Get one user (`GET /api/v1/users/{uuid}`)
- [x] Update user (`PUT /api/v1/users/{uuid}`)
- [x] Disable user
- [x] Enable user
- [x] Delete user (`DELETE /api/v1/users/{uuid}`)

### PHASE 3 — ROLES ✅
- [x] Create roles migration
- [x] Create Role model
- [x] Create RoleController
- [x] Create validation
- [x] Create CRUD API
- [x] Protect system roles from unsafe deletion

### PHASE 4 — PERMISSIONS ✅
- [x] Create permissions migration
- [x] Create Permission model
- [x] Create PermissionController
- [x] Create CRUD API
- [x] Group permissions by module
- [x] Make permission code unique

### PHASE 5 — ROLE PERMISSIONS ✅
- [x] Create role_permissions migration
- [x] Add unique (`role_id`, `permission_id`)
- [x] Create model relationships
- [x] Assign permission to role
- [x] Remove permission from role
- [x] Bulk sync role permissions
- [x] Return role with permissions

### PHASE 6 — USER ROLES ✅
- [x] Create user_roles migration
- [x] Add unique (`user_id`, `role_id`)
- [x] Create model relationships
- [x] Assign role to user
- [x] Remove role from user
- [x] Sync user roles
- [x] Return effective permissions

### PHASE 7 — JWT AUTHENTICATION ✅
- [x] Install/configure JWT (`jwt-auth`)
- [x] Create AuthController
- [x] Login by email
- [x] Login by username
- [x] Login by phone
- [x] Validate password
- [x] Issue access token
- [x] Issue refresh token
- [x] Create `/auth/me`
- [x] Refresh access token
- [x] Logout
- [x] Reject disabled users
- [x] Add JWT middleware (`auth:api`)
- [x] Load roles and permissions
- [x] Include session UUID
- [x] Include device UUID where needed

### PHASE 8 — USER DEVICES ✅
- [x] Create migration and model (`UserDevice`)
- [x] Register/update device during login
- [x] Update `last_seen_at`
- [x] Store first/latest IP
- [x] Block/unblock device
- [x] Trust device
- [x] List user's devices
- [x] Revoke sessions by device

### PHASE 9 — USER SESSIONS ✅
- [x] Create migration and model (`UserSession`)
- [x] Create session during login
- [x] Generate and hash refresh secret
- [x] Validate refresh token
- [x] Rotate refresh token
- [x] Revoke session on logout
- [x] Revoke one session remotely
- [x] Revoke all sessions
- [x] Expire old sessions
- [x] Check device status before refresh
- [x] Update `last_activity_at`

### PHASE 10 — LOGIN ATTEMPTS ✅
- [x] Create migration and model (`LoginAttempt`)
- [x] Record successful login
- [x] Record wrong password
- [x] Record blocked user/device
- [x] Record unknown account
- [x] Add IP/login rate limiting
- [x] Admin can view attempts
- [x] Filter by user/IP/status/date

### PHASE 11 — AUTH OTP ✅
- [x] Create migration and model (`AuthOtp`)
- [x] Generate OTP
- [x] Hash OTP
- [x] Set expiration
- [x] Limit attempts
- [x] Send email OTP
- [x] Verify OTP
- [x] Mark OTP used
- [x] Prevent reuse
- [x] Add resend cooldown
- [x] Forgot-password flow
- [x] Reset-password flow

### PHASE 12 — USER POS PIN ✅
- [x] Create user_pos_pins migration
- [x] Create UserPosPin model
- [x] Set PIN
- [x] Change PIN
- [x] Reset PIN
- [x] Disable/enable PIN
- [x] Verify PIN
- [x] Increment failed attempts
- [x] Lock after repeated failures
- [x] Auto-unlock after configured period
- [x] Update `last_used_at`
- [x] Never return `pin_hash`
- [x] Rate-limit PIN verification

### PHASE 13 — PERMISSION MIDDLEWARE ✅
- [x] Create permission checker (`RbacCacheService`)
- [x] Create permission middleware (`CheckPermission`)
- [x] Return 403 when denied
- [x] Add permissions to `/auth/me`
- [x] Support multiple roles
- [x] Remove duplicate permissions
- [x] Add automated tests (`RbacMiddlewareTest`)

### PHASE 14 — MICROSERVICE AUTHENTICATION ✅
- [x] Decide JWT signing strategy
- [x] Include `sub` = user UUID
- [x] Include session UUID
- [x] Include expiration
- [x] Include issuer
- [x] Include audience if needed
- [x] Add public-key/JWKS endpoint if using asymmetric signing
- [x] Product Service verifies tokens
- [x] POS Service verifies tokens
- [x] Sales Service verifies tokens
- [x] Reject invalid/expired tokens
- [x] Define session revocation strategy

### PHASE 15 — SECURITY ✅
- [x] Password hashing
- [x] POS PIN hashing
- [x] OTP hashing
- [x] Refresh secret hashing
- [x] Login rate limiting
- [x] OTP rate limiting
- [x] PIN rate limiting
- [x] User disable
- [x] Device blocking
- [x] Session revocation
- [x] Password reset security
- [x] Validate UUID parameters
- [x] Hide sensitive fields
- [x] Audit security events
- [x] Protect admin-only endpoints

### PHASE 16 — TESTING ✅
- **Authentication:**
  - [x] Login success
  - [x] Wrong password
  - [x] Disabled user
  - [x] Blocked device
  - [x] Refresh token
  - [x] Logout
  - [x] `/auth/me`
- **Permissions:**
  - [x] Allowed
  - [x] Denied
  - [x] Multiple roles
- **OTP:**
  - [x] Generate
  - [x] Invalid
  - [x] Expired
  - [x] Reused
  - [x] Too many attempts
  - [x] Resend cooldown
- **POS PIN:**
  - [x] Valid PIN
  - [x] Wrong PIN
  - [x] Locked PIN
  - [x] Disabled PIN
  - [x] Reset PIN
  - [x] Too many failures
- **Sessions:**
  - [x] Create
  - [x] Refresh
  - [x] Rotate refresh token
  - [x] Revoke one
  - [x] Revoke all
  - [x] Expired session

### PHASE 17 — API DOCUMENTATION ✅
- **Document with Scramble:**
  - [x] Authentication
  - [x] Users
  - [x] Roles
  - [x] Permissions
  - [x] Role Permissions
  - [x] User Roles
  - [x] Sessions
  - [x] Devices
  - [x] OTP / Password Reset
  - [x] POS PIN
  - [x] Login Attempts
- **For every endpoint document:**
  - [x] Request
  - [x] Success response
  - [x] Validation errors
  - [x] 401
  - [x] 403
  - [x] 404
  - [x] 422
  - [x] 429

---

## ⚖️ Service Boundary

### Identity Service OWNS:
- `users`
- `roles`
- `permissions`
- `role_permissions`
- `user_roles`
- `user_sessions`
- `user_devices`
- `login_attempts`
- `auth_otps`
- `user_pos_pins`

### Identity Service DOES NOT OWN:
- `businesses`, `business_users`, `outlets` (Owned by Business Service)
- `registers`, `pos_devices`, `shifts` (Owned by POS Service)
- `products`, `inventory`, `orders`, `payments` (Owned by Product/Sales Services)

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).
