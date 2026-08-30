# Smart Healthcare & Diagnostic Management System
# Comprehensive A-to-Z Visual Guide & Architectural Walkthrough

> **Companion Submission Document & User Interface Catalog**  
> *A Complete Visual and Technical Reference for Evaluators, Clinicians, and System Administrators*  
> *All exhibits captured directly from the live application environment (`/screenshots/`)*

---

## 📑 Complete Catalog of Visual Exhibits

| Section | Figure # | Exhibit Name | Image File Path |
| :--- | :---: | :--- | :--- |
| **Public & Gateway** | **01a** | Public Landing Page — Hero & Live Metrics | `screenshots/01a-landing-page-hero.png` |
| | **01b** | Public Landing Page — Interactive FAQ Widget | `screenshots/01b-landing-page-faq-expanded.png` |
| **Registration** | **02a** | User Registration — Blank Form State | `screenshots/02a-register-empty.png` |
| | **02b** | User Registration — Patient Account Form | `screenshots/02b-register-patient-filled.png` |
| | **02c** | User Registration — Doctor Practice Setup | `screenshots/02c-register-doctor-filled.png` |
| | **02d** | User Registration — Validation Error State | `screenshots/02d-register-validation-error.png` |
| **Authentication** | **03a** | User Authentication Portal (Default) | `screenshots/03a-login-default.png` |
| | **03b** | Authentication Portal — Password Revealed | `screenshots/03b-login-password-revealed.png` |
| | **03c** | Authentication Portal — Invalid Error Banner | `screenshots/03c-login-invalid-error.png` |
| | **03d** | Authentication Portal — Account Suspended Alert | `screenshots/03d-login-suspended-alert.png` |
| **Account Recovery** | **04a** | Password Recovery Request Form | `screenshots/04a-forgot-password-default.png` |
| | **04b** | Password Recovery — Token Generated Confirmation | `screenshots/04b-forgot-password-token-generated.png` |
| | **05a** | Tokenized Password Reset Interface | `screenshots/05a-reset-password-form.png` |
| **Patient Workflows** | **06a** | Patient Primary Dashboard & Health Metrics | `screenshots/06a-patient-dashboard.png` |
| | **07a** | Symptom Assessment Matrix (Step 1 - Empty) | `screenshots/07a-symptom-assessment-empty.png` |
| | **07b** | Symptom Assessment Matrix (Step 1 - Populated) | `screenshots/07b-symptom-assessment-filled.png` |
| | **08a** | Preliminary Diagnosis Result (Normal Condition) | `screenshots/08a-diagnosis-normal-result.png` |
| | **08b** | Acute Emergency Diagnosis & First-Aid Protocol | `screenshots/08b-diagnosis-emergency-result.png` |
| | **10a** | Appointment Booking — Doctor Selection (Step 2) | `screenshots/10a-appointment-step2-doctor-select.png` |
| | **10b** | Appointment Booking — Time Slot Picker | `screenshots/10b-appointment-step2-time-picker.png` |
| | **11a** | Availability Engine — Working Hours Conflict | `screenshots/11a-appointment-conflict-working-hours.png` |
| | **11b** | Availability Engine — 5-Minute Buffer Warning | `screenshots/11b-appointment-conflict-5min-buffer.png` |
| | **12a** | Patient Appointments Management Portal | `screenshots/12a-my-appointments-list.png` |
| | **13a** | Appointment Rescheduling Form | `screenshots/13a-reschedule-appointment-form.png` |
| | **13b** | Rescheduling Notice Rule Enforcement (Disabled) | `screenshots/13b-reschedule-appointment-disabled.png` |
| | **14a** | Appointment Cancellation Confirmation Modal | `screenshots/14a-cancel-appointment-modal.png` |
| | **20a** | Patient Financial Portal & Invoices History | `screenshots/20a-my-bills-list.png` |
| | **21a** | Standardized Itemized Medical Receipt (`PAID`) | `screenshots/21a-printable-receipt-paid.png` |
| **Doctor Workflows** | **15a** | Doctor Clinical Dashboard & Daily Summary | `screenshots/15a-doctor-dashboard.png` |
| | **16a** | Doctor Appointment Queue (Emergency Priority) | `screenshots/16a-doctor-appointments-queue.png` |
| | **16b** | Doctor Queue 30-Second Refresh Indicator | `screenshots/16b-doctor-appointments-refresh-indicator.png` |
| | **17a** | Time-Validated Appointment Completion Guard | `screenshots/17a-doctor-completion-time-guard.png` |
| | **18a** | Doctor Profile & Practice Schedule Configuration | `screenshots/18a-doctor-profile-form.png` |
| | **19a** | Medical Billing Generator (Initial State) | `screenshots/19a-billing-generation-empty.png` |
| | **19b** | Medical Billing Generator (Live Calculated) | `screenshots/19b-billing-generation-calculated.png` |
| **Admin Governance** | **22a** | Administrator Command Dashboard | `screenshots/22a-admin-dashboard.png` |
| | **23a** | Admin Management Hub — All Users Table | `screenshots/23a-admin-panel-users-tab.png` |
| | **23b** | Admin Management Hub — Instant Search Filter | `screenshots/23b-admin-panel-search-filtered.png` |
| | **23c** | Admin Management Hub — Patients Demographics Tab | `screenshots/23c-admin-panel-patients-tab.png` |
| | **23d** | Admin Management Hub — Registered Doctors Tab | `screenshots/23d-admin-panel-doctors-tab.png` |
| | **23e** | Admin Management Hub — Global Appointments Tab | `screenshots/23e-admin-panel-appointments-tab.png` |
| | **24a** | User Account Suspension & Session Revocation | `screenshots/24a-admin-suspend-user-action.png` |
| | **25a** | Administrative Warning Dispatch Modal | `screenshots/25a-admin-warning-modal.png` |
| | **26a** | Immutable System Security Audit Activity Log | `screenshots/26a-activity-log-table.png` |
| | **27a** | System Operational Analytics Dashboard (Chart.js)| `screenshots/27a-analytics-dashboard-charts.png` |
| **Desktop Modules** | **28a** | Standalone C++ Terminal Billing Menu | `screenshots/28a-cpp-billing-menu.png` |
| | **28b** | Standalone C++ Calculated Terminal Receipt | `screenshots/28b-cpp-billing-calculated-receipt.png` |
| | **29a** | Standalone VB.NET Desktop GUI Hospital Billing | `screenshots/29a-vb-billing-form.png` |

