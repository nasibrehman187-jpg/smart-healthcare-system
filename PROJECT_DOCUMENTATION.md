# Smart Healthcare & Diagnostic Management System
## Final Project Submission & Technical Report Documentation

---

## 📌 Section 1: Project Objectives & System Overview

### 1. Problem Statement
In traditional healthcare management environments, small-to-medium clinics and diagnostic centers face significant operational challenges:
- **Disorganized Patient Triage**: Patients often struggle to evaluate the urgency of their symptoms or identify the correct specialist, leading to delayed treatment or inappropriate appointments.
- **Scheduling Conflicts & Buffer Collisions**: Manual booking systems frequently suffer from double-booking, lack of buffer time between appointments, and bookings outside a physician's working hours.
- **Workflow Inefficiencies for Clinicians**: Doctors lack emergency-prioritized appointment views, making it difficult to attend to urgent cases first. Additionally, premature marking of future appointments as completed distorts clinical records.
- **Administrative & Security Vulnerabilities**: Traditional web portals often fail to immediately revoke access for suspended accounts, allowing active sessions to persist until manual logout.
- **Billing Overhead**: Manual invoicing and insurance discount calculations lead to accounting errors and delayed receipt generation.

### 2. Proposed Solution & Core System Goals
The **Smart Healthcare & Diagnostic Management System** addresses these issues through a multi-tiered web portal (PHP/MySQL) accompanied by standalone desktop billing tools (C++ & VB.NET):

1. **Intelligent Computer-Assisted Triage**: An automated symptom assessment engine evaluates symptom checkboxes and free-text inputs using weighted scoring to calculate condition severity, provide emergency advice, and recommend appropriate medical specialists.
2. **Strict Scheduling Integrity**: Algorithms enforce doctor working hours, double-booking prevention, and a mandatory 5-minute buffer between consecutive appointments, backed by a real-time AJAX availability checker.
3. **Optimized Doctor Workflows**: Appointments are automatically prioritized by emergency status. Doctor controls prevent marking future appointments completed before the scheduled time has elapsed.
4. **Multi-Layer Session Security**: A dual-layer session revocation engine combines synchronous page-load checks with a 20-second background polling API to immediately terminate active sessions when an admin suspends a user.
5. **Automated Financial Management**: Invoices are computed automatically with configurable insurance discounts, generating downloadable/printable PDF-styled receipts.
6. **Multi-Platform Support**: Complemented by lightweight standalone C++ (CLI) and VB.NET (GUI) billing applications for offline reception counters.

---

## 📊 Section 2: Feature Summary Matrix

The table below summarizes all implemented modules, associated files, and technical descriptions suitable for inclusion in technical submission documentation.

| Feature Module | Key File(s) | Technical Description |
| :--- | :--- | :--- |
| **Authentication & Session Management** | `login.php`, `register.php`, `logout.php`, `db.php` | Multi-role authentication (`patient`, `doctor`, `admin`), bcrypt password hashing (`password_hash`), cryptographic CSRF defense (`generateCsrfToken`), and 30-day persistent `remember_me` tokens. |
| **Account Recovery** | `forgot-password.php`, `reset-password.php` | Secure tokenized password recovery workflow with expiration tracking. |
| **Real-time Session Suspension** | `db.php`, `check-session-status.php`, `footer.php` | Dual-layer suspension enforcement: page-navigation checks in `db.php` + 20-second background JS polling API (`check-session-status.php`) for immediate session revocation. |
| **Symptom Diagnosis & Triage Engine** | `book-appointment.php`, `appointment-result.php` | Dual symptom selection (checkboxes + free-text NLP keyword parsing), weighted severity scoring (*Mild*, *Moderate*, *Severe*), emergency first-aid advice, and medical disclaimer. |
| **Doctor Recommendation System** | `appointment-result.php`, `book-appointment.php` | Specialty mapping matching diagnosed conditions with available clinic doctors. |
| **Conflict-Free Scheduling & Availability API** | `book-appointment.php`, `check-availability.php` | Enforcement of doctor working hours, double-booking prevention, 5-minute appointment slot buffer rule, and dynamic real-time AJAX availability checking. |
| **Patient Appointment Management** | `my-appointments.php`, `reschedule-appointment.php`, `cancel-appointment.php` | Appointment listing, cancellation, and rescheduling with mandatory 1-hour minimum notice period guard. |
| **Doctor Workspace & Triage Queue** | `doctor-appointments.php`, `doctor-profile.php` | Emergency-prioritized appointment list, time-validated "Mark as Completed" guard, direct invoice trigger, and doctor schedule/profile configuration. |
| **Billing & Financial Management** | `billing.php`, `my-bills.php`, `view-receipt.php` | Auto-calculated invoices (fees + diagnostics + tax), insurance percentage discount calculator, payment status tracking (`Paid`/`Unpaid`), and printable PDF receipts. |
| **Admin Panel & Security Audit Logs** | `admin-panel.php`, `activity-log.php` | User status management (active/suspended), administrative warning dispatch, and immutable activity logging (`logActivity()`). |
| **Analytics Dashboard** | `analytics.php` | Interactive visual analytics rendered using Chart.js (revenue metrics, appointment status breakdown, user role demographics). |
| **UI/UX & Landing Page** | `index.php`, `style.css` | Public landing page, modern CSS design system (glassmorphism, CSS variables), and interactive floating FAQ/Chat widget. |
| **C++ Desktop Utility** | `cpp_billing/patient_billing.cpp` | High-speed terminal invoice calculator written in C++17. |
| **VB.NET Desktop GUI Application** | `vb_billing/HospitalBilling/Form1.vb` | Standalone Windows desktop GUI billing application written in VB.NET / .NET 8.0. |

