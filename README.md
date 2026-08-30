# Smart Healthcare & Diagnostic Management System

> **A Comprehensive Multi-Tier Healthcare & Diagnostic Platform**  
> *Web Application (PHP / MySQL / JS) + Desktop Utility Services (C++ / VB.NET)*

---

## 📋 Executive Overview & Objectives

### 1. Problem Statement
Traditional healthcare management in small-to-medium clinics and diagnostic centers often suffers from fragmented workflows:
- Patients experience long wait times, inefficient appointment scheduling, and difficulty identifying the right specialist for their symptoms.
- Doctors face disorganized appointment lists without clear triage or emergency prioritization.
- Administrative staff struggle with billing errors, delayed session revocations for suspended accounts, and lack of real-time operational metrics.

### 2. Project Objectives
The **Smart Healthcare & Diagnostic Management System** was engineered as an integrated, multi-role web platform and desktop suite to streamline patient triage, appointment scheduling, doctor consultation workflows, and clinic financial management:
1. **Intelligent Patient Triage**: Provide an automated symptom-diagnosis engine that evaluates symptoms, calculates condition severity, provides emergency advice, and recommends appropriate medical specialists.
2. **Conflict-Free Scheduling**: Enforce strict scheduling integrity including doctor working hours, double-booking prevention, and mandatory 5-minute buffers between consecutive bookings.
3. **Clinical Workflow Efficiency**: Empower doctors with prioritized appointment queues (emergency-first), time-validated appointment completion controls, and direct bill generation.
4. **Administrative Governance & Security**: Protect sensitive health records using CSRF defense, password hashing, activity audit logging, and **instant + real-time 20-second background polling session termination** for suspended accounts.
5. **Multi-Platform Utility**: Complement the web portal with standalone C++ and VB.NET desktop billing utilities for offline cashier terminals.

---

## 🛠️ Complete Technology Stack

| Layer | Technology | Usage / Purpose |
| :--- | :--- | :--- |
| **Backend Web Server** | **PHP 8.x** | Core business logic, session handling, database access, security controls |
| **Database** | **MySQL 8.0+ / MariaDB** | Relational data persistence, indexed queries, relational integrity |
| **Frontend UI/UX** | **HTML5, Vanilla CSS3, JavaScript (ES6+)** | Responsive layouts, CSS custom properties, glassmorphism, dynamic DOM |
| **Interactive Analytics** | **Chart.js (CDN)** | Visual charts for admin dashboard (appointments by status, billing metrics) |
| **Desktop Module (CLI)** | **C++17** | High-speed standalone terminal billing calculator (`patient_billing.cpp`) |
| **Desktop Module (GUI)** | **VB.NET / .NET 8.0** | Standalone Windows desktop GUI billing application (`HospitalBilling`) |
| **Environment Server** | **XAMPP / WAMP / LAMP** | Apache HTTP Server + MySQL database environment |

---

## 📁 System Architecture & Directory Structure

```
Smart Healthcare & Diagnostic Management System/
│
├── README.md                           # Comprehensive project documentation
│
├── healthcare/                         # Main Web Application Directory
│   ├── db.php                          # MySQL connection, session setup, CSRF & active session suspension check
│   ├── check-session-status.php        # Lightweight JSON API endpoint for 20-second real-time session polling
│   ├── footer.php                      # Shared HTML footer containing the 20s real-time suspension polling JS
│   ├── index.php                       # Public landing page with features, FAQ, & floating chat widget
│   ├── login.php                       # User authentication login page with CSRF & remember-me token handling
│   ├── register.php                    # Patient registration form with client/server validation
│   ├── logout.php                      # Secure session termination & cookie revocation
│   ├── forgot-password.php             # Password recovery request page
│   ├── reset-password.php              # Password reset handler via secure token
│   ├── dashboard.php                   # Unified role-based dashboard (Patient, Doctor, Admin views)
│   ├── book-appointment.php            # Multi-step symptom diagnosis, triage, doctor selection & booking
│   ├── check-availability.php          # Real-time AJAX availability checker (5-min gap & working hours)
│   ├── appointment-result.php          # Detailed diagnosis summary, recommended doctor & booking confirmation
│   ├── my-appointments.php             # Patient appointment portal (view, cancel, reschedule with 1-hr rule)
│   ├── reschedule-appointment.php      # Appointment rescheduling handler with 1-hour notice guard
│   ├── cancel-appointment.php          # Appointment cancellation processor
│   ├── doctor-appointments.php         # Doctor workspace (emergency triage list, completion guard, bill trigger)
│   ├── doctor-schedule.php            # Doctor availability schedule setup
│   ├── doctor-profile.php             # Doctor profile, specialization, address, consultation fee management
│   ├── billing.php                     # Invoice generation & bill processing with insurance discount calculation
│   ├── my-bills.php                    # Patient billing history portal
│   ├── view-receipt.php                # Printable/downloadable itemized PDF receipt view
│   ├── admin-panel.php                 # Admin management hub (users, appointments, doctors, warnings)
│   ├── analytics.php                   # Interactive reporting dashboard with Chart.js statistics
│   ├── activity-log.php                # System audit log viewer for security and user tracking
│   ├── style.css                       # Modern global CSS stylesheet (design system, utilities, responsive layout)
│   └── schema.sql                      # Complete MySQL database schema & sample seed data
│
├── cpp_billing/                        # Standalone C++ Billing Utility
│   ├── patient_billing.cpp             # C++ source code for terminal invoice calculation
│   └── patient_billing.exe             # Compiled Windows C++ executable
│
└── vb_billing/                         # Standalone VB.NET Desktop GUI Utility
    ├── HospitalBilling.sln             # Visual Studio solution file
    └── HospitalBilling/                # VB.NET Project source files
        ├── Form1.vb                    # Main GUI logic & calculation event handlers
        ├── Form1.Designer.vb           # Form visual layout definition
        └── HospitalBilling.vbproj      # .NET project configuration file
```