---

## 🖼️ Detailed Annotations & Architectural Walkthrough

---

### Figure 1a & 1b: Public Landing Page & Interactive FAQ Gateway

```
[ Screenshot Paths: screenshots/01a-landing-page-hero.png | screenshots/01b-landing-page-faq-expanded.png ]
```

**Figure 1a & 1b: Public Landing Page (`index.php`)**
- 🔺 **Brand Header & Navigation Bar** — Displays system logo, navigation links (`Home`, `Features`, `About`, `FAQ`), and direct action buttons (`Login`, `Register`).
- 🔺 **Hero Call-to-Action Banner** — Introduces automated symptom diagnosis and smart specialist booking with a high-visibility "Get Started" launcher.
- 🔺 **Live System Metrics Counter** — Displays real-time clinic statistics (e.g. Registered Specialists, Consultations Completed, Satisfied Patients).
- 🔺 **Core Capability Feature Cards** — Summarizes system modules: AI Symptom Triage, Conflict-Free Scheduling, Real-Time Invoicing, and Secure Health Records.
- 🔺 **Interactive Floating FAQ Widget** — Expandable bottom-right assistant providing instant answers to common patient questions regarding clinic timings and emergency care.

**Technical & Architectural Explanation:**  
The landing page serves as the public entry point. It uses clean semantic HTML5 and modern CSS custom properties without third-party framework bloat. The floating FAQ widget uses an event-driven toggle listener that smoothly expands accordion cards without causing layout reflows, guiding unauthenticated visitors directly into registration or appointment booking.

---

### Figure 2a, 2b, 2c & 2d: Dynamic User Registration System

```
[ Screenshot Paths: 
  screenshots/02a-register-empty.png 
  screenshots/02b-register-patient-filled.png 
  screenshots/02c-register-doctor-filled.png 
  screenshots/02d-register-validation-error.png ]
```

