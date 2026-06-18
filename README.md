# SkyBook | Global Flight Reservation & Transaction System

SkyBook is a production-hardened monolithic flight booking, dynamic capacity allocation, and fleet management system engineered using **Object-Oriented PHP, MySQL (MySQLi), and Bootstrap 5**. The architecture is built with a strict defensive security posture, systematically neutralizing modern application threat vectors while coordinating complex multi-row relational mutations.


Created By:
Swayam Bhise<br>
Atharva Dubal
Shruti Jadhav
Nidhee Dhulap
Karishma Chauhan

---

## 🚀 Featured Engineering Highlight: `process_payment.php`

Instead of basic CRUD, the platform’s core capabilities are represented inside a single high-integrity execution file: **`process_payment.php`**. This controller acts as the primary transaction engine and demonstrates production-grade system design:

* **ACID-Compliant Atomic Transactions (`begin_transaction()`):** Coordinates parent ledger generation (`bookings`), dynamic child arrays (`booking_seats`), and fleet metrics (`flights`) inside an atomic database block. Any state execution interruption triggers an immediate `rollback()`, preventing orphaned rows or database corruption.
* **Asynchronous Race-Condition Resolution:** Mitigates real-time double-booking anomalies by running server-side checkpoint constraints immediately before database insertion, safely isolating simultaneous seat-allocation requests under heavy concurrent loads.
* **SQL Injection (SQLi) Defense Matrix:** Every inbound data stream is routed through type-casting gates and explicit **Parameterized Prepared Statements**, completely isolating user-supplied string literals from query execution blocks.

---

## 🔐 Core Security & Business Architecture

* **One-Way Cryptographic Salting:** Plaintext credentials are completely eliminated; user records are processed using the computational complexity of the **BCRYPT** algorithm via native `password_hash()` and `password_verify()` verification handling loops.
* **Role-Based Access Control Gates :** Hardened session filters intercept panel routing strings across backend admin suites (`admin_dashboard.php`, `admin_manifest.php`), strictly requiring `$_SESSION['role'] === 'admin'` to block privilege escalation or IDOR vulnerabilities.
* **Context-Aware Encoding :** Dynamic string entities (such as names, contact info, and special meal preferences) are sanitized using `htmlspecialchars()` boundaries before generation to block Reflected/Stored XSS vectors.
* **Decoupled Environment Abstraction:** Core infrastructure parameter keys are isolated inside a protected root `.env` container, keeping database parameters decoupled from public version control histories via `.gitignore`.
* **Dynamic Inventory Auto-Restoration:** The cancellation architecture ensures that when a reservation is terminated, the system accurately updates and releases the corresponding passenger seat capacity count back to the flight line availability pool automatically.



## 🏗️ System Architecture & Data Flow

The platform follows a classic **Layered Monolithic Architecture** with strict decoupled boundaries between presentation templates, session access controllers, and the underlying relational database transaction boundaries.

```text
 ┌────────────────────────────────────────────────────────────────────────┐
 │                           PRESENTATION LAYER                           │
 │  [ Passenger Portal Interface ]      │     [ Admin Command Dashboard ] │
 └───────────────────────────────────┬──┴─────────────────────────────────┘
                                     │
                                     ▼
 ┌────────────────────────────────────────────────────────────────────────┐
 │                       SESSION ACCESS CONTROL GATE                      │
 │  • Enforces state verification rules to mitigate IDOR vectors          │
 │  • Enforces RBAC checks (Requires $_SESSION['role'] === 'admin')       │
 └───────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
 ┌────────────────────────────────────────────────────────────────────────┐
 │                       BUSINESS LOGIC MIDDLEWARE                        │
 │  • Parameter type-casting and Sanitization Matrix                      │
 │  • Strict SQL Parameterization via Prepared Bindings                   │
 └───────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
 ┌────────────────────────────────────────────────────────────────────────┐
 │                    INFRASTRUCTURE PROTECTION LAYER                    │
 │  • Runtime Abstraction Engine parsing restricted .env parameters       │
 └───────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
 ┌────────────────────────────────────────────────────────────────────────┐
 │                           DATA STORAGE LAYER                           │
 │  • MySQL Database (InnoDB Engine enforcing strict ACID Compliance)    │
 └────────────────────────────────────────────────────────────────────────┘
