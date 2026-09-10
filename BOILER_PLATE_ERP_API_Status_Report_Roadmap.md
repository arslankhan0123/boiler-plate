# BOILER PLATE ERP
## API Status Report & Future Roadmap
**Base URL:** `http://127.0.0.1:8000/api/v1`

---

### 1. Authentication Module
#### Available APIs
* **POST** `/auth/login` — Working Fine
* **POST** `/auth/login/verify-otp` — Working Fine
* **POST** `/auth/forgot-password` — Working Fine
* **POST** `/auth/reset-password` — Working Fine

#### Suggestions & Enhancements
* **IP-Based Brute Force Protection:** Track login failures by IP address and temporarily lock or throttle authentication requests after repeated failures.
* **OAuth Social Login Integration:** Support logging in via third-party providers (Google, Apple, Microsoft) for seamless enterprise onboarding.
* **Security Notifications:** Send automated emails or push notifications when login occurs from an unrecognized device or geographic location.

#### Roadmap Status
* **✅ Multi-Factor Authentication (MFA) — Completed**
  * Email OTP verification has been fully integrated into the authentication flow.
  * If `is_verification_required` is enabled for a user, an OTP is automatically generated during login.
  * Access tokens are issued only after successful OTP verification through the `/auth/login/verify-otp` endpoint.
* **🟡 Login Activity Tracking — Partially Completed**
  * Background request logging is operational and captures login-related activities.
  * User-facing login history screens and dedicated endpoints are still pending.
* **⏳ Refresh Token API — Pending**
  * Laravel Passport custom refresh token implementation has not yet been completed.

---

### 2. User Session Module
#### Available APIs
* **GET** `/auth/me` — Working Fine
* **POST** `/auth/logout` — Working Fine

#### Suggestions & Enhancements
* **Dynamic Token Lifespans:** Allow tenant admins to configure session lifetime policies (e.g., shorter timeouts for cashiers, longer for admins).
* **Device & Location Enrichment:** Resolve client IP addresses to geographic locations and browser metadata to show user-friendly session listings.
* **Concurrent Session Limiting:** Add configuration to restrict a single user account to only one active login session, auto-logging out older sessions.

#### Roadmap Status
* **✅ User Activity Logs — Completed**
  * The `RecordActivity` background job automatically captures:
    * IP Address
    * Endpoint Accessed
    * Response Status
    * Request Duration
    * User Agent
  * All activity records are stored in the `activity_logs` table.
* **🟡 Logout From All Devices — Partially Completed**
  * Password reset automatically revokes all active user tokens.
  * A dedicated "Logout From All Devices" endpoint is still pending.
* **⏳ Active Sessions Management — Pending**
  * Session listing and management dashboard has not yet been implemented.

---

### 3. Setup Module (General & Core Masters)
#### Available APIs

##### 🌐 Countries Management
* **GET** `/countries` — Working Fine
* **GET** `/countries/{id}` — Working Fine
* **POST** `/countries` — Working Fine
* **PUT** `/countries` — Working Fine
* **DELETE** `/countries` — Working Fine
* **DELETE** `/countries/force` — Working Fine
* **POST** `/countries/import` — Working Fine
* **GET** `/countries/export` — Working Fine
* **GET** `/countries/import-sample` — Working Fine

##### 🔍 Lookups
* **GET** `/lookups` — Working Fine
* **GET** `/lookups/{id}` — Working Fine

##### 🏛️ States Management
* **GET** `/states` — Working Fine
* **GET** `/states/{id}` — Working Fine
* **POST** `/states` — Working Fine
* **PUT** `/states` — Working Fine
* **DELETE** `/states` — Working Fine
* **DELETE** `/states/force` — Working Fine
* **POST** `/states/import` — Working Fine
* **GET** `/states/export` — Working Fine
* **GET** `/states/import-sample` — Working Fine

##### 🏙️ Cities Management
* **GET** `/cities` — Working Fine
* **GET** `/cities/{id}` — Working Fine
* **POST** `/cities` — Working Fine
* **PUT** `/cities` — Working Fine
* **DELETE** `/cities` — Working Fine
* **DELETE** `/cities/force` — Working Fine
* **POST** `/cities/import` — Working Fine
* **GET** `/cities/export` — Working Fine
* **GET** `/cities/import-sample` — Working Fine