**Figure 2a–2d: User Registration (`register.php`)**
- 🔺 **Dynamic Role Switcher Dropdown** — Switches registration mode between `Patient` and `Doctor`, dynamically toggling required fields in the DOM.
- 🔺 **Patient Demographics Group (Figure 2b)** — Captures patient-specific records: Age (1–120), Body Weight (kg), Pakistani CNIC, and Health Insurance Policy Number.
- 🔺 **Doctor Practice Setup Group (Figure 2c)** — Captures clinical settings: Medical Specialization, Clinic Room Address, City, Consultation Fee (PKR), and Daily Working Hours.
- 🔺 **Real-Time Validation Engine (Figure 2d)** — Validates password complexity, CNIC formatting, and field completeness, rendering clear red alerts on input errors.

**Technical & Architectural Explanation:**  
`register.php` consolidates multi-role onboarding into a unified, secure endpoint. When a role is selected, a client-side listener toggles visibility and HTML5 `required` attributes. Upon form submission, the server executes a MySQL transaction: it encrypts the password with `password_hash(..., PASSWORD_DEFAULT)` (bcrypt), creates a record in `users`, and inserts the corresponding profile into either `patients` or `doctors`, guaranteeing strict relational integrity.

---

### Figure 3a, 3b, 3c & 3d: Secure User Authentication & Login Portal

```
[ Screenshot Paths: 
  screenshots/03a-login-default.png 
  screenshots/03b-login-password-revealed.png 
  screenshots/03c-login-invalid-error.png 
  screenshots/03d-login-suspended-alert.png ]
```

**Figure 3a–3d: User Login Portal (`login.php`)**
- 🔺 **Credential Input Form (Figure 3a)** — Accepts registered Email and Password with CSRF hidden token verification.
- 🔺 **Interactive Password Toggle Icon (Figure 3b)** — Switches password input between `type="password"` and `type="text"` (`👁️/🙈`) without losing input focus.
- 🔺 **Persistent Remember Me Checkbox** — Issues an encrypted 30-day token cookie stored in `remember_tokens` for secure return authentication.
- 🔺 **Authentication Error Alert (Figure 3c)** — Displays generic error banner (`"Invalid email or password."`) preventing account enumeration attacks.
- 🔺 **Account Suspension Alert (Figure 3d)** — Displays immediate suspension notification on `login.php?suspended=1` when an admin terminates an account.

**Technical & Architectural Explanation:**  
`login.php` implements defense-in-depth security. Every form render embeds a cryptographic token verified with `verifyCsrfToken()`. Password verification uses `password_verify()`. If valid, session variables (`user_id`, `role`, `full_name`) are initialized, an audit entry is logged to `activity_log`, and the user is redirected via the PRG pattern to `dashboard.php`.

---

### Figure 4a, 4b & 5a: Self-Service Password Recovery & Reset

```
[ Screenshot Paths: 
  screenshots/04a-forgot-password-default.png 
  screenshots/04b-forgot-password-token-generated.png 
  screenshots/05a-reset-password-form.png ]
```

**Figure 4a, 4b & 5a: Password Recovery System (`forgot-password.php` & `reset-password.php`)**
- 🔺 **Account Lookup Input (Figure 4a)** — Accepts user email address for identity verification.
- 🔺 **Cryptographic Token Generator (Figure 4b)** — Generates an unguessable 64-character hexadecimal reset token stored with a 1-hour expiration.
- 🔺 **Tokenized Reset Interface (Figure 5a)** — Validates `$_GET['token']` and presents New Password and Confirm Password inputs.
- 🔺 **Single-Use Token Purge** — Commits the new bcrypt hash to `users` and immediately deletes the used token from `password_resets`.

**Technical & Architectural Explanation:**  
The recovery pipeline prevents brute-force account takeovers by employing `bin2hex(random_bytes(32))` tokens with strict expiration timestamps (`expires_at > NOW()`). Upon successful password update, the token is permanently invalidated, ensuring single-use cryptographic security.

---

### Figure 6a: Patient Primary Dashboard & Health Metrics

```
[ Screenshot Path: screenshots/06a-patient-dashboard.png ]
```

**Figure 6a: Patient Primary Dashboard (`dashboard.php` - Patient View)**
- 🔺 **Personalized Patient Greeting** — Displays patient name, registered CNIC number, and insurance policy coverage status.
- 🔺 **Summary Metric Counters** — Aggregates Total Appointments, Pending Consultations, Completed Visits, and Outstanding Bills.
- 🔺 **Quick Action Launchers** — Direct navigation cards to "Book New Appointment", "My Appointments", and "Billing Invoices".
- 🔺 **Administrative Announcement Feed** — Displays high-priority warning messages or clinic updates when dispatched by administrators.