---

## 🚀 Complete Installation & Setup Guide

### 1. Prerequisites
- **XAMPP Server** (Apache + MySQL) installed on Windows/Linux/macOS.
- **PHP 8.0 or higher**.
- **MySQL 8.0+ / MariaDB 10.4+**.
- **C++ Compiler** (`g++`, `clang++`, or MinGW) for compiling the C++ module.
- **.NET 8.0 SDK** or **Visual Studio 2022** for running the VB.NET application.

---

### 2. Web Application Setup (XAMPP)

1. **Copy Project Files**:
   - Copy the `healthcare/` directory into your XAMPP web root:
     - **Windows**: `C:\xampp\htdocs\healthcare`
     - **macOS**: `/Applications/XAMPP/htdocs/healthcare`
     - **Linux**: `/opt/lampp/htdocs/healthcare`

2. **Start XAMPP Control Panel**:
   - Launch XAMPP Control Panel.
   - Start **Apache** and **MySQL** modules.

3. **Import Database Schema**:
   - Open your web browser and navigate to `http://localhost/phpmyadmin/`.
   - Click **New** on the left sidebar to create a database.
   - Name the database `healthcare_system` (Collation: `utf8mb4_unicode_ci`) and click **Create**.
   - Select the newly created `healthcare_system` database, go to the **Import** tab.
   - Choose the file `healthcare/schema.sql` from the project directory.
   - Click **Import** at the bottom.

4. **Verify Database Configuration**:
   - Inspect `healthcare/db.php`. Ensure database connection credentials match your environment:
     ```php
     $host     = "localhost";
     $username = "root";
     $password = "";
     $database = "healthcare_system";
     ```

5. **Launch Application**:
   - Open browser and navigate to: `http://localhost/healthcare/`

---

### 3. Demo / Evaluation Account Creation Guide

For evaluation purposes, default seed accounts exist in `schema.sql`, or you can register/create test accounts safely:

#### Default Test Credentials (from `schema.sql`):
- **Admin**: `admin@healthcare.com` / Password: `password123`
- **Doctor**: `dr.smith@healthcare.com` / Password: `password123`
- **Patient**: `john.doe@example.com` / Password: `password123`

#### Safe Admin Account Creation (if needed):
To promote an existing registered user to **Admin** status safely:
1. Register a new user on `http://localhost/healthcare/register.php`.
2. Open `phpMyAdmin` -> database `healthcare_system` -> table `users`.
3. Locate the newly registered user row and update the `role` column from `'patient'` to `'admin'`.

---

### 4. Compiling & Executing C++ Desktop Billing Utility

The C++ module is a lightweight standalone billing utility located in `cpp_billing/`.

#### Using MinGW / GCC (`g++`):
```bash
cd "cpp_billing"
g++ -O2 patient_billing.cpp -o patient_billing.exe
./patient_billing.exe
```

#### Running Pre-compiled Executable (Windows):
```cmd
cd cpp_billing
patient_billing.exe
```

---

### 5. Executing VB.NET Desktop Billing Utility

The VB.NET module is a Windows desktop GUI application located in `vb_billing/`.

#### Using .NET CLI (`dotnet run`):
```bash
cd "vb_billing/HospitalBilling"
dotnet run
```

#### Using Visual Studio:
1. Open `vb_billing/HospitalBilling.sln` in Visual Studio 2022.
2. Select **Build > Build Solution** (`Ctrl+Shift+B`).
3. Press **F5** or click **Start** to launch the GUI application.