##### 📍 Areas Management
* **GET** `/areas` — Working Fine
* **GET** `/areas/{id}` — Working Fine
* **POST** `/areas` — Working Fine
* **PUT** `/areas` — Working Fine
* **DELETE** `/areas` — Working Fine
* **DELETE** `/areas/force` — Working Fine
* **POST** `/areas/import` — Working Fine
* **GET** `/areas/export` — Working Fine
* **GET** `/areas/import-sample` — Working Fine

##### 💵 Currency Management
* **GET** `/currencies` — Working Fine
* **GET** `/currencies/{id}` — Working Fine
* **POST** `/currencies` — Working Fine
* **PUT** `/currencies` — Working Fine
* **DELETE** `/currencies` — Working Fine
* **DELETE** `/currencies/force` — Working Fine
* **POST** `/currencies/import` — Working Fine
* **GET** `/currencies/export` — Working Fine
* **GET** `/currencies/import-sample` — Working Fine

#### Suggestions & Enhancements
* **Global Location Caching:** Countries, states, and cities changes are rare. Implementing high-performance Redis caching will speed up dropdown requests.
* **Automatic Exchange Rates Updates:** Integrate external exchange rate APIs (e.g., Open Exchange Rates) to automatically update conversion rates daily.
* **Geocoding Support:** Store latitude/longitude configurations on areas and cities to enable spatial mapping for logistics and delivery zones.

---

### 4. Setup Module (HR & Organization Masters)
#### Available APIs

##### 🏢 Departments Management
* **GET** `/departments` — Working Fine
* **GET** `/departments/{id}` — Working Fine
* **POST** `/departments` — Working Fine
* **PUT** `/departments` — Working Fine
* **DELETE** `/departments` — Working Fine
* **DELETE** `/departments/force` — Working Fine
* **POST** `/departments/import` — Working Fine
* **GET** `/departments/export` — Working Fine
* **GET** `/departments/import-sample` — Working Fine

##### 🎓 Designations Management
* **GET** `/designations` — Working Fine
* **GET** `/designations/{id}` — Working Fine
* **POST** `/designations` — Working Fine
* **PUT** `/designations` — Working Fine
* **DELETE** `/designations` — Working Fine
* **DELETE** `/designations/force` — Working Fine
* **POST** `/designations/import` — Working Fine
* **GET** `/designations/export` — Working Fine
* **GET** `/designations/import-sample` — Working Fine

##### 👥 Groups Management
* **GET** `/groups` — Working Fine
* **GET** `/groups/{id}` — Working Fine
* **POST** `/groups` — Working Fine
* **PUT** `/groups` — Working Fine
* **DELETE** `/groups` — Working Fine
* **DELETE** `/groups/force` — Working Fine
* **POST** `/groups/import` — Working Fine
* **GET** `/groups/export` — Working Fine
* **GET** `/groups/import-sample` — Working Fine

##### 👤 Employees Management
* **GET** `/employees` — Working Fine
* **GET** `/employees/{id}` — Working Fine
* **POST** `/employees` — Working Fine
* **PUT** `/employees` — Working Fine
* **DELETE** `/employees` — Working Fine
* **DELETE** `/employees/force` — Working Fine
* **POST** `/employees/import` — Working Fine
* **GET** `/employees/export` — Working Fine
* **GET** `/employees/import-sample` — Working Fine

#### Suggestions & Enhancements
* **Hierarchical Structure (Org Chart):** Add a parent-child mapping on departments to automatically construct organization trees.
* **Employee Status Logs:** Implement tracking of designation, department, and salary movements over time for each employee.
* **Document Depository:** Provide secure, encrypted file uploads for employee identification docs, contracts, and certifications.
* **Shift Scheduling Mapping:** Integrate shifts to employee groups for automated roster assignments.

---

### 5. Setup Module (Contact Masters)
#### Available APIs

##### 📦 Suppliers Management
* **GET** `/suppliers` — Working Fine
* **GET** `/suppliers/{id}` — Working Fine
* **POST** `/suppliers` — Working Fine
* **PUT** `/suppliers` — Working Fine
* **DELETE** `/suppliers` — Working Fine
* **DELETE** `/suppliers/force` — Working Fine
* **POST** `/suppliers/import` — Working Fine
* **GET** `/suppliers/export` — Working Fine
* **GET** `/suppliers/import-sample` — Working Fine