**Technical & Architectural Explanation:**  
`dashboard.php` is protected by `requireLogin()` and `requireRole('patient')`. It executes indexed `COUNT(*)` aggregation queries across `appointments` and `billing` joined on `patient_id`. The interface provides patients with instant clarity regarding upcoming consultations and pending payments.

---

### Figure 7a & 7b: Computer-Assisted Symptom Assessment (Step 1)

```
[ Screenshot Paths: 
  screenshots/07a-symptom-assessment-empty.png 
  screenshots/07b-symptom-assessment-filled.png ]
```

**Figure 7a & 7b: Symptom Assessment Matrix (`book-appointment.php` - Step 1)**
- 🔺 **Symptom Checkbox Matrix** — Structured multi-select checkboxes for common clinical symptoms (Fever, Dry Cough, Shortness of Breath, Chest Pain, Fatigue, Headache, Skin Rash).
- 🔺 **Natural Language Free-Text Area** — Multi-line description box allowing patients to describe their symptoms in plain English.
- 🔺 **Symptom Duration Selector** — Captures duration (`< 24 hours`, `1-3 days`, `1+ weeks`) to calibrate clinical advice.
- 🔺 **Analyze & Triage Button** — Submits symptom payload to the automated scoring algorithm.

**Technical & Architectural Explanation:**  
Step 1 of the booking pipeline accepts both discrete and unstructured symptom data. The form POSTs to the diagnosis engine which tokenizes the free-text description, performs lowercase keyword matching against the medical vocabulary, and merges it with checked items using `array_unique()`.

---

### Figure 8a & 8b: Diagnostic Triage Output & Emergency Protocol

```
[ Screenshot Paths: 
  screenshots/08a-diagnosis-normal-result.png 
  screenshots/08b-diagnosis-emergency-result.png ]
```

**Figure 8a & 8b: Diagnosis Results (`appointment-result.php`)**
- 🔺 **Assessed Condition Card (Figure 8a)** — Identifies the most probable condition (e.g. "Viral Flu / Common Cold") with confidence rating.
- 🔺 **Severity Classification Badge** — Visual pills: `Normal` (Blue), `Moderate` (Amber), or `Emergency` (Red).
- 🔺 **Tailored Clinical Advice Box** — Actionable patient recommendations calibrated by symptom duration.
- 🔺 **Red Emergency Alert Banner (Figure 8b)** — Prominently warns when acute red-flag symptoms (chest pain, severe dyspnea) are detected.
- 🔺 **Immediate First-Aid Protocol Card** — Step-by-step non-medical safety steps (e.g. sit upright, loosen tight clothing, stay calm).
- 🔺 **Recommended Specialist Tag** — Automatically recommends matching specialist (General Physician vs Cardiologist).

**Technical & Architectural Explanation:**  
`appointment-result.php` processes the symptom set against the `diagnosis_rules` table using an `array_intersect()` weighted scoring matrix. For emergency conditions, severity is escalated to `Emergency`, which ensures the booking is pushed to the top of the doctor's queue in `doctor-appointments.php`. Diagnostic results are stored in `$_SESSION['diagnosis_data']` to flow seamlessly into Step 2.

---

### Figure 10a, 10b, 11a & 11b: Specialist Scheduling & Conflict Prevention Engine

```
[ Screenshot Paths: 
  screenshots/10a-appointment-step2-doctor-select.png 
  screenshots/10b-appointment-step2-time-picker.png 
  screenshots/11a-appointment-conflict-working-hours.png 
  screenshots/11b-appointment-conflict-5min-buffer.png ]
```

**Figure 10a–11b: Appointment Scheduling (`book-appointment.php` - Step 2)**
- 🔺 **Doctor Selection Dropdown (Figure 10a)** — Lists active physicians, pre-selecting the recommended doctor with a `[RECOMMENDED]` badge.
- 🔺 **Clinic Address & Working Hours Card** — Dynamically displays the selected doctor's room number and practice hours.
- 🔺 **Date & Time Selectors (Figure 10b)** — Calendar picker preventing past date selection and time slot input.
- 🔺 **Working Hours Conflict Warning (Figure 11a)** — Live AJAX alert: *"Doctor is only available between 02:00 PM and 05:00 PM."*
- 🔺 **5-Minute Slot Buffer Conflict Warning (Figure 11b)** — Live alert: *"Time is within 5 minutes of an existing appointment. Next available slot: 02:45 PM."*