---

## 📸 Section 3: Recommended Screenshots Checklist for Final Report

Use this checklist when capturing screenshots for your written report or presentation slides:

- [ ] **1. Public Landing Page (`index.php`)**: Hero section, statistics counter, system features, and floating FAQ chat widget.
- [ ] **2. Authentication Screens (`login.php` & `register.php`)**: User registration form and clean single-card login page with password toggle.
- [ ] **3. Patient Dashboard (`dashboard.php`)**: Patient overview showing total appointments, quick links, and status metrics.
- [ ] **4. Symptom Assessment Form (`book-appointment.php` Step 1)**: Interactive checkbox list and free-text symptom description box.
- [ ] **5. Diagnosis Summary & Triage Output (`appointment-result.php`)**: Calculated severity level, clinical advice, emergency warnings, and recommended specialist doctor.
- [ ] **6. Real-Time Slot Availability Checker (`book-appointment.php` Step 2)**: Dynamic AJAX warning demonstrating doctor working-hour or 5-minute buffer enforcement.
- [ ] **7. Patient Appointment Management (`my-appointments.php`)**: List of upcoming appointments showing cancellation button and disabled reschedule button (1-hour notice rule).
- [ ] **8. Doctor Workspace (`doctor-appointments.php`)**: Emergency-prioritized appointment queue and time-protected "Mark as Completed" action.
- [ ] **9. Billing Generator (`billing.php`)**: Invoice creation form displaying consultation fees, test charges, tax, and insurance discount calculation.
- [ ] **10. Printable Receipt (`view-receipt.php`)**: Standardized itemized PDF-style bill layout ready for printing.
- [ ] **11. Admin Governance Panel (`admin-panel.php`)**: User management table, active/suspended status toggles, and warning modal.
- [ ] **12. System Analytics Dashboard (`analytics.php`)**: Visual Chart.js graphs displaying revenue trends and appointment metrics.
- [ ] **13. Audit Activity Log (`activity-log.php`)**: Timestamped security audit trail tracking user actions.
- [ ] **14. C++ Standalone Utility Execution (`patient_billing.cpp`)**: Command-prompt output demonstrating standalone C++ billing calculation.
- [ ] **15. VB.NET Standalone Desktop GUI App (`HospitalBilling`)**: Form window showing VB.NET GUI billing calculator.

---

## ⚡ Section 4: System Setup & Execution Summary

### 1. Database Setup
Import `healthcare/schema.sql` into MySQL via phpMyAdmin or MySQL CLI:
```sql
CREATE DATABASE healthcare_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthcare_system;
SOURCE /path/to/healthcare/schema.sql;
```

### 2. Web Portal Launch
Place `healthcare/` directory into Apache web root (`C:\xampp\htdocs\healthcare`) and access:
`http://localhost/healthcare/`

### 3. C++ Application Compilation
```bash
cd cpp_billing
g++ -O2 patient_billing.cpp -o patient_billing.exe
./patient_billing.exe
```

### 4. VB.NET Application Launch
```bash
cd vb_billing/HospitalBilling
dotnet run
```

---

## ⚖️ Section 5: Medical Disclaimer

> **NOTICE**: The Smart Healthcare & Diagnostic Management System is designed solely for administrative management and computer-assisted decision support. The automated symptom assessment engine offers general triage recommendations and does not constitute a formal clinical diagnosis. Patients experiencing severe, acute, or emergency medical symptoms should immediately contact local emergency medical services or visit the nearest hospital emergency department.