##### 👥 Customers Management
* **GET** `/customers` — Working Fine
* **GET** `/customers/{id}` — Working Fine
* **POST** `/customers` — Working Fine
* **PUT** `/customers` — Working Fine
* **DELETE** `/customers` — Working Fine
* **DELETE** `/customers/force` — Working Fine
* **POST** `/customers/import` — Working Fine
* **GET** `/customers/export` — Working Fine
* **GET** `/customers/import-sample` — Working Fine

#### Suggestions & Enhancements
* **Credit Limits and Controls:** Define credit limits and payment terms (e.g., Net 30) for both suppliers and customers. Restrict transactions automatically if rules are broken.
* **Multiple Address Directories:** Support multiple shipping, billing, and warehouse locations per customer/supplier.
* **Communication History logs:** Log transactional emails, invoices, and payment receipts sent to contacts directly under their master profiles.

#### Setup Module Roadmap Status
* **✅ Completed Features**
  * Core Geographical Lookups (Countries, States, Cities, Areas, Currencies).
  * Organization Setup (Departments, Designations, Groups, Employees).
  * Trading Contacts Setup (Suppliers, Customers).
* **⏳ Pending Features**
  * Tax Classes Management.
  * Business Settings Module.

---

### 6. Admin – Tenants Module
#### Available APIs
* **GET** `/admin/tenants` — Working Fine
* **POST** `/admin/tenants` — Working Fine
* **POST** `/admin/tenants/{tenant}/users` — Working Fine

#### Suggestions & Enhancements
* **Queue-Based Tenant Provisioning:** Asynchronously run database setup scripts, seeders, and configurations using Laravel Queues to prevent API timeouts during tenant creation.
* **Usage Dashboard:** Introduce metrics tracking tenant CPU usage, storage utilization, and active connections.
* **Custom Domain Mapping:** Allow white-labeled subdomains and custom apex domains mapping per tenant.

#### Roadmap Status
* **⏳ Pending Features**
  * Tenant Suspension & Reactivation.
  * Tenant Database Backup Management.
  * Usage Analytics & Reporting.

---

### 7. Roles & Permissions Module
#### Available APIs
* **GET** `/roles` — Working Fine
* **GET** `/roles/{role}` — Working Fine
* **POST** `/roles` — Working Fine
* **PUT** `/roles/{role}` — Working Fine
* **DELETE** `/roles/{role}` — Working Fine
* **PUT** `/roles/{role}/permissions` — Working Fine

##### Permissions
* **GET** `/permissions` — Working Fine
* **GET** `/permissions/{permission}` — Working Fine
* **POST** `/permissions` — Working Fine
* **PUT** `/permissions/{permission}` — Working Fine
* **DELETE** `/permissions/{permission}` — Working Fine

##### User Role Assignment
* **GET** `/users/{user}/roles` — Working Fine
* **PUT** `/users/{user}/roles` — Working Fine

##### Column Visibility Management
* **GET** `/column-catalog` — Working Fine
* **GET** `/roles/{role}/column-visibility` — Working Fine
* **PUT** `/roles/{role}/column-visibility` — Working Fine

#### Suggestions & Enhancements
* **Hierarchical Role Inheritance:** Allow roles to inherit permissions (e.g., "Store Manager" automatically inherits permissions from "Sales Associate").
* **Field-level Access Control (Column Security):** Enforce data masking/filtering in controllers based on column visibility permissions (e.g., cashier cannot see profit margins).
* **Action-Scope Definitions:** Define permission scopes (e.g., View "Own Data", View "Branch Data", or View "Corporate Data").

#### Roadmap Status
* **✅ Audit Logs — Completed**
  * Dynamic audit tracking has been implemented through the `Auditable` trait.
  * All Create, Update, and Delete operations are recorded.
  * Before and after values are stored in the `audit_trails` table.
* **✅ Role Change History — Completed**
  * Role and permission changes automatically generate audit events.
  * Complete modification history is maintained in the audit trail system.
* **⏳ Permission Groups — Pending**
  * Permission categorization and grouping functionality is planned for future releases.