**Technical & Architectural Explanation:**  
Step 2 joins `doctors` and `users` tables. As the patient selects a doctor and time, asynchronous requests are dispatched to `check-availability.php`. The backend evaluates `appointment_time BETWEEN (existing - 5 min) AND (existing + 5 min)` and `TIME(appointment_time) BETWEEN available_from AND available_to`. If a conflict occurs, it calculates the next available conflict-free slot and suggests it to the user.

---

### Figure 12a, 13a, 13b & 14a: Patient Appointments Portal & Rescheduling Rules

```
[ Screenshot Paths: 
  screenshots/12a-my-appointments-list.png 
  screenshots/13a-reschedule-appointment-form.png 
  screenshots/13b-reschedule-appointment-disabled.png 
  screenshots/14a-cancel-appointment-modal.png ]
```

**Figure 12a–14a: Patient Appointments Management (`my-appointments.php` & `reschedule-appointment.php`)**
- 🔺 **Appointments Table (Figure 12a)** — Displays Appointment ID, Doctor Name, Date/Time, Assessed Disease, Priority badge, and Status pill.
- 🔺 **Rescheduling Form (Figure 13a)** — Shows current booking details, clinic notice rules, and new date/time pickers.
- 🔺 **1-Hour Minimum Notice Guard (Figure 13b)** — Renders the reschedule button in a disabled state for appointments scheduled within < 1 hour.
- 🔺 **Cancellation Confirmation Guard (Figure 14a)** — Client-side modal prompt requiring explicit confirmation before cancelling a booking.

**Technical & Architectural Explanation:**  
`my-appointments.php` queries appointments filtered by `patient_id`. For each booking, it evaluates `(strtotime($appt_time) - time()) < 3600`. If within 1 hour, rescheduling is blocked server-side and disabled in the UI. Cancelling an appointment transitions `status = 'Cancelled'` and immediately reopens the doctor's time slot for new bookings.

---

### Figure 15a, 16a, 16b, 17a & 18a: Doctor Clinical Workspace & Practice Management

```
[ Screenshot Paths: 
  screenshots/15a-doctor-dashboard.png 
  screenshots/16a-doctor-appointments-queue.png 
  screenshots/16b-doctor-appointments-refresh-indicator.png 
  screenshots/17a-doctor-completion-time-guard.png 
  screenshots/18a-doctor-profile-form.png ]
```

**Figure 15a–18a: Doctor Portal (`doctor-appointments.php` & `doctor-profile.php`)**
- 🔺 **Doctor Dashboard (Figure 15a)** — Summarizes Today's Queue, Total Patients Treated, Emergency Visits, and practice summary.
- 🔺 **Emergency-Prioritized Queue (Figure 16a)** — Lists appointments sorted with emergency cases pinned at the top.
- 🔺 **30-Second Auto-Refresh Indicator (Figure 16b)** — Visual notice: *"🔄 Auto-refreshes every 30 seconds to show new appointments"*.
- 🔺 **Time-Validated Completion Guard (Figure 17a)** — Protects clinical integrity by blocking doctors from marking future appointments completed.
- 🔺 **Doctor Profile Editor (Figure 18a)** — Settings form for updating Specialization, Clinic Address, City, Practice Hours, and Fee.

**Technical & Architectural Explanation:**  
`doctor-appointments.php` enforces clinical triage via SQL: `ORDER BY CASE WHEN severity_level = 'Emergency' THEN 1 ELSE 2 END, appointment_time ASC`. A client-side timer refreshes the view every 30 seconds. When completing an appointment, the server validates `strtotime($appt_time) <= time()`, preventing premature record completion and transitioning `status = 'Completed'`.

---

### Figure 19a, 19b, 20a & 21a: Automated Invoicing & Itemized Medical Receipts

```
[ Screenshot Paths: 
  screenshots/19a-billing-generation-empty.png 
  screenshots/19b-billing-generation-calculated.png 
  screenshots/20a-my-bills-list.png 
  screenshots/21a-printable-receipt-paid.png ]
```

