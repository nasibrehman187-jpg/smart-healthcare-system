<?php
// =====================================================
// book-appointment.php — 2-Step Assessment & Booking Flow
// =====================================================
// STEP 1: Symptom Assessment & Duration Input
//   - Patient selects symptoms and specifies days duration
//   - PHP runs weighted scoring diagnosis engine (array_intersect)
//   - Adjusts advice based on duration (>=7 days or >=3 days emergency)
//   - Stores temporary result in $_SESSION['pending_diagnosis']
//   - Shows assessment result with "Yes, Book an Appointment" and "No, Not Now"
//
// STEP 2: Doctor & Time Selection
//   - "No, Not Now" clears session and redirects to dashboard.php
//   - "Yes, Book an Appointment" proceeds to Step 2 (Doctor & Schedule selection)
//   - On final POST: inserts appointment into database, sets $_SESSION['last_diagnosis']
//     and redirects to appointment-result.php (PRG pattern).
// =====================================================

require 'db.php';
requireLogin();
requireRole('patient'); // Only patients can book appointments

// Handle cancellation of pending diagnosis or booking
if (isset($_GET['cancel_pending'])) {
    unset($_SESSION['pending_diagnosis']);
    unset($_SESSION['pending_booking']);
    header("Location: dashboard.php");
    exit();
}

// Get the patient_id for the logged-in user
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$patient_row = $stmt->get_result()->fetch_assoc();
$patient_id = $patient_row['patient_id'] ?? 0;
$stmt->close();

// Fetch all available doctors for the dropdown
$doctors_result = $conn->query(
    "SELECT d.doctor_id, u.full_name, d.specialization, d.clinic_address, d.city, d.consultation_fee,
            d.available_from, d.available_to
     FROM doctors d
     JOIN users u ON d.user_id = u.user_id
     ORDER BY u.full_name ASC"
);

// All known symptoms for checkbox grid (key => friendly label)
$all_symptoms = [
    'fever'                => '🌡️ Fever',
    'cough'                => '😷 Cough',
    'body_ache'            => '💪 Body Ache',
    'shortness_of_breath'  => '😮‍💨 Shortness of Breath',
    'headache'             => '🤕 Headache',
    'nausea'               => '🤢 Nausea',
    'sensitivity_to_light' => '💡 Sensitivity to Light',
    'stomach_pain'         => '🤧 Stomach Pain',
    'vomiting'             => '🤮 Vomiting',
    'diarrhea'             => '🚽 Diarrhea',
    'chest_pain'           => '💔 Chest Pain',
    'sweating'             => '💦 Sweating',
    'rash'                 => '🔴 Skin Rash',
    'fatigue'              => '🥱 Fatigue / Weakness',
    'loss_of_appetite'     => '🍽️ Loss of Appetite',
    'dizziness'            => '💫 Dizziness',
    'sore_throat'          => '🧣 Sore Throat',
    'runny_nose'           => '🤧 Runny Nose',
    'joint_pain'           => '🦴 Joint Pain',
    'chills'               => '🥶 Chills'
];

$error = '';

// Determine current step
$requested_step = intval($_GET['step'] ?? 1);

// Step 2 Guard: Redirect to Step 1 if trying to jump to Step 2 without completing Step 1
if ($requested_step === 2 && !isset($_SESSION['pending_diagnosis'])) {
    header("Location: book-appointment.php?step=1");
    exit();
}

// Step 3 Guard: Redirect to Step 2 if trying to jump to Step 3 without completing Step 2
if ($requested_step === 3 && (!isset($_SESSION['pending_diagnosis']) || !isset($_SESSION['pending_booking']))) {
    header("Location: book-appointment.php?step=2");
    exit();
}

// Reset pending diagnosis on fresh GET to Step 1 (when no form submission is happening)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['step']) && !isset($_GET['view_result'])) {
    unset($_SESSION['pending_diagnosis']);
    unset($_SESSION['pending_booking']);
}

// Keyword Map for optional free-text symptom matching (English + Roman Urdu)
$keyword_map = [
    'fever'                => ['fever', 'bukhar', 'buqhar', 'bukhaar', 'garmi', 'temperature', 'harkat'],
    'cough'                => ['cough', 'khansi', 'khaansi', 'khasi'],
    'body_ache'            => ['body ache', 'bodyache', 'jism dard', 'badan dard', 'jism mein dard', 'jism mai dard', 'jism me dard', 'body pain', 'pain in body'],
    'shortness_of_breath'  => ['shortness of breath', 'breathless', 'sans phulna', 'saans phulna', 'saans me takleef', 'saans mein takleef', 'saans mai takleef', 'breath difficulty', 'breathing problem', 'difficulty breathing'],
    'headache'             => ['headache', 'head pain', 'sar dard', 'sardard', 'sar mein dard', 'sar mai dard', 'sir dard', 'sir mein dard'],
    'nausea'               => ['nausea', 'matli', 'dil kharab', 'ji machalna', 'jee machalna', 'ulti ka ehsaas', 'queasy', 'nauseous'],
    'sensitivity_to_light' => ['sensitivity to light', 'light sensitivity', 'roshni se takleef', 'photophobia'],
    'stomach_pain'         => ['stomach pain', 'stomachache', 'stomach ache', 'pait dard', 'paet dard', 'pait mein dard', 'pait mai dard', 'pait me dard', 'paet mein dard', 'abdominal pain', 'belly pain', 'qabz', 'constipation'],
    'vomiting'             => ['vomiting', 'vomit', 'ulti', 'ultian', 'ulteean', 'qay'],
    'diarrhea'             => ['diarrhea', 'loose motion', 'loose motions', 'dast', 'pet kharab', 'pait kharab', 'ishal'],
    'chest_pain'           => ['chest pain', 'seenay mein dard', 'seenay mai dard', 'seene mein dard', 'seene mai dard', 'seene me dard', 'chest mein dard', 'seene ka dard', 'chest tightness', 'pain in chest'],
    'sweating'             => ['sweating', 'sweat', 'paseena', 'pasina', 'excessive sweating'],
    'rash'                 => ['rash', 'rashes', 'kharish', 'skin allergy', 'red spots', 'khujli'],
    'joint_pain'           => ['joint pain', 'jodoron mein dard', 'jodon mein dard', 'jodon mai dard', 'ghutno mein dard', 'ghutnon mein dard', 'jodain dard', 'jod dard', 'joints pain'],
    'sore_throat'          => ['sore throat', 'gala kharab', 'gale mein dard', 'gale mai dard', 'gala dard', 'throat pain', 'throat infection'],
    'swollen_glands'       => ['swollen glands', 'gale ki sujan', 'swollen lymph', 'swelling in neck']
];