---

### 8. Accounting Module
#### Available APIs
* **GET** `/accounting` — Working Fine

#### Suggestions & Enhancements
* **Strict Double-Entry Validation:** Enforce checks ensuring total debit equals total credit at controller-level before saving journal logs.
* **Dynamic Chart of Accounts:** Make the account tree flexible with customizable parent-child ledger structures.
* **Tax Code Mapping:** Allow automatic general ledger tax distribution mappings for sales and purchase entries.

#### Roadmap Status
* **⏳ Pending Features**
  * Create / Update / Delete APIs.
  * Chart of Accounts.
  * General Ledger.
  * Trial Balance.
  * Balance Sheet.
  * Profit & Loss Reports.

---

### 9. Inventory Module
#### Available APIs
* **GET** `/inventory` — Working Fine

#### Suggestions & Enhancements
* **Valuation Systems support:** Add configuration support for FIFO (First In First Out), LIFO, and Weighted Average costing models.
* **Barcode Handling Optimization:** Optimize search and detail endpoints to quickly fetch items when scanned via POS or handheld warehouse terminals.
* **Inventory Reconciliation Workflow:** Introduce APIs for periodic stock counting, capturing variance adjustments automatically.

#### Roadmap Status
* **⏳ Pending Features**
  * Product Management.
  * Inventory CRUD APIs.
  * Stock Adjustments.
  * Warehouse Management.
  * Stock Transfers.
  * Low Stock Alerts.

---

### 10. POS Module
#### Available APIs
* **GET** `/pos` — Working Fine

#### Suggestions & Enhancements
* **Offline Synchronization Cache:** Build the API to support delta syncs from locally cached sales tickets on POS terminals that were processed offline.
* **Split Payments Logic:** Allow split payments (e.g., Cash + Gift Card) with partial refunds tracking.
* **Cash Drawer Tracking:** Force cash drop records and drawer closing logs at session close to track variances.

#### Roadmap Status
* **⏳ Pending Features**
  * Register Session Management.
  * Sales Checkout Process.
  * Receipt Generation.
  * Suspended Sales.
  * Cash Drawer Management.

---

### 11. Purchase Module
#### Available APIs
* **GET** `/purchase` — Working Fine

#### Suggestions & Enhancements
* **3-Way Matching:** Design verification workflows matching Purchase Order, Goods Receipt (GRN), and Invoice billing amounts.
* **Supplier Lead Time Monitoring:** Log scheduled versus actual shipment arrivals to calculate performance scores.

#### Roadmap Status
* **⏳ Pending Features**
  * Supplier Management *(Core master setup complete under Setup module)*.
  * Purchase Orders.
  * Goods Receipt Notes (GRN).
  * Purchase Billing.
  * Vendor Payments.

---

### 12. Sales Module
#### Available APIs
* **GET** `/sale` — Working Fine

#### Suggestions & Enhancements
* **Customer Scheme Rules:** Implement pricing matrices and volume-based discounts that apply automatically.
* **Commission Calculations:** Track sales agent IDs on quotations/invoices to auto-calculate agent commission rates.

#### Roadmap Status
* **⏳ Pending Features**
  * Customer Management *(Core master setup complete under Setup module)*.
  * Sales Quotations.
  * Sales Invoices.
  * Returns Management.
  * Customer Credit Tracking.

---

### Project Summary
#### Fully Implemented Modules
* Authentication Module
* User Session Module
* Setup Module *(Countries, Lookups, States, Cities, Areas, Currencies, Departments, Designations, Groups, Employees, Suppliers, Customers)*
* Admin Tenants Core Module
* Roles & Permissions Module

#### Major Enhancements Delivered
* Email OTP Based MFA Authentication
* User Activity Logging System
* Audit Trail Management
* Role Change Tracking
* Extended Setup and Organization Masters (Departments, Designations, Groups, Employees, Suppliers, and Customers)

#### Modules Under Development
* Accounting
* Inventory
* POS
* Purchase
* Sales

#### Overall Status
The foundation, security, and directory/master setup modules of **Boiler Plate ERP** have been successfully implemented and are fully operational. Core operational business flows including POS transactions, Accounting ledgers, Inventory, Purchase, and Sales pipelines are currently in the roadmap phase.
