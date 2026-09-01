-- =====================================================
-- Smart Healthcare & Diagnostic Management System
-- Database Schema + Seed Data
-- DIT 2nd Semester Final Project
-- =====================================================
-- HOW TO USE: Open phpMyAdmin → SQL tab → paste this entire file → click "Go"
-- OR run from MySQL CLI:  mysql -u root < schema.sql
-- =====================================================

-- Create the database (skip if already exists)
CREATE DATABASE IF NOT EXISTS healthcare_system;
USE healthcare_system;

-- =====================================================
-- TABLE 1: users
-- Central authentication table for ALL roles (patient, doctor, admin)
-- Passwords are stored as bcrypt hashes, NEVER as plain text
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,           -- Unique ID for each user
    full_name VARCHAR(100) NOT NULL,                   -- User's full name
    email VARCHAR(100) NOT NULL UNIQUE,                -- Login email (must be unique)
    password VARCHAR(255) NOT NULL,                    -- Bcrypt hash from password_hash()
    role ENUM('patient', 'doctor', 'admin') NOT NULL,  -- Determines which dashboard they see
    phone VARCHAR(20) NOT NULL,                        -- Contact phone number
    status ENUM('active', 'suspended') DEFAULT 'active', -- Account status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP     -- Auto-set when account is created
);

-- =====================================================
-- TABLE 2: patients
-- Extra profile data for users with role = 'patient'
-- Linked to users table via user_id foreign key
-- =====================================================
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,         -- Unique patient ID
    user_id INT NOT NULL,                              -- Links to users.user_id
    age INT NOT NULL,                                  -- Patient's age (validated: 1-120)
    weight DECIMAL(5,2) NOT NULL,                      -- Weight in kg
    cnic VARCHAR(15) NOT NULL,                         -- Pakistani CNIC: XXXXX-XXXXXXX-X
    insurance_number VARCHAR(50) DEFAULT NULL,          -- If set, patient gets billing discount
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 3: doctors
-- Extra profile data for users with role = 'doctor'
-- Includes schedule and fee information
-- =====================================================
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,           -- Unique doctor ID
    user_id INT NOT NULL,                              -- Links to users.user_id
    specialization VARCHAR(100) NOT NULL,               -- e.g., "General Physician", "Cardiologist"
    clinic_address VARCHAR(255) DEFAULT NULL,           -- Clinic location address
    city VARCHAR(100) DEFAULT NULL,                     -- City location
    available_from TIME DEFAULT '09:00:00',             -- Start of working hours
    available_to TIME DEFAULT '17:00:00',               -- End of working hours
    consultation_fee DECIMAL(10,2) DEFAULT 500.00,      -- Fee in PKR (default Rs. 500)
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 4: appointments
-- Links a patient to a doctor with severity and status
-- Severity determines priority sorting for doctors
-- =====================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,                            -- Which patient booked
    doctor_id INT NOT NULL,                             -- Which doctor is assigned
    severity_level ENUM('Emergency', 'Normal', 'Follow-up') NOT NULL,  -- Priority level
    appointment_time DATETIME NOT NULL,                 -- Scheduled date and time
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    token_number VARCHAR(50) DEFAULT NULL,              -- Unique booking token / reference code
    payment_method VARCHAR(50) DEFAULT 'Cash at Reception', -- Payment option selected
    payment_tid VARCHAR(100) DEFAULT NULL,              -- Transaction ID for online demo payment (JazzCash/EasyPaisa)
    payment_screenshot_path VARCHAR(255) DEFAULT NULL,  -- Relative path for payment proof screenshot
    symptoms_selected VARCHAR(500) DEFAULT NULL,        -- Comma-separated list of symptoms chosen
    symptoms_text TEXT DEFAULT NULL,                    -- Patient's raw typed symptom description
    diagnosed_disease VARCHAR(100) DEFAULT NULL,        -- Preliminary assessment disease name
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,     -- When booking was made
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 5: diagnosis_rules
-- Powers the symptom-based diagnosis engine
-- Each rule maps a combination of symptoms to a possible disease
-- The PHP code scores these rules using array_intersect() — NOT hardcoded if/else
-- =====================================================
CREATE TABLE IF NOT EXISTS diagnosis_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    symptom_combination VARCHAR(500) NOT NULL,           -- Comma-separated symptoms (e.g., "fever,cough,headache")
    possible_disease VARCHAR(100) NOT NULL,              -- Disease name to display
    advice TEXT NOT NULL,                                -- Medical advice text
    recommended_specialization VARCHAR(100) DEFAULT 'General Physician', -- Suggested doctor specialization
    is_emergency TINYINT(1) DEFAULT 0,                   -- Flag for high-priority emergency rules
    first_aid_steps TEXT DEFAULT NULL                    -- General, non-medical first-aid safety steps
);