---

## 🌟 Comprehensive Feature List

### 🔐 1. Authentication & Security Layer
- **Multi-Role User System**: Role-based access control supporting `patient`, `doctor`, and `admin`.
- **Password Security**: Passwords hashed using standard `password_verify()` and `password_hash()` (bcrypt).
- **CSRF Defense**: Cryptographic CSRF tokens generated per session and validated on every POST request via `verifyCsrfToken()`.
- **Persistent Remember-Me**: 30-day token-based persistent authentication stored securely in `remember_tokens` table.
- **Account Recovery**: Tokenized password reset via `forgot-password.php` and `reset-password.php`.
- **Immediate & Real-time Session Suspension**:
  - **Navigation Guard**: Synchronous checks in `db.php` on every page load instantly revoke suspended sessions.
  - **Background Polling Guard**: Lightweight 20-second JavaScript polling via `check-session-status.php` terminates active sessions in real-time even if the user remains idle on a page.

---

### 🩺 2. AI-Assisted Symptom Diagnosis & Triage Engine
- **Flexible Symptom Input**: Dual-mode input via structured checkboxes and natural language free-text parsing.
- **NLP Keyword Matching**: Evaluates free-text input against medical keyword vectors (e.g. *fever, chest pain, shortness of breath, headache, nausea*).
- **Weighted Triage Algorithm**: Calculates condition severity (*Mild*, *Moderate*, *Severe*) and diagnosis confidence.
- **Duration-Based Medical Advice**: Generates tailored clinical recommendations based on symptom duration.
- **Emergency First-Aid Guidance**: Displays prominent emergency first-aid protocols and red-flag warnings when critical symptoms (e.g. chest pain, severe dyspnea) are detected.
- **Medical Disclaimer**: Clear regulatory disclaimer clarifying that recommendations are computer-assisted and do not replace professional medical judgment.

---

### 🗓️ 3. Smart Doctor Triage & Appointment Scheduling
- **Specialty Matching**: Automatically recommends doctors matching the diagnosed condition.
- **5-Minute Slot Buffer Rule**: Prevents scheduling appointments within 5 minutes of existing doctor appointments to guarantee buffer time.
- **Working Hours Enforcement**: Validates that requested appointment times fall within the doctor's set working hours (`available_from` to `available_to`).
- **Real-Time Availability API**: Asynchronous AJAX validation (`check-availability.php`) provides live feedback and suggests the next available slot when conflicts occur.
- **Double-Booking Prevention**: Database-level and server-side checks prevent overlapping bookings.

---

### 📅 4. Patient Appointment Management
- **Appointment Dashboard**: View upcoming, completed, and cancelled appointments with status indicators.
- **Appointment Cancellation**: One-click cancellation with confirmation prompts.
- **Reschedule Notice Rule**: Enforces a minimum 1-hour notice period before the scheduled appointment time. Rescheduling requests within 1 hour are blocked with clear UI feedback.

---

### 👨‍⚕️ 5. Doctor Clinical Workspace
- **Emergency-Prioritized Queue**: Appointments listed in `doctor-appointments.php` are sorted by severity (*Emergency/Severe* first).
- **Time-Validated Completion Guard**: "Mark as Completed" button is strictly protected; doctors cannot mark future appointments completed until the scheduled time has elapsed.
- **Direct Invoice Trigger**: Doctors can launch bill generation directly upon completing an appointment.
- **Schedule & Profile Management**: Doctors can set consultation fees, working hours, clinic address, and specialization via `doctor-profile.php`.

---

### 💳 6. Billing & Financial Management
- **Automated Invoice Calculation**: Automatically calculates consultation fees, lab diagnostic charges, treatment fees, and tax.
- **Insurance Discount Engine**: Applies insurance coverage percentage discounts dynamically.
- **Payment Status Tracking**: Maintains clear `Paid` and `Unpaid` invoice statuses.
- **Patient Bill Portal**: Patients can view itemized billing history in `my-bills.php`.
- **Printable PDF Receipts**: Standardized, clean receipt layout in `view-receipt.php` ready for printing or saving as PDF.

---

### 🛡️ 7. Administrative Governance & Analytics
- **User Status Control**: Toggle user status (`active` vs `suspended`) with immediate session revocation.
- **Warning System**: Send administrative warning messages to users stored in database logs.
- **Immutable Activity Audit Log**: Tracks all critical user actions (logins, bookings, status changes, bill creation) in `activity_log`.
- **Interactive Analytics Dashboard**: Graphical representation of system metrics using Chart.js in `analytics.php` (appointment status distribution, revenue trends, user demographics).
- **Complete Data Management Panel**: Comprehensive administrative search, filter, and control interface in `admin-panel.php`.

