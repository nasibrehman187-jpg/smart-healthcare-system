# Smart Healthcare & Diagnostic Management System
# Master Expanded Screenshot Inventory & Verification Checklist

> **Real-World UI/UX Visual Catalog & Interaction Matrix**  
> *All screenshots captured live from the running application environment (`http://localhost/healthcare/`)*  
> *Location:* `/screenshots/`

---

## 📸 Complete 45-State Visual Inventory Table

| # | Exact Filename | Role / Auth State | Exact Click-Path & State Trigger | Detailed Description of Rendered UI/UX |
| :-: | :--- | :--- | :--- | :--- |
| **01a** | `01a-landing-page-hero.png` | Public (Unauthenticated) | Navigate to `http://localhost/healthcare/index.php` | Full landing page view: brand header, hero call-to-action banner, live metrics counter, core service cards. |
| **01b** | `01b-landing-page-faq-expanded.png` | Public (Unauthenticated) | Click FAQ assistant widget in bottom-right | Expanded floating FAQ chat widget displaying interactive clinical and clinic timing answers. |
| **02a** | `02a-register-empty.png` | Public (Unauthenticated) | Navigate to `http://localhost/healthcare/register.php` | Initial empty registration form showing Role selector, basic identity fields, and security notice. |
| **02b** | `02b-register-patient-filled.png` | Public (Unauthenticated) | Select `Role = Patient` & fill all demographic inputs | Patient registration form fully populated: Full Name, Email, Phone, Age, Weight, Pakistani CNIC, Insurance Number, Password. |
| **02c** | `02c-register-doctor-filled.png` | Public (Unauthenticated) | Select `Role = Doctor` & fill all clinical inputs | Doctor registration form populated: Specialization, Clinic Address, City, Available Hours (09:00 - 15:00), Consultation Fee (Rs. 1,500). |
| **02d** | `02d-register-validation-error.png` | Public (Unauthenticated) | Enter mismatched passwords and submit form | Form re-rendered displaying high-visibility red validation alert: `"Passwords do not match."` |
| **03a** | `03a-login-default.png` | Public (Unauthenticated) | Navigate to `http://localhost/healthcare/login.php` | Clean single-card authentication interface with Email/Password inputs, Remember Me checkbox, and Register link. |
| **03b** | `03b-login-password-revealed.png` | Public (Unauthenticated) | Type password and click `👁️` toggle icon | Password field switches to `type="text"`, reveals plaintext password, and toggle icon changes to `🙈`. |
| **03c** | `03c-login-invalid-error.png` | Public (Unauthenticated) | Enter invalid credentials and click Login | Red alert banner displayed: `"Invalid email or password."` (generic message preventing user enumeration). |
| **03d** | `03d-login-suspended-alert.png` | Suspended Session | Admin suspends user -> User redirected to `login.php?suspended=1` | High-priority amber alert box: `"Your account was suspended. Please contact admin for assistance."` |
| **04a** | `04a-forgot-password-default.png` | Public (Unauthenticated) | Click "Forgot Password?" link on login page | Password recovery request form with Email input and "Send Reset Link" button. |
| **04b** | `04b-forgot-password-token-generated.png` | Public (Unauthenticated) | Enter registered email and click Submit | Security confirmation banner displaying the 64-character cryptographic token and direct reset hyperlink. |
| **05a** | `05a-reset-password-form.png` | Public (Tokenized) | Open `reset-password.php?token=demo_recovery_token` | Token-validated password reset form with New Password and Confirm Password inputs. |
| **06a** | `06a-patient-dashboard.png` | Logged in as **Patient** | Authenticate as `nasibrehman187@gmail.com` | Patient dashboard displaying personalized greeting, Total/Pending/Completed appointment counters, and Quick Action buttons. |
| **07a** | `07a-symptom-assessment-empty.png` | Logged in as **Patient** | Click "Book New Appointment" -> Step 1 | Initial symptom evaluation form with multi-select checkboxes, duration picker, and free-text description box. |
| **07b** | `07b-symptom-assessment-filled.png` | Logged in as **Patient** | Check 3 symptoms (Fever, Cough, Fatigue) + enter text | Step 1 form populated with patient notes: *"Experiencing dry coughing, continuous low fever, and fatigue for 2 days."* |
| **08a** | `08a-diagnosis-normal-result.png` | Logged in as **Patient** | Submit non-emergency symptoms (Fever, Cough) | Diagnostic result card: "Viral Flu / Common Cold", Normal severity pill (Blue), duration advice, and recommended General Physician. |
| **08b** | `08b-diagnosis-emergency-result.png` | Logged in as **Patient** | Submit red-flag symptoms (Chest Pain, Dyspnea) | Red emergency alert banner, first-aid protocol cards (sit upright, loosen clothing), and direct Cardiologist recommendation. |
| **10a** | `10a-appointment-step2-doctor-select.png` | Logged in as **Patient** | Click "Proceed to Doctor Booking" -> Step 2 | Step 2 doctor dropdown showing `[RECOMMENDED]` specialist badge, consultation fee, clinic address, and practice hours. |
| **10b** | `10b-appointment-step2-time-picker.png` | Logged in as **Patient** | Select appointment date and time slot | Step 2 with calendar date picker and time input (`14:30`) within doctor's active working hours. |
| **11a** | `11a-appointment-conflict-working-hours.png` | Logged in as **Patient** | Select consultation time `23:30` (outside hours) | Dynamic AJAX warning box: *"Doctor is only available between 02:00 PM and 05:00 PM."* |
| **11b** | `11b-appointment-conflict-5min-buffer.png` | Logged in as **Patient** | Select time within 5 minutes of existing booking | Dynamic warning: *"Time is within 5 minutes of an existing appointment. Next available slot: 02:45 PM."* |
| **12a** | `12a-my-appointments-list.png` | Logged in as **Patient** | Click "My Appointments" in navigation | Comprehensive appointments table with Doctor Name, Date/Time, Assessed Disease, Priority badge, Status pill, and Action buttons. |
| **13a** | `13a-reschedule-appointment-form.png` | Logged in as **Patient** | Click "✏️ Reschedule" on eligible appointment | Rescheduling form displaying original booking summary, clinic notice rules, and new date/time pickers. |
| **13b** | `13b-reschedule-appointment-disabled.png` | Logged in as **Patient** | View appointment scheduled within < 1 hour | Table row showing disabled reschedule button with tooltip: *"Cannot reschedule — less than 1 hour before scheduled time"*. |
| **14a** | `14a-cancel-appointment-modal.png` | Logged in as **Patient** | Click "✖ Cancel" button on appointment row | Confirmation dialog guard preventing accidental cancellations before updating status to `Cancelled`. |
| **15a** | `15a-doctor-dashboard.png` | Logged in as **Doctor** | Authenticate as `engrazhariqbal34@gmail.com` | Doctor dashboard showing Today's Patient Queue counter, Total Patients Treated, Emergency Visits, and practice summary. |
| **16a** | `16a-doctor-appointments-queue.png` | Logged in as **Doctor** | Click "Appointments" in doctor navigation | Emergency-prioritized clinical queue (Emergency visits pinned at top) with patient symptom breakdown. |
| **16b** | `16b-doctor-appointments-refresh-indicator.png` | Logged in as **Doctor** | Top bar of `doctor-appointments.php` | Visual indicator: *"🔄 This page auto-refreshes every 30 seconds to show new appointments"* (pauses during active dialogs). |
| **17a** | `17a-doctor-completion-time-guard.png` | Logged in as **Doctor** | Click "Mark as Completed" on appointment | Time guard enforcement: validates that appointment time has arrived before allowing status completion. |
| **18a** | `18a-doctor-profile-form.png` | Logged in as **Doctor** | Click "Edit Profile" in doctor portal | Settings form for modifying Specialization, Clinic Address, City, Practice Hours (`available_from` / `available_to`), and Fee. |
| **19a** | `19a-billing-generation-empty.png` | Logged in as **Doctor** | Navigate to `billing.php` | Invoice generation form showing Appointment selector dropdown and empty fee breakdown fields. |
| **19b** | `19b-billing-generation-calculated.png` | Logged in as **Doctor** | Select appointment and enter Lab / Treatment charges | Live calculation card: Base Fee (Rs. 800) + Lab Tests (Rs. 650) + Treatment (Rs. 350) + Tax (Rs. 50) - Insurance = Total. |
| **20a** | `20a-my-bills-list.png` | Logged in as **Patient** | Click "My Bills" in patient navigation | Patient financial records table showing Invoice ID, Consultation Date, Doctor, Total Billed Amount, and Paid/Unpaid badges. |
| **21a** | `21a-printable-receipt-paid.png` | Logged in as **Patient** | Click "View Receipt" on paid bill #1 | Professional itemized medical receipt with clinic metadata, line items ledger, `PAID` stamp, and Print/PDF stylesheet. |
| **22a** | `22a-admin-dashboard.png` | Logged in as **Admin** | Authenticate as `admin@healthcare.com` | Administrator command center displaying Total Users, Registered Doctors, Active Patients, Appointments, and Revenue metrics. |
| **23a** | `23a-admin-panel-users-tab.png` | Logged in as **Admin** | Click "Admin Panel" -> All Users Tab | Centralized user accounts table with User ID, Full Name, Email, Role, Status badge, and Action controls. |
| **23b** | `23b-admin-panel-search-filtered.png` | Logged in as **Admin** | Type `"Nasib"` in table instant search bar | Sub-millisecond client-side filtering isolating the matching patient record across all visible columns. |
| **23c** | `23c-admin-panel-patients-tab.png` | Logged in as **Admin** | Click "Patients" tab in Admin Panel | Dedicated patient demographics table showing Patient ID, Name, Age, Weight (kg), CNIC, and Insurance Number. |
| **23d** | `23d-admin-panel-doctors-tab.png` | Logged in as **Admin** | Click "Doctors" tab in Admin Panel | Dedicated doctors table showing Doctor ID, Name, Specialization, Clinic Address, Working Hours, and Consultation Fee. |
| **23e** | `23e-admin-panel-appointments-tab.png` | Logged in as **Admin** | Click "Appointments" tab in Admin Panel | Global clinic appointments table showing Patient, Doctor, Date/Time, Severity, and Lifecycle Status. |
| **24a** | `24a-admin-suspend-user-action.png` | Logged in as **Admin** | Click "Suspend / Reactivate" status button | Account status toggled to `Suspended` with immediate background session termination confirmation. |
| **25a** | `25a-admin-warning-modal.png` | Logged in as **Admin** | Click "✉️ Send Warning" on user row | Modal popup dialog with pre-filled User ID, recipient name, and custom warning message text area. |
| **26a** | `26a-activity-log-table.png` | Logged in as **Admin** | Click "Activity Log" in admin navigation | Immutable security audit trail table displaying Log ID, Timestamp, User Name, Role, Action category, and Context details. |
| **27a** | `27a-analytics-dashboard-charts.png` | Logged in as **Admin** | Click "System Analytics" in admin navigation | Rendered Chart.js graphs: Appointments Status (Doughnut), Monthly Revenue (Bar), and User Demographics (Pie). |
| **28a** | `28a-cpp-billing-menu.png` | C++ Standalone CLI | Launch `patient_billing.exe` in terminal | Interactive ASCII console menu with input validation loop for patient billing and late fine calculation. |
| **28b** | `28b-cpp-billing-calculated-receipt.png` | C++ Standalone CLI | Enter patient data, 4 late days, senior category | Formatted C++ terminal receipt output using `<iomanip>` showing Subtotal, 15% discount, Rs. 200 late fine, and Total. |
| **29a** | `29a-vb-billing-form.png` | VB.NET Standalone GUI | Launch `HospitalBilling.sln` (.NET Windows Forms) | Windows GUI form calculating room charges (3 days @ Rs. 1,200), consultation fee, 20% insurance discount, and Total Payable. |

---

## 🎯 Verification & Confirmation of Static Pages

As required by Rule 6, the following pages were examined and confirmed to have a single static visual view (no internal sub-tabs):
- `login.php` (Single authentication card layout with toggleable states)
- `forgot-password.php` (Single form layout)
- `reset-password.php` (Single tokenized form layout)
- `doctor-profile.php` (Single clinical configuration form layout)
- `activity-log.php` (Single chronological audit table layout)
- `view-receipt.php` (Single printable A4 invoice layout)

All other pages with internal sub-states, role variations, dynamic tabs, modals, and validation errors have been captured and documented individually in the matrix above.