**Figure 19a–21a: Billing & Invoicing System (`billing.php`, `my-bills.php`, `view-receipt.php`)**
- 🔺 **Invoicing Generator (Figure 19a & 19b)** — Live calculator computing `Total = (Consultation + Tests + Treatments + Tax) - Insurance Discount`.
- 🔺 **Patient Bills Portal (Figure 20a)** — Displays invoice ledger with `Paid`/`Unpaid` status pills and insurance savings summary.
- 🔺 **Printable Itemized Receipt (Figure 21a)** — Professional medical invoice featuring clinic metadata, itemized ledger, `PAID` stamp, and Print/PDF stylesheet.

**Technical & Architectural Explanation:**  
`billing.php` provides interactive client-side calculations that pre-populate the doctor's fee and compute tax and insurance discounts. `view-receipt.php` incorporates `@media print` CSS rules, suppressing headers and buttons during printing to produce clean, professional A4 receipts.

---

### Figure 22a, 23a–23e, 24a, 25a, 26a & 27a: Admin Command & Governance Center

```
[ Screenshot Paths: 
  screenshots/22a-admin-dashboard.png 
  screenshots/23a-admin-panel-users-tab.png 
  screenshots/23b-admin-panel-search-filtered.png 
  screenshots/23c-admin-panel-patients-tab.png 
  screenshots/23d-admin-panel-doctors-tab.png 
  screenshots/23e-admin-panel-appointments-tab.png 
  screenshots/24a-admin-suspend-user-action.png 
  screenshots/25a-admin-warning-modal.png 
  screenshots/26a-activity-log-table.png 
  screenshots/27a-analytics-dashboard-charts.png ]
```

**Figure 22a–27a: Admin Management (`admin-panel.php`, `activity-log.php`, `analytics.php`)**
- 🔺 **Admin Command Dashboard (Figure 22a)** — Displays global metrics (Total Users, Doctors, Patients, Appointments, Revenue).
- 🔺 **Tabbed Management Hub (Figure 23a–23e)** — Dedicated tabs for Users, Patients, Doctors, and Global Appointments.
- 🔺 **Real-Time Instant Search Filter (Figure 23b)** — Client-side filter isolating records across all visible columns without page reload.
- 🔺 **User Suspension & Session Revocation (Figure 24a)** — Toggles user status between `Active` and `Suspended`, revoking active sessions.
- 🔺 **Warning Dispatch Modal (Figure 25a)** — Popup modal dialog to send administrative warnings logged to `activity_log`.
- 🔺 **Security Audit Log (Figure 26a)** — Immutable audit trail displaying timestamps, user IDs, roles, and action categories.
- 🔺 **Operational Analytics Dashboard (Figure 27a)** — Interactive Chart.js doughnut, bar, and pie charts summarizing clinic performance.

**Technical & Architectural Explanation:**  
The administrative suite provides full operational control protected by `requireRole('admin')`. When a user is suspended, `db.php` terminates their session on their next navigation, and the background polling API (`check-session-status.php`) forcibly logs out idle tabs within 20 seconds. `analytics.php` compiles database aggregations into Chart.js data objects for visual reporting.

---

### Figure 28a, 28b & 29a: Standalone Desktop Utilities (C++ & VB.NET)

```
[ Screenshot Paths: 
  screenshots/28a-cpp-billing-menu.png 
  screenshots/28b-cpp-billing-calculated-receipt.png 
  screenshots/29a-vb-billing-form.png ]
```

**Figure 28a–29a: Standalone Desktop Applications**
- 🔺 **C++ Terminal Billing Menu (Figure 28a)** — Interactive console menu with input validation loop.
- 🔺 **C++ Calculated Terminal Receipt (Figure 28b)** — Outputs formatted receipt via `<iomanip>` showing Subtotal, 15% discount, late fine (Rs. 50/day), and Total.
- 🔺 **VB.NET Windows Desktop GUI Form (Figure 29a)** — Desktop window computing inpatient room accommodation (Days × Room Charge), consultation fee, 20% insurance discount, and Total.

**Technical & Architectural Explanation:**  
The C++ program (`patient_billing.cpp`) and VB.NET application (`HospitalBilling`) are standalone offline desktop billing calculators. They ensure that clinic cashiers can compute patient charges, apply policy discounts, and assess late fines even during network or server downtime.

---

## 🏁 Visual Documentation Summary

All **45 distinct visual states and sub-states** across the web and desktop applications have been captured, cataloged, and technically documented. Evaluators and students can directly cross-reference each figure with the live screenshots stored in `/screenshots/`.
