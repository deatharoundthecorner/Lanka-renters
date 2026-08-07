# Lanka Renters Role Permissions & Access Control

This document details the backend access authorization rules and URL route protections.

---

## Access Permissions Matrix

| Section / Folder | customer | owner | driver | admin | guest |
| :--- | :---: | :---: | :---: | :---: | :---: |
| `public/` (root) | Yes | Yes | Yes | Yes | Yes |
| `public/customer/` | **Yes** | No | No | No | No |
| `public/owner/` | No | **Yes** | No | No | No |
| `public/driver/` | No | No | **Yes** | No | No |
| `public/admin/` | No | No | No | **Yes** | No |

---

## Enforcement Implementation

Authorization checks are implemented at the entry point of each view directory using the centralized `AuthHelper`:

### 1. Customer Portal Views (`public/customer/`)
Injected in header components:
```php
AuthHelper::requireRole('customer');
```

### 2. Vehicle Owner Portal Views (`public/owner/`)
Injected in header components:
```php
AuthHelper::requireRole('owner');
```

### 3. Driver Portal Views (`public/driver/`)
Injected at the top of each view wrapper:
```php
AuthHelper::requireRole('driver');
```

### 4. Admin Portal Views (`public/admin/`)
Injected in the shared layout layout wrapper:
```php
AuthHelper::requireRole('admin');
```
If an unauthenticated request attempts to load a protected route, they are automatically signed out (if another role is active) and redirected to `login.php`.