-- =====================================================
-- TABLE 6: billing
-- Auto-calculated bills for completed appointments
-- Consultation fee is pulled from the doctor's record, not entered manually
-- Insurance discount (20%) applied if patient has insurance_number
-- =====================================================
CREATE TABLE IF NOT EXISTS billing (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,                         -- Links to the appointment
    consultation_fee DECIMAL(10,2) NOT NULL,             -- Pulled from doctors.consultation_fee
    test_charges DECIMAL(10,2) DEFAULT 0.00,             -- Additional test/lab charges
    insurance_discount_percent DECIMAL(5,2) DEFAULT 0.00, -- 20% if insured, 0% otherwise
    total_amount DECIMAL(10,2) NOT NULL,                 -- Final calculated amount
    payment_status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid', -- Payment status tracking
    payment_tid VARCHAR(100) DEFAULT NULL,              -- Transaction ID from online payment
    payment_screenshot_path VARCHAR(255) DEFAULT NULL,  -- Screenshot receipt path
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 7: remember_tokens
-- Secure persistent 30-day login tokens for "Remember Me"
-- Stores selector + hashed validator (never raw tokens or passwords)
-- =====================================================
CREATE TABLE IF NOT EXISTS remember_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector VARCHAR(64) NOT NULL,
    hashed_validator VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 8: activity_log
-- Audit trail of key system actions
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- =====================================================
-- TABLE 9: warnings
-- Admin warning notifications for users
-- =====================================================
CREATE TABLE IF NOT EXISTS warnings (
    warning_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- =====================================================
-- SEED DATA: Diagnosis Rules (7 rules)
-- These are the rules the diagnosis engine matches against
-- More rules = more accurate matching
-- Format: symptom names are lowercase with underscores
-- =====================================================

INSERT INTO diagnosis_rules (symptom_combination, possible_disease, advice, recommended_specialization, is_emergency, first_aid_steps) VALUES

-- Rule 1: Flu — common combination of fever + cough + body ache + headache
('fever,cough,body_ache,headache',
 'Flu (Influenza)',
 'Rest well, drink plenty of fluids, and take over-the-counter fever reducers like paracetamol. See a doctor if symptoms persist beyond 3 days or if fever exceeds 103°F.',
 'General Physician', 0, NULL),

-- Rule 2: Respiratory Infection — involves breathing difficulty
('fever,cough,shortness_of_breath',
 'Respiratory Infection',
 'Seek medical attention promptly. Avoid smoking and dusty environments. A chest X-ray may be recommended by your doctor. Monitor breathing difficulty closely.',
 'Pulmonologist', 0, NULL),

-- Rule 3: Migraine — severe headache with light sensitivity
('headache,nausea,sensitivity_to_light',
 'Migraine',
 'Rest in a dark, quiet room. Stay hydrated and avoid screen time. Over-the-counter pain relievers may help. Consult a doctor if migraines occur frequently.',
 'Neurologist', 0, NULL),

-- Rule 4: Food Poisoning — stomach-related symptoms
('stomach_pain,vomiting,diarrhea,nausea',
 'Food Poisoning',
 'Stay hydrated with ORS (oral rehydration salts). Avoid solid food until vomiting stops. Eat bland foods when recovering. Seek medical help if symptoms last more than 24 hours.',
 'Gastroenterologist', 0, NULL),

-- Rule 5: Cardiac Issue — EMERGENCY, chest pain is critical
('chest_pain,shortness_of_breath,sweating',
 'Cardiac Issue (Emergency)',
 'SEEK IMMEDIATE EMERGENCY MEDICAL ATTENTION. Do not delay — call an ambulance or go to the nearest emergency room immediately. Do not ignore chest pain combined with breathing difficulty.',
 'Cardiologist', 1,
 '1. Call emergency services (1122) or go to the nearest ER immediately.\n2. Sit down or lie down in a comfortable position, avoid exertion.\n3. Loosen any tight clothing.\n4. Stay calm and avoid panic — try slow, steady breathing.\n5. Do not drive yourself — have someone else drive or call an ambulance.\n6. Do NOT take any medication unless already prescribed by your doctor for this exact condition.'),

-- Rule 6: Dengue — fever + rash + joint pain combination
('fever,body_ache,rash,joint_pain,headache',
 'Dengue Fever',
 'Seek medical attention immediately. Stay hydrated with fluids. Platelet count monitoring is essential. Use mosquito nets.',
 'General Physician', 1,
 '1. Seek immediate medical evaluation at a hospital or clinic.\n2. Rest in a comfortable place and stay hydrated with clean water or ORS.\n3. Use mosquito nets to prevent further mosquito bites.\n4. Monitor closely for warning signs such as severe abdominal pain or bleeding.\n5. Do NOT take any unprescribed medications.'),

-- Rule 7: Throat Infection — sore throat with swollen glands
('sore_throat,fever,swollen_glands,headache',
 'Throat Infection',
 'Gargle with warm salt water 3-4 times daily. Stay hydrated with warm fluids. Avoid cold drinks. Antibiotics may be needed — consult a doctor for proper prescription. Do not self-medicate.',
 'ENT Specialist', 0, NULL);


-- =====================================================
-- SEED DATA: Admin Account
-- Email: admin@healthcare.com | Password: admin123
-- 
-- IMPORTANT: The password hash below is for 'admin123'
-- Generated using PHP's password_hash('admin123', PASSWORD_DEFAULT)
-- If it doesn't work, run this in your browser:
--   <?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>
-- Then replace the hash below with the output
-- =====================================================

-- We'll create the admin via a PHP setup approach instead.
-- See the instructions below:

-- OPTION 1 (Recommended): Create a file called setup_admin.php with:
--   <?php
--   require 'db.php';
--   $hash = password_hash('admin123', PASSWORD_DEFAULT);
--   $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'admin', ?)");
--   $name = "System Administrator";
--   $email = "admin@healthcare.com";
--   $phone = "03001234567";
--   $stmt->bind_param("ssss", $name, $email, $hash, $phone);
--   $stmt->execute();
--   echo "Admin created!";
--   ?>
-- Run it ONCE in browser: http://localhost/healthcare/setup_admin.php
-- Then DELETE the file for security.

-- OPTION 2: Register as a normal user, then in phpMyAdmin:
--   UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';

-- =====================================================
-- END OF SCHEMA
-- =====================================================