// =====================================================
// HANDLE FORM SUBMISSION (POST)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- CSRF Verification ---
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {

        $form_step = intval($_POST['form_step'] ?? 1);

        // =================================================
        // STEP 1 POST: SYMPTOM ASSESSMENT
        // =================================================
        if ($form_step === 1) {

            $checkbox_symptoms = $_POST['symptoms'] ?? [];
            if (!is_array($checkbox_symptoms)) {
                $checkbox_symptoms = [$checkbox_symptoms];
            }
            $symptoms_text = trim($_POST['symptoms_text'] ?? '');
            $days_duration = intval($_POST['days_duration'] ?? 1);

            // Supplementary Keyword Matching on Free-Text Input
            $text_detected_symptoms = [];
            if ($symptoms_text !== '') {
                $lower_text = mb_strtolower($symptoms_text);
                foreach ($keyword_map as $sym_key => $keywords) {
                    foreach ($keywords as $kw) {
                        if (mb_stripos($lower_text, $kw) !== false) {
                            $text_detected_symptoms[] = $sym_key;
                            break;
                        }
                    }
                }
            }

            // Symptoms detected purely from text (excluding those already checked in checkboxes)
            $text_only_detected = array_values(array_diff($text_detected_symptoms, $checkbox_symptoms));

            // Merged symptoms list (checkbox + text detected, deduplicated & cleaned)
            $selected_symptoms = array_values(array_filter(array_unique(array_merge($checkbox_symptoms, $text_detected_symptoms)), function($val) {
                return is_string($val) && trim($val) !== '';
            }));

            $has_checkbox_symptoms = (count($checkbox_symptoms) > 0);
            $has_text_input        = (mb_strlen($symptoms_text) > 0);

            if (!$has_checkbox_symptoms && !$has_text_input) {
                $error = "Please select at least one symptom or describe your symptoms in the text box below.";
            } elseif ($days_duration < 1 || $days_duration > 90) {
                $error = "Please enter a valid symptom duration (1 to 90 days).";
            } else {

                // Run Diagnosis Engine scoring against diagnosis_rules
                $rules_result = $conn->query("SELECT * FROM diagnosis_rules");
                $best_match = null;
                $best_score = 0;

                while ($rule = $rules_result->fetch_assoc()) {
                    $rule_symptoms = explode(',', $rule['symptom_combination']);
                    $matched_symptoms = array_intersect($selected_symptoms, $rule_symptoms);
                    $match_count = count($matched_symptoms);

                    if ($match_count >= 1) {
                        $score = $match_count / count($rule_symptoms);
                        if ($score > $best_score) {
                            $best_score = $score;
                            $best_match = $rule;
                            $best_match['matched_count'] = $match_count;
                            $best_match['total_in_rule'] = count($rule_symptoms);
                        }
                    }
                }

                if ($best_match !== null) {
                    $confidence = round($best_score * 100);
                    $disease    = $best_match['possible_disease'];
                    $base_advice = $best_match['advice'];
                    $rec_spec   = $best_match['recommended_specialization'] ?? 'General Physician';
                    $is_emergency = intval($best_match['is_emergency'] ?? 0);
                    $first_aid_steps = $best_match['first_aid_steps'] ?? null;
                    $found      = true;
                    $matched    = $best_match['matched_count'];
                    $total      = $best_match['total_in_rule'];
                } else {
                    $confidence = 0;
                    $disease    = 'Unclear — General Checkup Needed';
                    $base_advice = 'Your symptoms do not clearly match a specific condition in our database. Please visit a general physician for a thorough examination and proper evaluation.';
                    $rec_spec   = 'General Physician';
                    $is_emergency = 0;
                    $first_aid_steps = null;
                    $found      = false;
                    $matched    = 0;
                    $total      = 0;
                }

                // --- DURATION-BASED ADVICE PREFIX LOGIC ---
                $advice_prefix = '';
                $is_emergency_condition = ($is_emergency === 1 || strpos($disease, 'Emergency') !== false || strpos($disease, 'Cardiac') !== false || in_array('chest_pain', $selected_symptoms));

                if ($days_duration >= 7) {
                    $advice_prefix = "You've had these symptoms for over a week — please see a doctor soon. ";
                } elseif ($days_duration >= 3 && $is_emergency_condition) {
                    $advice_prefix = "Given the duration and symptoms, prompt medical attention is recommended. ";
                }

                $final_advice = $advice_prefix . $base_advice;

                // Store temporary diagnosis in SESSION
                $_SESSION['pending_diagnosis'] = [
                    'disease'                    => $disease,
                    'confidence'                 => $confidence,
                    'advice'                     => $final_advice,
                    'recommended_specialization' => $rec_spec,
                    'is_emergency'               => $is_emergency,
                    'first_aid_steps'            => $first_aid_steps,
                    'days_duration'              => $days_duration,
                    'selected_symptoms'          => $selected_symptoms,
                    'text_detected_symptoms'     => $text_only_detected,
                    'symptoms_text'              => $symptoms_text,
                    'matched'                    => $matched,
                    'total'                      => $total,
                    'found'                      => $found
                ];

                unset($_SESSION['csrf_token']);
                header("Location: book-appointment.php?step=1&view_result=1");
                exit();
            }

        // =================================================
        // STEP 2 POST: DOCTOR SELECTION & SCHEDULE -> PROCEED TO STEP 3 (PAYMENT)
        // =================================================
        } elseif ($form_step === 2) {

            if (!isset($_SESSION['pending_diagnosis'])) {
                header("Location: book-appointment.php?step=1");
                exit();
            }

            $doctor_id        = intval($_POST['doctor_id'] ?? 0);
            $severity_level   = $_POST['severity_level'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';

            if ($doctor_id <= 0) {
                $error = "Please select a doctor.";
            } elseif (!in_array($severity_level, ['Emergency', 'Normal', 'Follow-up'])) {
                $error = "Please select a valid severity level.";
            } elseif (empty($appointment_time)) {
                $error = "Please select an appointment date and time.";
            } elseif (strtotime($appointment_time) <= time()) {
                $error = "The appointment date and time must be in the future.";
            } else {

                // --- Working Hours Check & Doctor Info Fetch ---
                $doc_hours_stmt = $conn->prepare(
                    "SELECT d.available_from, d.available_to, d.consultation_fee, d.specialization, d.clinic_address, d.city, u.full_name AS doctor_name 
                     FROM doctors d 
                     JOIN users u ON d.user_id = u.user_id 
                     WHERE d.doctor_id = ?"
                );
                $doc_hours_stmt->bind_param("i", $doctor_id);
                $doc_hours_stmt->execute();
                $doc_hours = $doc_hours_stmt->get_result()->fetch_assoc();
                $doc_hours_stmt->close();

                if (!$doc_hours) {
                    $error = "Selected doctor not found.";
                } else {
                    $requested_time_only = date('H:i:s', strtotime($appointment_time));
                    $hours_ok = true;
                    if (!empty($doc_hours['available_from']) && !empty($doc_hours['available_to'])) {
                        if ($requested_time_only < $doc_hours['available_from'] || $requested_time_only > $doc_hours['available_to']) {
                            $hours_ok = false;
                            $from_fmt = date('g:i A', strtotime($doc_hours['available_from']));
                            $to_fmt   = date('g:i A', strtotime($doc_hours['available_to']));
                            $error = "This doctor is only available from {$from_fmt} to {$to_fmt}. Please choose a time within those hours.";
                        }
                    }

                    if ($hours_ok) {
                        // --- 5-Minute Buffer Gap + Exact Duplicate Check ---
                        $requested_ts = strtotime($appointment_time);
                        $buffer_seconds = 300; // 5 minutes
                        $window_start = date('Y-m-d H:i:s', $requested_ts - $buffer_seconds);
                        $window_end   = date('Y-m-d H:i:s', $requested_ts + $buffer_seconds);

                        $conflict_stmt = $conn->prepare(
                            "SELECT a.appointment_time, u.full_name AS doctor_name
                             FROM appointments a
                             JOIN doctors d ON a.doctor_id = d.doctor_id
                             JOIN users u ON d.user_id = u.user_id
                             WHERE a.doctor_id = ?
                               AND a.appointment_time BETWEEN ? AND ?
                               AND a.status IN ('Pending', 'Confirmed')
                             ORDER BY a.appointment_time ASC
                             LIMIT 1"
                        );
                        $conflict_stmt->bind_param("iss", $doctor_id, $window_start, $window_end);
                        $conflict_stmt->execute();
                        $conflict_result = $conflict_stmt->get_result();

                        if ($conflict_result->num_rows > 0) {
                            $conflict_row = $conflict_result->fetch_assoc();
                            $conflict_ts  = strtotime($conflict_row['appointment_time']);
                            $next_available_ts = $conflict_ts + $buffer_seconds;
                            $next_available_fmt = date('g:i A', $next_available_ts);
                            $doc_name_display = $conflict_row['doctor_name'];

                            if ($appointment_time === $conflict_row['appointment_time']) {
                                $error = "This exact time slot is already booked for Dr. {$doc_name_display}. The next available slot is {$next_available_fmt}.";
                            } else {
                                $error = "This time is too close to an existing appointment for Dr. {$doc_name_display}. The next available slot is {$next_available_fmt}.";
                            }
                            $conflict_stmt->close();
                        } else {
                            $conflict_stmt->close();

                            // Calculate billing details (same 20% insurance discount formula as billing.php)
                            $consultation_fee = floatval($doc_hours['consultation_fee']);
                            
                            // Check patient insurance
                            $ins_stmt = $conn->prepare("SELECT insurance_number FROM patients WHERE patient_id = ?");
                            $ins_stmt->bind_param("i", $patient_id);
                            $ins_stmt->execute();
                            $ins_row = $ins_stmt->get_result()->fetch_assoc();
                            $ins_stmt->close();

                            $has_insurance = !empty($ins_row['insurance_number']);
                            $insurance_discount_percent = $has_insurance ? 20.00 : 0.00;
                            $discount_amount = ($consultation_fee * $insurance_discount_percent) / 100;
                            $total_payable   = $consultation_fee - $discount_amount;

                            // Save booking parameters into SESSION — NO APPOINTMENT ROW INSERTED YET (Rule 3)
                            $_SESSION['pending_booking'] = [
                                'doctor_id'          => $doctor_id,
                                'doctor_name'        => $doc_hours['doctor_name'],
                                'specialization'     => $doc_hours['specialization'],
                                'clinic_address'     => $doc_hours['clinic_address'],
                                'city'               => $doc_hours['city'],
                                'appointment_time'   => $appointment_time,
                                'severity_level'     => $severity_level,
                                'consultation_fee'   => $consultation_fee,
                                'has_insurance'      => $has_insurance,
                                'insurance_number'   => $ins_row['insurance_number'] ?? '',
                                'discount_percent'   => $insurance_discount_percent,
                                'discount_amount'    => $discount_amount,
                                'total_payable'      => $total_payable
                            ];

                            unset($_SESSION['csrf_token']);
                            header("Location: book-appointment.php?step=3");
                            exit();
                        }
                    }
                }
            }

        // =================================================
        // STEP 3 POST: PAYMENT CONFIRMATION & APPOINTMENT FINALIZATION
        // =================================================
        } elseif ($form_step === 3) {

            if (!isset($_SESSION['pending_diagnosis']) || !isset($_SESSION['pending_booking'])) {
                header("Location: book-appointment.php?step=1");
                exit();
            }

            $booking = $_SESSION['pending_booking'];
            $pending = $_SESSION['pending_diagnosis'];

            $doctor_id        = $booking['doctor_id'];
            $severity_level   = $booking['severity_level'];
            $appointment_time = $booking['appointment_time'];
            $payment_method   = trim($_POST['payment_method'] ?? 'Cash at Reception');

            // --- 5-Minute Buffer Gap + Exact Duplicate Check (Final Verification) ---
            $requested_ts = strtotime($appointment_time);
            $buffer_seconds = 300; // 5 minutes
            $window_start = date('Y-m-d H:i:s', $requested_ts - $buffer_seconds);
            $window_end   = date('Y-m-d H:i:s', $requested_ts + $buffer_seconds);

            $conflict_stmt = $conn->prepare(
                "SELECT appointment_id FROM appointments 
                 WHERE doctor_id = ? 
                   AND appointment_time BETWEEN ? AND ? 
                   AND status IN ('Pending', 'Confirmed') 
                 LIMIT 1"
            );
            $conflict_stmt->bind_param("iss", $doctor_id, $window_start, $window_end);
            $conflict_stmt->execute();
            if ($conflict_stmt->get_result()->num_rows > 0) {
                $conflict_stmt->close();
                $error = "This appointment time slot was just taken. Please return to Step 2 to select another time slot.";
            } else {
                $conflict_stmt->close();

                $symptoms_str      = implode(',', $pending['selected_symptoms']);
                $symptoms_text_raw = !empty($pending['symptoms_text']) ? $pending['symptoms_text'] : null;
                $diagnosed_disease = $pending['disease'];

                // Insert into appointments table ONLY AFTER payment is confirmed (Rule 3)
                $insert_appt = $conn->prepare(
                    "INSERT INTO appointments (patient_id, doctor_id, severity_level, appointment_time, status, symptoms_selected, symptoms_text, diagnosed_disease)
                     VALUES (?, ?, ?, ?, 'Confirmed', ?, ?, ?)"
                );
                $insert_appt->bind_param("iisssss", $patient_id, $doctor_id, $severity_level, $appointment_time, $symptoms_str, $symptoms_text_raw, $diagnosed_disease);

                if ($insert_appt->execute()) {
                    $new_appt_id = $conn->insert_id;
                    $insert_appt->close();

                    // Generate unique, human-readable Token Number: TK-YYYYMMDD-XXXX (e.g. TK-20260901-0017)
                    $token_number = 'TK-' . date('Ymd') . '-' . str_pad($new_appt_id, 4, '0', STR_PAD_LEFT);

                    $upd_tok = $conn->prepare("UPDATE appointments SET token_number = ? WHERE appointment_id = ?");
                    $upd_tok->bind_param("si", $token_number, $new_appt_id);
                    $upd_tok->execute();
                    $upd_tok->close();

                    // Pass booking and token details to PRG redirect
                    $pending['appointment_id']   = $new_appt_id;
                    $pending['token_number']     = $token_number;
                    $pending['doctor_name']      = $booking['doctor_name'];
                    $pending['specialization']   = $booking['specialization'];
                    $pending['clinic_address']   = $booking['clinic_address'];
                    $pending['city']             = $booking['city'];
                    $pending['appointment_time'] = $booking['appointment_time'];
                    $pending['severity_level']   = $booking['severity_level'];
                    $pending['consultation_fee'] = $booking['consultation_fee'];
                    $pending['discount_amount']  = $booking['discount_amount'];
                    $pending['total_payable']    = $booking['total_payable'];
                    $pending['payment_method']   = $payment_method;

                    $_SESSION['last_diagnosis'] = $pending;

                    // Log activity
                    logActivity($_SESSION['user_id'], 'Booked Appointment', "Appt #{$new_appt_id} (Token: {$token_number}, Paid: Rs. " . number_format($booking['total_payable'], 2) . ")");

                    // Clear temporary pending states
                    unset($_SESSION['pending_diagnosis']);
                    unset($_SESSION['pending_booking']);
                    unset($_SESSION['csrf_token']);

                    // Redirect to final result page
                    header("Location: appointment-result.php");
                    exit();
                } else {
                    $error = "Failed to complete appointment booking. Please try again.";
                    $insert_appt->close();
                }
            }
        }
    }
    unset($_SESSION['csrf_token']);
}