---

### 🎨 8. UI/UX & Design System
- **Modern Glassmorphism Interface**: Clean color palette, modern typography (*Fredoka*, *Nunito*), and custom CSS variable tokens.
- **Interactive Landing Page**: Feature showcases, statistics overview, and floating expandable FAQ/Chat widget.
- **Responsive Layout**: Designed for seamless accessibility across desktop, tablet, and mobile displays.

---

## 📊 Feature Summary Matrix (For Project Reports)

| Feature Module | Key File(s) | Technical Description |
| :--- | :--- | :--- |
| **Authentication & Security** | `login.php`, `register.php`, `db.php`, `forgot-password.php` | Multi-role auth, password hashing, CSRF validation, 30-day remember-me tokens. |
| **Real-time Session Termination** | `db.php`, `check-session-status.php`, `footer.php` | Dual-layer suspension guard: synchronous page-load checks + 20s background polling. |
| **Symptom Diagnosis & Triage** | `book-appointment.php`, `appointment-result.php` | Keyword matching, weighted scoring algorithm, emergency advice & specialty matching. |
| **Appointment Scheduling** | `book-appointment.php`, `check-availability.php` | 5-minute buffer enforcement, working hours check, real-time availability AJAX API. |
| **Appointment Management** | `my-appointments.php`, `reschedule-appointment.php` | Patient appointment listing, cancellation, and 1-hour minimum notice reschedule guard. |
| **Doctor Clinical Workspace** | `doctor-appointments.php`, `doctor-profile.php` | Emergency-first queue sorting, time-validated completion guard, bill trigger. |
| **Billing & Invoicing** | `billing.php`, `my-bills.php`, `view-receipt.php` | Auto-calculated invoice, insurance discount engine, payment tracking, PDF receipts. |
| **Admin Analytics & Logs** | `admin-panel.php`, `analytics.php`, `activity-log.php` | User management, warning dispatch, audit log tracking, Chart.js analytics dashboard. |
| **Desktop Utilities** | `cpp_billing/patient_billing.cpp`, `vb_billing/HospitalBilling/` | High-speed C++ terminal calculator & standalone VB.NET desktop GUI billing app. |

---

## 📸 Recommended Screenshots for Final Documentation Report

When compiling the final report document, capturing the following screenshots is recommended:

1. **Public Landing Page** (`index.php`): Showcase hero banner, statistics cards, and floating FAQ widget.
2. **User Login Page** (`login.php`): Display clean single-card authentication layout and password toggle.
3. **Patient Symptom Diagnosis** (`book-appointment.php` Step 1): Show symptom checkboxes and free-text NLP input.
4. **Diagnosis Result & Triage** (`appointment-result.php`): Display severity rating, medical advice, and recommended doctor.
5. **Real-time Availability Checker** (`book-appointment.php` Step 2): Demonstrate live buffer/working hour warning.
6. **Patient Appointments Portal** (`my-appointments.php`): Show appointment status badges and disabled reschedule button (1-hour rule).
7. **Doctor Workspace** (`doctor-appointments.php`): Illustrate emergency-prioritized queue and "Mark as Completed" button.
8. **Billing Invoice Generator** (`billing.php`): Show live bill calculation and insurance discount breakdown.
9. **Printable PDF Receipt** (`view-receipt.php`): Display professional itemized invoice layout.
10. **Admin Panel & Suspensions** (`admin-panel.php`): Showcase user table, status toggles, and warning modal.
11. **System Analytics Dashboard** (`analytics.php`): Capture Chart.js visual graphs and metrics summary.
12. **C++ & VB.NET Desktop Apps**: Capture CLI output of C++ billing program and GUI layout of VB.NET application.

---

## ⚠️ Known Limitations & Medical Disclaimer

### Known Technical Limitations
1. **SMS / Email Gateway Integration**: Password resets and notification alerts currently output via on-screen tokens and activity logs; integration with external SMTP/Twilio gateways requires API credentials.
2. **Local Client Time Sync**: The system relies on server timezone settings (`Asia/Karachi`); client devices with incorrectly set system clocks should refer to server-rendered timestamps.

### Medical Safety Disclaimer
> **IMPORTANT**: The Smart Healthcare & Diagnostic Management System is designed as a computer-assisted decision support and administrative tool. It provides automated triage and informational guidance based on user-entered symptoms. **It does not provide definitive medical diagnoses or replace professional clinical judgment.** In cases of severe, acute, or life-threatening symptoms, users must immediately seek emergency medical care at the nearest hospital.

---

*Submitted as a complete, fully-functional final software deliverable.*