$csrf_token = generateCsrfToken();
$showing_step_1_result  = (isset($_GET['view_result']) && isset($_SESSION['pending_diagnosis']));
$showing_step_2_form    = ($requested_step === 2 && isset($_SESSION['pending_diagnosis']));
$showing_step_3_payment = ($requested_step === 3 && isset($_SESSION['pending_diagnosis']) && isset($_SESSION['pending_booking']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment — Smart Healthcare System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🏥</span> Smart Healthcare
    </div>
    <ul class="navbar-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="book-appointment.php">📋 Book Appointment</a></li>
        <li><a href="my-appointments.php">📅 My Appointments</a></li>
        <li><a href="billing.php">💰 My Bills</a></li>
        <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
    </ul>
</nav>

<div class="container">

    <h1 class="page-title">🩺 Symptom Assessment & Appointment Booking</h1>

    <!-- Step Progress Indicator -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="flex: 1; padding: 0.75rem 1rem; border-radius: var(--radius); text-align: center; font-weight: 600; 
                    background: <?php echo (!$showing_step_2_form) ? 'var(--primary)' : 'var(--gray-200)'; ?>; 
                    color: <?php echo (!$showing_step_2_form) ? 'white' : 'var(--gray-700)'; ?>;">
            Step 1: Symptom Assessment
        </div>
        <div style="flex: 1; padding: 0.75rem 1rem; border-radius: var(--radius); text-align: center; font-weight: 600; 
                    background: <?php echo ($showing_step_2_form) ? 'var(--primary)' : 'var(--gray-200)'; ?>; 
                    color: <?php echo ($showing_step_2_form) ? 'white' : 'var(--gray-700)'; ?>;">
            Step 2: Choose Doctor & Time
        </div>
    </div>

    <!-- Show Error Banner -->
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- =====================================================
         VIEW 1B: STEP 1 RESULT — Assessment Result & Decision
         ===================================================== -->
    <?php if ($showing_step_1_result): ?>
        <?php $diag = $_SESSION['pending_diagnosis']; ?>

        <div class="diagnosis-result">
            <h3>📊 Preliminary Assessment Result</h3>

            <p style="font-size: 1.15rem; margin-bottom: 0.5rem;">
                <strong>Possible Concern:</strong> 
                <?php echo htmlspecialchars($diag['disease']); ?>
            </p>

            <p style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--gray-600);">
                <strong>Duration of Symptoms:</strong> <?php echo intval($diag['days_duration']); ?> day(s)
            </p>

            <?php if ($diag['found']): ?>
                <?php
                $conf = (int)$diag['confidence'];
                $conf_badge_class = 'badge-green';
                if ($conf < 40) $conf_badge_class = 'badge-grey';
                elseif ($conf < 70) $conf_badge_class = 'badge-orange';
                ?>
                <p class="confidence" style="margin-top: 0.75rem;">
                    <span class="badge <?php echo $conf_badge_class; ?>" style="font-size: 0.95rem; padding: 0.35rem 0.85rem;">
                        Confidence Level: <?php echo $conf; ?>%
                    </span>
                    <span style="color: var(--gray-600); margin-left: 0.5rem; font-size: 0.9rem;">
                        (<?php echo $diag['matched']; ?> of <?php echo $diag['total']; ?> symptoms matched)
                    </span>
                </p>
            <?php endif; ?>

            <?php if (!empty($diag['text_detected_symptoms'])): ?>
                <?php
                $formatted_text_syms = array_map(function($s) {
                    return ucwords(str_replace('_', ' ', $s));
                }, $diag['text_detected_symptoms']);
                ?>
                <div style="background-color: #f0f9ff; border: 1.5px solid #bae6fd; color: #0369a1; padding: 0.65rem 1rem; border-radius: var(--radius); margin-top: 0.85rem; margin-bottom: 0.85rem; font-size: 0.9rem;">
                    💡 We also detected: <strong><?php echo htmlspecialchars(implode(', ', $formatted_text_syms)); ?></strong> from your written description.
                </div>
            <?php endif; ?>

            <?php if (!empty($diag['is_emergency']) && !empty($diag['first_aid_steps'])): ?>
                <div style="background-color: #fef2f2; border: 2px solid #fca5a5; border-left: 6px solid #dc2626; color: #991b1b; padding: 1.25rem; border-radius: var(--radius); margin-top: 1.25rem; margin-bottom: 1.25rem;">
                    <h4 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                        🚨 EMERGENCY — Seek immediate medical attention
                    </h4>
                    <p style="font-weight: 600; margin-bottom: 0.5rem; color: #7f1d1d;">Recommended First-Aid & Safety Steps:</p>
                    <div style="font-size: 0.93rem; line-height: 1.6; color: #7f1d1d; white-space: pre-line;">
                        <?php echo htmlspecialchars($diag['first_aid_steps']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="advice">
                <strong>📝 Advice:</strong><br>
                <?php echo htmlspecialchars($diag['advice']); ?>
            </div>
        </div>

        <!-- MEDICAL SAFETY DISCLAIMER -->
        <div class="disclaimer">
            <strong>⚠️ Important Medical Disclaimer</strong>
            This is a preliminary assessment only and does not replace a qualified doctor's diagnosis.
            Please consult a healthcare professional for proper medical evaluation and treatment.
        </div>

        <!-- Booking Decision Card -->
        <div class="card" style="text-align: center; padding: 2rem;">
            <h3 style="color: var(--gray-900); margin-bottom: 0.75rem;">Would you like to book a doctor's appointment now?</h3>
            <p style="color: var(--gray-600); margin-bottom: 1.5rem;">You can choose your preferred doctor, schedule date, and time slot in Step 2.</p>
            
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="book-appointment.php?step=2" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1.05rem;">
                    📅 Yes, Book an Appointment
                </a>
                <a href="book-appointment.php?cancel_pending=1" class="btn btn-secondary" style="padding: 0.85rem 1.75rem; font-size: 1.05rem;">
                    ❌ No, Not Now
                </a>
            </div>
        </div>

    <!-- =====================================================
         VIEW 2: STEP 2 FORM — Doctor & Schedule Selection
         ===================================================== -->
    <?php elseif ($showing_step_2_form): ?>
        <?php 
        $diag = $_SESSION['pending_diagnosis']; 
        $rec_spec = $diag['recommended_specialization'] ?? 'General Physician';

        // Categorize doctors: recommended vs others
        $doctors_list = [];
        $doctors_result->data_seek(0);
        while ($doc = $doctors_result->fetch_assoc()) {
            $doctors_list[] = $doc;
        }

        $recommended_doctors = [];
        $other_doctors       = [];
        foreach ($doctors_list as $doc) {
            if (strcasecmp(trim($doc['specialization']), trim($rec_spec)) === 0) {
                $recommended_doctors[] = $doc;
            } else {
                $other_doctors[] = $doc;
            }
        }

        $has_recommended = (count($recommended_doctors) > 0);
        $sorted_doctors  = array_merge($recommended_doctors, $other_doctors);
        ?>

        <!-- Read-Only Assessment Summary Banner -->
        <div class="card" style="background-color: #eff6ff; border-color: #bfdbfe; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="color: #1e40af; margin-bottom: 0.3rem;">📋 Assessment Summary</h4>
                    <p style="color: #1e3a8a; margin: 0; font-size: 0.95rem;">
                        <strong>Condition:</strong> <?php echo htmlspecialchars($diag['disease']); ?> | 
                        <strong>Duration:</strong> <?php echo intval($diag['days_duration']); ?> day(s) |
                        <strong>Recommended Specialist:</strong> <?php echo htmlspecialchars($rec_spec); ?>
                    </p>
                </div>
                <a href="book-appointment.php?step=1" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                    ✏️ Re-assess Symptoms
                </a>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="form_step" value="2">

            <div class="card">
                <div class="card-header">📅 Step 2: Choose Doctor & Schedule Appointment</div>

                <?php if ($has_recommended): ?>
                    <div style="background-color: #f0fdf4; border: 1.5px solid #86efac; padding: 0.65rem 1rem; border-radius: var(--radius); margin-bottom: 1.25rem; color: #166534; font-size: 0.9rem;">
                        💡 Based on your symptoms, we recommend a <strong><?php echo htmlspecialchars($rec_spec); ?></strong>. You're free to choose any doctor below.
                    </div>
                <?php endif; ?>

                <!-- Doctor Selection Dropdown -->
                <div class="form-group">
                    <label for="doctor_id">Select Doctor <span class="required">*</span></label>
                    <select id="doctor_id" name="doctor_id" required onchange="updateDoctorAddress(this)">
                        <option value="" data-address="">-- Choose a Doctor --</option>
                        <?php 
                        foreach ($sorted_doctors as $doc):
                            $is_rec = (strcasecmp(trim($doc['specialization']), trim($rec_spec)) === 0);
                            $city_text = !empty($doc['city']) ? ' - ' . htmlspecialchars($doc['city']) : '';
                            $full_address = trim(($doc['clinic_address'] ?? '') . (!empty($doc['city']) ? ', ' . $doc['city'] : ''));
                        ?>
                            <option value="<?php echo $doc['doctor_id']; ?>"
                                data-address="<?php echo htmlspecialchars($full_address); ?>"
                                data-from="<?php echo date('g:i A', strtotime($doc['available_from'])); ?>"
                                data-to="<?php echo date('g:i A', strtotime($doc['available_to'])); ?>"
                                <?php if (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doc['doctor_id']) echo 'selected'; ?>>
                                <?php echo $is_rec ? '⭐ ' : ''; ?>Dr. <?php echo htmlspecialchars($doc['full_name']); ?> 
                                (<?php echo htmlspecialchars($doc['specialization']); ?>)<?php echo $city_text; ?><?php echo $is_rec ? ' - ⭐ Recommended' : ''; ?>
                                — Rs. <?php echo number_format($doc['consultation_fee']); ?> 
                                [<?php echo date('h:i A', strtotime($doc['available_from'])); ?> - <?php echo date('h:i A', strtotime($doc['available_to'])); ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="doctor_address_box" style="display:none; margin-top: 0.6rem; background: #f8fafc; border: 1.5px solid #cbd5e1; padding: 0.75rem 1rem; border-radius: var(--radius); color: #334155; font-size: 0.9rem;">
                        📍 <strong>Clinic Location:</strong> <span id="doctor_address_text"></span>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Severity Level -->
                    <div class="form-group">
                        <label for="severity_level">Severity Level <span class="required">*</span></label>
                        <select id="severity_level" name="severity_level" required>
                            <option value="">-- Select Severity --</option>
                            <option value="Emergency" <?php if (isset($_POST['severity_level']) && $_POST['severity_level'] === 'Emergency') echo 'selected'; ?>>
                                🔴 Emergency
                            </option>
                            <option value="Normal" <?php if (isset($_POST['severity_level']) && $_POST['severity_level'] === 'Normal') echo 'selected'; ?>>
                                🟡 Normal
                            </option>
                            <option value="Follow-up" <?php if (isset($_POST['severity_level']) && $_POST['severity_level'] === 'Follow-up') echo 'selected'; ?>>
                                🟢 Follow-up
                            </option>
                        </select>
                    </div>

                    <!-- Appointment Date & Time -->
                    <div class="form-group">
                        <label for="appointment_time">Appointment Date & Time <span class="required">*</span></label>
                        <input type="datetime-local" id="appointment_time" name="appointment_time" 
                               value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? ''); ?>" required>
                        <div id="time_availability_warning" style="display:none; margin-top: 0.5rem; background-color: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b; padding: 0.5rem 0.75rem; border-radius: var(--radius); font-size: 0.88rem;"></div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.9rem; font-size: 1.05rem;">
                        💳 Proceed to Payment & Confirmation →
                    </button>
                    <a href="book-appointment.php?cancel_pending=1" class="btn btn-secondary" style="padding: 0.9rem 1.5rem; text-align: center;">
                        ❌ Cancel
                    </a>
                </div>
            </div>
        </form>

    <!-- =====================================================
         VIEW 3: STEP 3 FORM — Payment Confirmation & Token Generation
         ===================================================== -->
    <?php elseif ($showing_step_3_payment): 
        $booking = $_SESSION['pending_booking'];
        $diag    = $_SESSION['pending_diagnosis'];
    ?>

        <!-- Step Progress Breadcrumb -->
        <div class="card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem; background: #f8fafc; border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.92rem; font-weight: 600; flex-wrap: wrap; gap: 0.5rem;">
                <span style="color: #059669;">✔️ Step 1: Assessment</span>
                <span style="color: #94a3b8;">➔</span>
                <span style="color: #059669;">✔️ Step 2: Doctor & Schedule</span>
                <span style="color: #94a3b8;">➔</span>
                <span style="color: var(--primary);">💳 Step 3: Payment & Confirmation</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">💳 Step 3: Confirm Payment & Generate Token</div>

            <!-- Booking Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: var(--radius); padding: 1.25rem;">
                    <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.05em;">Doctor Details</div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: var(--gray-900); margin-top: 0.35rem;">
                        Dr. <?php echo htmlspecialchars($booking['doctor_name']); ?>
                    </div>
                    <div style="color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($booking['specialization']); ?></div>
                    <div style="color: #64748b; font-size: 0.85rem; margin-top: 0.35rem;">
                        📍 <?php echo htmlspecialchars($booking['clinic_address'] . (!empty($booking['city']) ? ', ' . $booking['city'] : '')); ?>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: var(--radius); padding: 1.25rem;">
                    <div style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: 0.05em;">Appointment Time</div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--gray-900); margin-top: 0.35rem;">
                        📅 <?php echo date('l, F j, Y', strtotime($booking['appointment_time'])); ?>
                    </div>
                    <div style="color: var(--primary); font-size: 1rem; font-weight: 700; margin-top: 0.2rem;">
                        ⏰ <?php echo date('h:i A', strtotime($booking['appointment_time'])); ?>
                    </div>
                    <div style="margin-top: 0.35rem;">
                        <span class="badge badge-<?php echo ($booking['severity_level'] === 'Emergency') ? 'red' : (($booking['severity_level'] === 'Follow-up') ? 'grey' : 'blue'); ?>">
                            <?php echo htmlspecialchars($booking['severity_level']); ?> Priority
                        </span>
                    </div>
                </div>
            </div>

            <!-- Payment Fee Breakdown Box -->
            <div style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.75rem;">
                <h4 style="margin-top: 0; margin-bottom: 1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    🧾 Consultation Fee Breakdown
                </h4>

                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.95rem; color: #475569;">
                    <span>Doctor Consultation Fee:</span>
                    <span style="font-weight: 600;">Rs. <?php echo number_format($booking['consultation_fee'], 2); ?></span>
                </div>

                <?php if ($booking['has_insurance']): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.95rem; color: #059669;">
                        <span>🛡️ Insurance Coverage (20% Discount):</span>
                        <span style="font-weight: 700;">- Rs. <?php echo number_format($booking['discount_amount'], 2); ?></span>
                    </div>
                    <small style="color: #059669; display: block; margin-top: -0.2rem; margin-bottom: 0.35rem;">
                        Policy #<?php echo htmlspecialchars($booking['insurance_number']); ?> automatically applied
                    </small>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; font-size: 0.95rem; color: #64748b;">
                        <span>Insurance Coverage:</span>
                        <span>None (Not Insured)</span>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #0f172a; margin-top: 0.75rem; padding-top: 0.75rem;">
                    <span style="font-size: 1.15rem; font-weight: 800; color: #0f172a;">TOTAL PAYABLE:</span>
                    <span style="font-size: 1.45rem; font-weight: 800; color: #059669;">
                        Rs. <?php echo number_format($booking['total_payable'], 2); ?>
                    </span>
                </div>
            </div>

            <!-- Payment Confirmation Form -->
            <form method="POST" action="book-appointment.php?step=3">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="form_step" value="3">

                <!-- Payment Method (Demo) -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="font-weight: 700; color: var(--gray-900); margin-bottom: 0.75rem; display: block;">
                        Select Payment Method:
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                        <label style="border: 1.5px solid #cbd5e1; border-radius: var(--radius); padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #f8fafc;">
                            <input type="radio" name="payment_method" value="Cash at Reception" checked style="accent-color: var(--primary);">
                            <div>
                                <div style="font-weight: 700; color: #1e293b;">💵 Cash at Clinic</div>
                                <small style="color: #64748b;">Pay at counter upon arrival</small>
                            </div>
                        </label>

                        <label style="border: 1.5px solid #cbd5e1; border-radius: var(--radius); padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #f8fafc;">
                            <input type="radio" name="payment_method" value="Credit/Debit Card (Demo)" style="accent-color: var(--primary);">
                            <div>
                                <div style="font-weight: 700; color: #1e293b;">💳 Debit / Credit Card</div>
                                <small style="color: #64748b;">Simulated instant payment</small>
                            </div>
                        </label>

                        <label style="border: 1.5px solid #cbd5e1; border-radius: var(--radius); padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #f8fafc;">
                            <input type="radio" name="payment_method" value="JazzCash / EasyPaisa (Demo)" style="accent-color: var(--primary);">
                            <div>
                                <div style="font-weight: 700; color: #1e293b;">📱 Mobile Wallet</div>
                                <small style="color: #64748b;">JazzCash / EasyPaisa Demo</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary" style="flex: 2; min-width: 240px; padding: 1rem 1.5rem; font-size: 1.1rem; font-weight: 700;">
                        ✅ Confirm Payment & Book Appointment
                    </button>
                    <a href="book-appointment.php?step=2" class="btn btn-secondary" style="flex: 1; min-width: 160px; padding: 1rem; text-align: center;">
                        ← Back to Schedule
                    </a>
                </div>
            </form>
        </div>

    <!-- =====================================================
         VIEW 1A: STEP 1 FORM — Symptom Assessment Input
         ===================================================== -->
    <?php else: ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="form_step" value="1">

            <div class="card">
                <div class="card-header">🔍 Step 1: Select Your Symptoms & Duration</div>
                <p style="color: #64748b; margin-bottom: 1rem;">
                    Select all symptoms you are currently experiencing and specify how long you have had them.
                </p>

                <!-- Symptom Duration Field -->
                <div class="form-group" style="margin-bottom: 1.5rem; max-width: 320px;">
                    <label for="days_duration">How many days have you had these symptoms? <span class="required">*</span></label>
                    <input type="number" id="days_duration" name="days_duration" 
                           value="<?php echo htmlspecialchars($_POST['days_duration'] ?? '1'); ?>" 
                           min="1" max="90" required style="font-size: 1.05rem; padding: 0.65rem;">
                </div>

                <!-- Checkbox Grid -->
                <div class="checkbox-grid">
                    <?php foreach ($all_symptoms as $symptom_key => $symptom_label): ?>
                        <label class="checkbox-item">
                            <input type="checkbox" name="symptoms[]" value="<?php echo htmlspecialchars($symptom_key); ?>"
                                <?php 
                                if (isset($_POST['symptoms']) && in_array($symptom_key, $_POST['symptoms'])) {
                                    echo 'checked';
                                }
                                ?>>
                            <span><?php echo htmlspecialchars($symptom_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Free-Text Symptom Description (Optional) -->
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="symptoms_text" style="font-weight: 600; color: var(--gray-800);">
                        Describe your symptoms in your own words (optional):
                    </label>
                    <textarea id="symptoms_text" name="symptoms_text" rows="3" maxlength="300" 
                              placeholder="e.g. I have a fever and headache since yesterday or bukhar aur sar dard hai"
                              style="width: 100%; padding: 0.75rem; border-radius: var(--radius); border: 1.5px solid var(--gray-300); font-family: inherit; font-size: 0.95rem; resize: vertical;"><?php echo htmlspecialchars($_POST['symptoms_text'] ?? ''); ?></textarea>
                    <small style="color: var(--gray-500); display: block; margin-top: 0.35rem;">
                        💡 You can type in English or Roman Urdu. Key symptoms mentioned will be automatically combined with your checkbox selections.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-3" style="padding: 0.95rem; font-size: 1.1rem;">
                    🔍 Assess Symptoms & Proceed
                </button>
            </div>
        </form>

    <?php endif; ?>

</div>

<script>
function updateDoctorAddress(select) {
    var option = select.options[select.selectedIndex];
    var address = option ? option.getAttribute('data-address') : null;
    var box = document.getElementById('doctor_address_box');
    var text = document.getElementById('doctor_address_text');
    
    if (address && address.trim() !== '') {
        text.textContent = address;
        box.style.display = 'block';
    } else if (box) {
        box.style.display = 'none';
    }
}

function checkAvailabilityRealtime() {
    var doctorSelect = document.getElementById('doctor_id');
    var timeInput    = document.getElementById('appointment_time');
    var warningBox   = document.getElementById('time_availability_warning');
    
    if (!doctorSelect || !timeInput || !warningBox) return;
    
    var docId = doctorSelect.value;
    var apptTime = timeInput.value;
    
    if (!docId || !apptTime) {
        warningBox.style.display = 'none';
        return;
    }
    
    fetch('check-availability.php?doctor_id=' + encodeURIComponent(docId) + '&appointment_time=' + encodeURIComponent(apptTime))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.available === false) {
                var msg = '';
                if (data.reason === 'outside_hours') {
                    msg = '\u26a0\ufe0f ' + (data.message || 'Outside working hours') + '.';
                } else if (data.reason === 'too_close') {
                    msg = '\u26a0\ufe0f Too close to another booking. Next available: <strong>' + data.next_available + '</strong>';
                } else if (data.reason === 'exact_conflict') {
                    msg = '\u26a0\ufe0f This exact time is already booked. Next available: <strong>' + data.next_available + '</strong>';
                } else {
                    msg = '\u26a0\ufe0f This time slot is not available.';
                }
                warningBox.innerHTML = msg;
                warningBox.style.display = 'block';
            } else {
                warningBox.innerHTML = '';
                warningBox.style.display = 'none';
            }
        })
        .catch(function(err) {
            console.error('Availability check failed:', err);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('doctor_id');
    var timeInput = document.getElementById('appointment_time');

    if (select) {
        select.addEventListener('change', function() {
            updateDoctorAddress(this);
            checkAvailabilityRealtime();
        });
        if (select.value !== '') {
            updateDoctorAddress(select);
        }
    }

    if (timeInput) {
        timeInput.addEventListener('change', checkAvailabilityRealtime);
        timeInput.addEventListener('input', checkAvailabilityRealtime);
        if (timeInput.value !== '') {
            checkAvailabilityRealtime();
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>
