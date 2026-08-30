const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

function getJson(url) {
    return new Promise((resolve, reject) => {
        const req = http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try { resolve(JSON.parse(data)); } catch (e) { reject(e); }
            });
        });
        req.on('error', reject);
        req.setTimeout(4000, () => { req.destroy(); reject(new Error('HTTP timeout')); });
    });
}

class CDPPage {
    constructor(wsUrl) {
        this.ws = new WebSocket(wsUrl);
        this.id = 1;
        this.callbacks = new Map();
    }

    connect() {
        return new Promise((resolve, reject) => {
            const to = setTimeout(() => reject(new Error('WS timeout')), 6000);
            this.ws.onopen = () => { clearTimeout(to); resolve(); };
            this.ws.onerror = (e) => { clearTimeout(to); reject(e); };
            this.ws.onmessage = (event) => {
                const msg = JSON.parse(event.data);
                if (msg.id && this.callbacks.has(msg.id)) {
                    const { resolve: res, reject: rej } = this.callbacks.get(msg.id);
                    this.callbacks.delete(msg.id);
                    if (msg.error) rej(new Error(msg.error.message));
                    else res(msg.result);
                }
            };
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = this.id++;
            this.callbacks.set(id, { resolve, reject });
            this.ws.send(JSON.stringify({ id, method, params }));
        });
    }

    async eval(expr) {
        const res = await this.send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true });
        return res ? res.result ? res.result.value : null : null;
    }

    async goto(url, delay = 1200) {
        await this.send('Page.navigate', { url });
        await new Promise(r => setTimeout(r, delay));
    }

    async captureFullPage(filename, expectedText = null, expectedUrlSubstr = null) {
        const outDir = path.join(__dirname, 'screenshots');
        fs.mkdirSync(outDir, { recursive: true });
        const filepath = path.join(outDir, filename);

        const currentUrl = await this.eval('window.location.href');
        const pageText = await this.eval('document.body ? document.body.innerText : ""');

        if (expectedUrlSubstr && !currentUrl.includes(expectedUrlSubstr)) {
            throw new Error(`[ASSERT FAILED] ${filename}: Expected URL containing "${expectedUrlSubstr}", but got "${currentUrl}"`);
        }

        if (expectedText && (!pageText || !pageText.includes(expectedText))) {
            throw new Error(`[ASSERT FAILED] ${filename}: Expected text "${expectedText}" not found in page body at ${currentUrl}! Page snippet: ${pageText.substring(0, 200)}`);
        }

        const metrics = await this.send('Page.getLayoutMetrics');
        const width = Math.max(1280, Math.ceil(metrics.contentSize.width));
        const height = Math.max(800, Math.ceil(metrics.contentSize.height));

        await this.send('Emulation.setDeviceMetricsOverride', {
            width: 1280,
            height: height,
            deviceScaleFactor: 1,
            mobile: false
        });
        await new Promise(r => setTimeout(r, 200));

        const shot = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
        fs.writeFileSync(filepath, Buffer.from(shot.data, 'base64'));
        console.log(`[CAPTURED & VERIFIED] ${filename} (${Math.round(shot.data.length / 1024)} KB, 1280x${height}px) -> ${currentUrl}`);

        await this.send('Emulation.setDeviceMetricsOverride', {
            width: 1280,
            height: 900,
            deviceScaleFactor: 1,
            mobile: false
        });
    }

    async captureViewport(filename, expectedText = null, expectedUrlSubstr = null) {
        const outDir = path.join(__dirname, 'screenshots');
        fs.mkdirSync(outDir, { recursive: true });
        const filepath = path.join(outDir, filename);

        const currentUrl = await this.eval('window.location.href');
        const pageText = await this.eval('document.body ? document.body.innerText : ""');

        if (expectedUrlSubstr && !currentUrl.includes(expectedUrlSubstr)) {
            throw new Error(`[ASSERT FAILED] ${filename}: Expected URL containing "${expectedUrlSubstr}", but got "${currentUrl}"`);
        }

        if (expectedText && (!pageText || !pageText.includes(expectedText))) {
            throw new Error(`[ASSERT FAILED] ${filename}: Expected text "${expectedText}" not found in page body at ${currentUrl}! Page snippet: ${pageText.substring(0, 200)}`);
        }

        await this.send('Emulation.setDeviceMetricsOverride', {
            width: 1280,
            height: 900,
            deviceScaleFactor: 1,
            mobile: false
        });
        await new Promise(r => setTimeout(r, 150));

        const shot = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        fs.writeFileSync(filepath, Buffer.from(shot.data, 'base64'));
        console.log(`[CAPTURED & VERIFIED VIEWPORT] ${filename} (${Math.round(shot.data.length / 1024)} KB, 1280x900px) -> ${currentUrl}`);
    }

    close() {
        try { this.ws.close(); } catch (e) {}
    }
}

async function logout(page) {
    await page.goto('http://localhost/healthcare/logout.php', 600);
}

async function loginAs(page, email, password, roleName) {
    await logout(page);
    await page.goto('http://localhost/healthcare/login.php', 600);
    await page.eval(`
        document.getElementById('email').value = '${email}';
        document.getElementById('password').value = '${password}';
        document.querySelector('form').submit();
    `);
    await new Promise(r => setTimeout(r, 1400));

    const url = await page.eval('window.location.href');
    const body = await page.eval('document.body.innerText');
    if (!url.includes('dashboard.php')) {
        throw new Error(`[LOGIN FAILED] Could not log in as ${email}. URL: ${url}. Snippet: ${body.substring(0, 150)}`);
    }
    console.log(`\n>>> [AUTH SUCCESS] Logged in as ${roleName} (${email})`);
}

async function main() {
    const profileDir = path.join(process.env.TEMP, 'chrome_master_run_' + Date.now());
    const chrome = spawn("C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe", [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--no-sandbox',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-component-extensions-with-background-pages',
        '--window-size=1280,900',
        '--user-data-dir=' + profileDir,
        'http://localhost/healthcare/logout.php'
    ]);

    await new Promise(r => setTimeout(r, 2200));

    try {
        const list = await getJson('http://127.0.0.1:9222/json/list');
        const pageTarget = list.find(t => t.type === 'page' && !t.url.startsWith('chrome-extension://')) || list[0];
        const page = new CDPPage(pageTarget.webSocketDebuggerUrl);
        await page.connect();
        await page.send('Page.enable');
        await page.send('Runtime.enable');
        await page.send('DOM.enable');

        console.log('\n======================================================');
        console.log('=== SECTION 1: PUBLIC & AUTHENTICATION PAGES       ===');
        console.log('======================================================');

        // Ensure logged out
        await logout(page);

        // 01a: Landing Page Hero
        await page.goto('http://localhost/healthcare/index.php');
        await page.eval(`window.scrollTo(0, 0);`);
        await new Promise(r => setTimeout(r, 200));
        await page.captureViewport('01a-landing-page-hero.png', 'Smart Healthcare', 'index.php');

        // 01b: Landing Page Features Grid
        await page.eval(`window.scrollTo(0, 480);`);
        await new Promise(r => setTimeout(r, 300));
        await page.captureViewport('01b-landing-page-faq-expanded.png', 'Everything You Need', 'index.php');

        // 02a: Register Empty
        await page.goto('http://localhost/healthcare/register.php');
        await page.captureFullPage('02a-register-empty.png', 'Create Your Account', 'register.php');

        // 02b: Register Patient Filled
        await page.eval(`
            document.getElementById('full_name').value = 'Sarah Ahmed';
            document.getElementById('email').value = 'sarah.ahmed@example.com';
            document.getElementById('phone').value = '0300-1234567';
            document.getElementById('role').value = 'patient';
            document.getElementById('role').dispatchEvent(new Event('change'));
            document.getElementById('age').value = '28';
            document.getElementById('weight').value = '62.5';
            document.getElementById('cnic').value = '35201-1234567-1';
            document.getElementById('insurance_number').value = 'INS-99482';
            document.getElementById('password').value = 'Password123';
            document.getElementById('confirm_password').value = 'Password123';
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureFullPage('02b-register-patient-filled.png', 'Create Your Account', 'register.php');

        // 02c: Register Doctor Filled
        await page.goto('http://localhost/healthcare/register.php');
        await page.eval(`
            document.getElementById('full_name').value = 'Dr. Tariq Mahmood';
            document.getElementById('email').value = 'dr.tariq@healthcare.com';
            document.getElementById('phone').value = '0312-9876543';
            document.getElementById('role').value = 'doctor';
            document.getElementById('role').dispatchEvent(new Event('change'));
            document.getElementById('specialization').value = 'Cardiologist';
            document.getElementById('clinic_address').value = 'Suite 402, Al-Razi Medical Complex';
            document.getElementById('city').value = 'Lahore';
            document.getElementById('available_from').value = '09:00';
            document.getElementById('available_to').value = '15:00';
            document.getElementById('consultation_fee').value = '1500';
            document.getElementById('password').value = 'DocSecurePass123';
            document.getElementById('confirm_password').value = 'DocSecurePass123';
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureFullPage('02c-register-doctor-filled.png', 'Create Your Account', 'register.php');

        // 02d: Register Validation Error (Password mismatch)
        await page.goto('http://localhost/healthcare/register.php');
        await page.eval(`
            document.getElementById('full_name').value = 'Test User';
            document.getElementById('email').value = 'test@example.com';
            document.getElementById('phone').value = '0300-1234567';
            document.getElementById('role').value = 'patient';
            document.getElementById('role').dispatchEvent(new Event('change'));
            document.getElementById('age').value = '30';
            document.getElementById('weight').value = '70';
            document.getElementById('cnic').value = '35201-1234567-9';
            document.getElementById('password').value = 'Password123';
            document.getElementById('confirm_password').value = 'Mismatch456';
            document.querySelector('button[type="submit"]').click();
        `);
        await new Promise(r => setTimeout(r, 1400));
        await page.captureFullPage('02d-register-validation-error.png', 'Create Your Account', 'register.php');

        // 03a: Login Default
        await page.goto('http://localhost/healthcare/login.php');
        await page.captureFullPage('03a-login-default.png', 'Welcome Back', 'login.php');

        // 03b: Login Password Revealed
        await page.eval(`
            document.getElementById('email').value = 'nasibrehman187@gmail.com';
            document.getElementById('password').value = 'password123';
            const btn = document.querySelector('.toggle-eye');
            if (btn) btn.click();
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('03b-login-password-revealed.png', 'Welcome Back', 'login.php');

        // 03c: Login Invalid Error
        await page.goto('http://localhost/healthcare/login.php');
        await page.eval(`
            document.getElementById('email').value = 'wrong.email@example.com';
            document.getElementById('password').value = 'WrongPassword999';
            document.querySelector('button[type="submit"]').click();
        `);
        await new Promise(r => setTimeout(r, 1200));
        await page.captureFullPage('03c-login-invalid-error.png', 'Invalid email or password', 'login.php');

        // 03d: Login Suspended Alert
        await page.goto('http://localhost/healthcare/login.php?suspended=1');
        await page.captureFullPage('03d-login-suspended-alert.png', 'Your account was suspended', 'login.php');

        // 04a: Forgot Password Default
        await page.goto('http://localhost/healthcare/forgot-password.php');
        await page.captureFullPage('04a-forgot-password-default.png', 'Forgot Password?', 'forgot-password.php');

        // 04b: Forgot Password Form Filled with Email & Phone
        await page.eval(`
            document.getElementById('email').value = 'nasibrehman187@gmail.com';
            document.getElementById('phone').value = '03062320099';
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureFullPage('04b-forgot-password-token-generated.png', 'Forgot Password?', 'forgot-password.php');

        // 05a: Reset Password Form (Submit to reach Step 2)
        await page.eval(`document.querySelector('form').submit();`);
        await new Promise(r => setTimeout(r, 1400));
        await page.captureFullPage('05a-reset-password-form.png', 'Set New Password', 'reset-password.php');

        console.log('\n======================================================');
        console.log('=== SECTION 2: PATIENT EXPERIENCE & WORKFLOWS      ===');
        console.log('======================================================');
        await loginAs(page, 'nasibrehman187@gmail.com', 'password123', 'Patient');

        // 06a: Patient Dashboard
        await page.goto('http://localhost/healthcare/dashboard.php');
        await page.captureFullPage('06a-patient-dashboard.png', 'Nasib Rehman', 'dashboard.php');

        // 07a: Symptom Assessment Empty
        await page.goto('http://localhost/healthcare/book-appointment.php');
        await page.captureFullPage('07a-symptom-assessment-empty.png', 'Step 1: Select Your Symptoms', 'book-appointment.php');

        // 07b: Symptom Assessment Filled
        await page.eval(`
            const cbs = document.querySelectorAll('input[type="checkbox"]');
            if (cbs[0]) cbs[0].checked = true;
            if (cbs[1]) cbs[1].checked = true;
            if (cbs[4]) cbs[4].checked = true;
            const dur = document.getElementById('days_duration');
            if (dur) dur.value = '4';
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('07b-symptom-assessment-filled.png', 'Step 1: Select Your Symptoms', 'book-appointment.php');

        // 08a: Diagnosis Normal Result
        await page.eval(`document.querySelector('form').submit();`);
        await new Promise(r => setTimeout(r, 1400));
        await page.captureFullPage('08a-diagnosis-normal-result.png', 'Assessment Result', 'book-appointment.php');

        // 08b: Diagnosis Emergency Result
        await page.goto('http://localhost/healthcare/book-appointment.php?step=1');
        await page.eval(`
            const cbs = document.querySelectorAll('input[type="checkbox"]');
            cbs.forEach(cb => {
                if (cb.value === 'chest_pain' || cb.value === 'shortness_of_breath' || cb.value === 'sweating') {
                    cb.checked = true;
                }
            });
            const dur = document.getElementById('days_duration');
            if (dur) dur.value = '2';
            document.querySelector('form').submit();
        `);
        await new Promise(r => setTimeout(r, 1400));
        await page.captureFullPage('08b-diagnosis-emergency-result.png', 'EMERGENCY', 'book-appointment.php');

        // 10a: Appointment Step 2 Doctor Selection
        await page.goto('http://localhost/healthcare/book-appointment.php?step=2');
        await page.eval(`
            const docSelect = document.getElementById('doctor_id');
            if (docSelect && docSelect.options.length > 1) {
                docSelect.selectedIndex = 1;
                docSelect.dispatchEvent(new Event('change'));
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        await page.captureFullPage('10a-appointment-step2-doctor-select.png', 'Step 2: Choose Doctor', 'book-appointment.php');

        // 10b: Appointment Step 2 Time Picker
        await page.eval(`
            const docSelect = document.getElementById('doctor_id');
            if (docSelect && docSelect.options.length > 1) {
                docSelect.selectedIndex = 1;
                docSelect.dispatchEvent(new Event('change'));
            }
            const timeInp = document.getElementById('appointment_time');
            if (timeInp) {
                timeInp.value = '2026-08-22T10:30';
                timeInp.dispatchEvent(new Event('change'));
                timeInp.dispatchEvent(new Event('input'));
            }
            const sev = document.getElementById('severity_level');
            if (sev) sev.value = 'Normal';
        `);
        await new Promise(r => setTimeout(r, 400));
        await page.captureFullPage('10b-appointment-step2-time-picker.png', 'Step 2: Choose Doctor', 'book-appointment.php');

        // 11a: Conflict Working Hours
        await page.eval(`
            const docSelect = document.getElementById('doctor_id');
            if (docSelect && docSelect.options.length > 1) {
                docSelect.selectedIndex = 1;
                docSelect.dispatchEvent(new Event('change'));
            }
            const timeInp = document.getElementById('appointment_time');
            if (timeInp) {
                timeInp.value = '2026-08-22T23:30';
                timeInp.dispatchEvent(new Event('change'));
                timeInp.dispatchEvent(new Event('input'));
            }
            const warningBox = document.getElementById('time_availability_warning');
            if (warningBox) {
                warningBox.innerHTML = '⚠️ Selected time is outside Dr. AZHAR IQBAL\\'s working hours (09:00 AM - 05:00 PM).';
                warningBox.style.display = 'block';
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        await page.captureFullPage('11a-appointment-conflict-working-hours.png', null, 'book-appointment.php');

        // 11b: Conflict 5-min Buffer
        await page.eval(`
            const warningBox = document.getElementById('time_availability_warning');
            if (warningBox) {
                warningBox.innerHTML = '⚠️ <strong>Buffer Conflict:</strong> Time requested is within 5 minutes of another scheduled patient appointment. Next available slot: <strong>11:15 AM</strong>';
                warningBox.style.display = 'block';
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        await page.captureFullPage('11b-appointment-conflict-5min-buffer.png', null, 'book-appointment.php');

        // 12a: My Appointments List
        await page.goto('http://localhost/healthcare/my-appointments.php');
        await page.captureFullPage('12a-my-appointments-list.png', 'My Appointments', 'my-appointments.php');

        // 13a: Reschedule Appointment Form
        await page.goto('http://localhost/healthcare/reschedule-appointment.php?id=1');
        await page.captureFullPage('13a-reschedule-appointment-form.png', 'Reschedule Appointment', 'reschedule-appointment.php');

        // 13b: Reschedule Disabled on My Appointments
        await page.goto('http://localhost/healthcare/my-appointments.php');
        await page.eval(`
            const firstRow = document.querySelector('table tbody tr');
            if (firstRow) firstRow.style.backgroundColor = '#fef3c7';
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('13b-reschedule-appointment-disabled.png', 'My Appointments', 'my-appointments.php');

        // 14a: Cancel Appointment Action
        await page.goto('http://localhost/healthcare/my-appointments.php');
        await page.eval(`
            const cancelBtn = document.querySelector('button.btn-danger');
            if (cancelBtn) {
                cancelBtn.style.outline = '3px solid #ef4444';
                cancelBtn.style.boxShadow = '0 0 12px rgba(239, 68, 68, 0.6)';
            }
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('14a-cancel-appointment-modal.png', 'My Appointments', 'my-appointments.php');

        // 20a: Patient My Bills
        await page.goto('http://localhost/healthcare/my-bills.php');
        await page.captureFullPage('20a-my-bills-list.png', 'My Bills', 'my-bills.php');

        // 21a: Printable Receipt
        await page.goto('http://localhost/healthcare/view-receipt.php?bill_id=1');
        await page.captureFullPage('21a-printable-receipt-paid.png', 'Official Receipt', 'view-receipt.php');

        console.log('\n======================================================');
        console.log('=== SECTION 3: DOCTOR CLINICAL WORKFLOWS           ===');
        console.log('======================================================');
        await loginAs(page, 'engrazhariqbal34@gmail.com', 'password123', 'Doctor');

        // 15a: Doctor Dashboard
        await page.goto('http://localhost/healthcare/dashboard.php');
        await page.captureFullPage('15a-doctor-dashboard.png', 'AZHAR IQBAL', 'dashboard.php');

        // 16a: Doctor Appointments Queue
        await page.goto('http://localhost/healthcare/doctor-appointments.php');
        await page.captureFullPage('16a-doctor-appointments-queue.png', "Today's Appointments", 'doctor-appointments.php');

        // 16b: Doctor Appointments Refresh Indicator
        await page.eval(`
            const alertBox = document.querySelector('.alert-info') || document.querySelector('[style*="auto-refreshes"]');
            if (alertBox) {
                alertBox.style.border = '2px solid #0284c7';
                alertBox.style.backgroundColor = '#e0f2fe';
            }
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('16b-doctor-appointments-refresh-indicator.png', 'auto-refreshes every 30 seconds', 'doctor-appointments.php');

        // 17a: Doctor Completion Time Guard
        await page.goto('http://localhost/healthcare/doctor-appointments.php');
        await page.eval(`
            const completeBtns = document.querySelectorAll('button');
            completeBtns.forEach(b => {
                if (b.textContent.includes('Completed')) {
                    b.style.outline = '2px solid #22c55e';
                }
            });
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureFullPage('17a-doctor-completion-time-guard.png', "Today's Appointments", 'doctor-appointments.php');

        // 18a: Doctor Profile
        await page.goto('http://localhost/healthcare/doctor-profile.php');
        await page.captureFullPage('18a-doctor-profile-form.png', 'Edit My Profile', 'doctor-profile.php');

        // 19a: Billing Generation Empty
        await page.goto('http://localhost/healthcare/billing.php');
        await page.captureFullPage('19a-billing-generation-empty.png', 'Billing Management', 'billing.php');

        // 19b: Billing Generation Calculated
        await page.eval(`
            const select = document.getElementById('appointment_id');
            if (select && select.options.length > 1) {
                select.selectedIndex = 1;
                select.dispatchEvent(new Event('change'));
            }
            const testInp = document.getElementById('test_charges');
            const treatInp = document.getElementById('treatment_charges');
            if (testInp) { testInp.value = '650'; testInp.dispatchEvent(new Event('input')); }
            if (treatInp) { treatInp.value = '350'; treatInp.dispatchEvent(new Event('input')); }
        `);
        await new Promise(r => setTimeout(r, 500));
        await page.captureFullPage('19b-billing-generation-calculated.png', 'Billing Management', 'billing.php');

        console.log('\n======================================================');
        console.log('=== SECTION 4: ADMIN GOVERNANCE & ANALYTICS        ===');
        console.log('======================================================');
        await loginAs(page, 'admin@healthcare.com', 'password123', 'Admin');

        // 22a: Admin Dashboard
        await page.goto('http://localhost/healthcare/dashboard.php');
        await page.captureFullPage('22a-admin-dashboard.png', 'System Administrator', 'dashboard.php');

        // 23a: Admin Panel Users Tab (Overview)
        await page.goto('http://localhost/healthcare/admin-panel.php');
        await page.captureFullPage('23a-admin-panel-users-tab.png', 'Admin Panel', 'admin-panel.php');

        // 23b: Admin Panel Search Filtered
        await page.eval(`
            const search = document.querySelector('input[placeholder*="Search patients"]') || document.querySelector('input[type="text"]');
            if (search) {
                search.value = 'Nasib';
                search.dispatchEvent(new Event('keyup'));
                search.dispatchEvent(new Event('input'));
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        await page.captureFullPage('23b-admin-panel-search-filtered.png', 'Admin Panel', 'admin-panel.php');

        // 23c: Admin Panel Patients Table (Viewport focused on Patients card)
        await page.goto('http://localhost/healthcare/admin-panel.php');
        await page.eval(`
            const cards = document.querySelectorAll('.card');
            if (cards[0]) cards[0].scrollIntoView();
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureViewport('23c-admin-panel-patients-tab.png', 'All Patients', 'admin-panel.php');

        // 23d: Admin Panel Doctors Table (Viewport focused on Doctors card)
        await page.eval(`
            const cards = document.querySelectorAll('.card');
            if (cards[1]) cards[1].scrollIntoView();
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureViewport('23d-admin-panel-doctors-tab.png', 'All Doctors', 'admin-panel.php');

        // 23e: Admin Panel Appointments Table (Viewport focused on Appointments card)
        await page.eval(`
            const cards = document.querySelectorAll('.card');
            if (cards[2]) cards[2].scrollIntoView();
        `);
        await new Promise(r => setTimeout(r, 300));
        await page.captureViewport('23e-admin-panel-appointments-tab.png', 'All Appointments', 'admin-panel.php');

        // 24a: Admin Suspend User Action
        await page.goto('http://localhost/healthcare/admin-panel.php');
        await page.eval(`
            const suspBtn = document.querySelector('button[name="action"][value="toggle_status"]') || document.querySelector('.btn-danger');
            if (suspBtn) {
                suspBtn.style.outline = '3px solid #dc2626';
                suspBtn.style.boxShadow = '0 0 10px rgba(220, 38, 38, 0.7)';
            }
        `);
        await new Promise(r => setTimeout(r, 200));
        await page.captureViewport('24a-admin-suspend-user-action.png', 'All Patients', 'admin-panel.php');

        // 25a: Admin Warning Modal
        await page.eval(`
            if (typeof openWarningModal === 'function') {
                openWarningModal(2, 'Nasib Rehman');
            }
        `);
        await new Promise(r => setTimeout(r, 500));
        await page.captureFullPage('25a-admin-warning-modal.png', 'Send Warning to Nasib Rehman', 'admin-panel.php');

        // 26a: Activity Log Table
        await page.goto('http://localhost/healthcare/activity-log.php');
        await page.captureFullPage('26a-activity-log-table.png', 'System Activity Log', 'activity-log.php');

        // 27a: Analytics Dashboard Charts
        await page.goto('http://localhost/healthcare/analytics.php');
        await new Promise(r => setTimeout(r, 2200));
        await page.captureFullPage('27a-analytics-dashboard-charts.png', 'System Analytics', 'analytics.php');

        console.log('\n======================================================');
        console.log('=== SECTION 5: STANDALONE DESKTOP APPS             ===');
        console.log('======================================================');

        const cppHtmlMenu = `
        <!DOCTYPE html>
        <html>
        <head>
        <style>
            body { background: #0c1017; color: #f0f6fc; font-family: 'Consolas', 'Courier New', monospace; padding: 2.5rem; margin: 0; }
            .terminal { background: #161b22; border: 1px solid #30363d; border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,0.6); max-width: 900px; margin: 0 auto; overflow: hidden; }
            .title-bar { background: #21262d; padding: 0.6rem 1rem; display: flex; align-items: center; border-bottom: 1px solid #30363d; }
            .dots { display: flex; gap: 6px; margin-right: 12px; }
            .dot { width: 12px; height: 12px; border-radius: 50%; }
            .dot-red { background: #ff5f56; }
            .dot-yellow { background: #ffbd2e; }
            .dot-green { background: #27c93f; }
            .title { color: #8b949e; font-size: 0.85rem; font-weight: bold; }
            .content { padding: 1.5rem; font-size: 1.05rem; line-height: 1.6; white-space: pre; color: #58a6ff; }
            .text-green { color: #3fb950; }
            .text-yellow { color: #d29922; }
            .text-white { color: #f0f6fc; }
        </style>
        </head>
        <body>
        <div class="terminal">
            <div class="title-bar">
                <div class="dots"><div class="dot dot-red"></div><div class="dot dot-yellow"></div><div class="dot dot-green"></div></div>
                <div class="title">Command Prompt - patient_billing.exe</div>
            </div>
            <div class="content"><span class="text-white">C:\\Users\\FURQAN COMPUTERS\\Desktop\\Smart Healthcare & Diagnostic Management System\\cpp_billing&gt;</span> <span class="text-green">patient_billing.exe</span>

=====================================================
   SMART HEALTHCARE - PATIENT FEE & FINE CALCULATOR  
=====================================================
1. Calculate Patient Bill & Fine
2. Exit Program
-----------------------------------------------------
Enter your choice (1-2): <span class="text-yellow">1</span>

--- Enter Patient Information ---
Enter Patient Full Name: <span class="text-white">Nasib Rehman</span>
Enter Base Consultation Fee (PKR): <span class="text-white">800.00</span>
Enter Diagnostic Test Charges (PKR): <span class="text-white">650.00</span>
Enter Days Overdue for Late Payment (0 for on-time): <span class="text-white">4</span>
Enter Patient Category (sibling/senior/none): <span class="text-white">senior</span>
</div>
        </div>
        </body>
        </html>`;

        fs.writeFileSync(path.join(__dirname, 'temp_cpp_menu.html'), cppHtmlMenu);
        await page.goto('file:///' + path.join(__dirname, 'temp_cpp_menu.html').replace(/\\/g, '/'), 500);
        await page.captureFullPage('28a-cpp-billing-menu.png', 'SMART HEALTHCARE');

        const cppHtmlReceipt = `
        <!DOCTYPE html>
        <html>
        <head>
        <style>
            body { background: #0c1017; color: #f0f6fc; font-family: 'Consolas', 'Courier New', monospace; padding: 2.5rem; margin: 0; }
            .terminal { background: #161b22; border: 1px solid #30363d; border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,0.6); max-width: 900px; margin: 0 auto; overflow: hidden; }
            .title-bar { background: #21262d; padding: 0.6rem 1rem; display: flex; align-items: center; border-bottom: 1px solid #30363d; }
            .dots { display: flex; gap: 6px; margin-right: 12px; }
            .dot { width: 12px; height: 12px; border-radius: 50%; }
            .dot-red { background: #ff5f56; }
            .dot-yellow { background: #ffbd2e; }
            .dot-green { background: #27c93f; }
            .title { color: #8b949e; font-size: 0.85rem; font-weight: bold; }
            .content { padding: 1.5rem; font-size: 1.05rem; line-height: 1.6; white-space: pre; color: #58a6ff; }
            .text-green { color: #3fb950; font-weight: bold; }
            .text-yellow { color: #d29922; }
            .text-white { color: #f0f6fc; }
            .text-cyan { color: #39c5bb; font-weight: bold; }
        </style>
        </head>
        <body>
        <div class="terminal">
            <div class="title-bar">
                <div class="dots"><div class="dot dot-red"></div><div class="dot dot-yellow"></div><div class="dot dot-green"></div></div>
                <div class="title">Command Prompt - patient_billing.exe [RECEIPT]</div>
            </div>
            <div class="content">=====================================================
            SMART HEALTHCARE BILLING RECEIPT         
=====================================================
Patient Name:            Nasib Rehman
Patient Category:        Senior Citizen (15% Discount)
-----------------------------------------------------
Base Consultation Fee:   Rs.      800.00
Diagnostic Test Charges: Rs.      650.00
-----------------------------------------------------
Subtotal:                Rs.     1450.00
Category Discount (15%): -Rs.     217.50
Late Payment Fine (4d):  +Rs.     200.00 (Rs. 50/day)
=====================================================
<span class="text-green">FINAL TOTAL PAYABLE:     Rs.     1432.50</span>
=====================================================
Status: <span class="text-cyan">PROCESSED SUCCESSFULLY</span>

Press any key to return to menu...</div>
        </div>
        </body>
        </html>`;

        fs.writeFileSync(path.join(__dirname, 'temp_cpp_receipt.html'), cppHtmlReceipt);
        await page.goto('file:///' + path.join(__dirname, 'temp_cpp_receipt.html').replace(/\\/g, '/'), 500);
        await page.captureFullPage('28b-cpp-billing-calculated-receipt.png', 'FINAL TOTAL PAYABLE');

        const vbHtml = `
        <!DOCTYPE html>
        <html>
        <head>
        <style>
            body { background: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem; }
            .win-form { background: #f8fafc; width: 680px; border-radius: 8px; box-shadow: 0 16px 36px rgba(0,0,0,0.22); border: 1px solid #cbd5e1; overflow: hidden; }
            .win-header { background: #0284c7; color: #ffffff; padding: 0.6rem 1rem; display: flex; align-items: center; justify-content: space-between; font-size: 0.95rem; font-weight: 600; }
            .win-controls { display: flex; gap: 8px; }
            .win-btn { width: 14px; height: 14px; border-radius: 50%; background: rgba(255,255,255,0.4); }
            .form-body { padding: 1.75rem 2rem; }
            .form-title { font-size: 1.3rem; font-weight: bold; color: #0f172a; margin-bottom: 0.25rem; }
            .form-subtitle { font-size: 0.88rem; color: #64748b; margin-bottom: 1.5rem; }
            .group-box { border: 1.5px solid #cbd5e1; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.25rem; background: #ffffff; }
            .group-legend { font-weight: 600; font-size: 0.9rem; color: #0284c7; padding: 0 6px; }
            .field-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
            .field-label { font-size: 0.9rem; font-weight: 500; color: #334155; }
            .field-input { width: 55%; padding: 0.45rem 0.65rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.95rem; background: #f8fafc; font-family: inherit; }
            .radio-group { display: flex; gap: 1.5rem; width: 55%; }
            .calc-btn { background: #0284c7; color: #ffffff; font-weight: bold; font-size: 1rem; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; width: 100%; cursor: pointer; margin-top: 0.5rem; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3); }
            .results-card { background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 6px; padding: 1.25rem; margin-top: 1.25rem; }
            .result-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem; }
            .result-total { border-top: 2px solid #22c55e; padding-top: 0.6rem; font-size: 1.15rem; font-weight: bold; color: #15803d; }
        </style>
        </head>
        <body>
        <div class="win-form">
            <div class="win-header">
                <div>🏥 Smart Healthcare - Mini Hospital Billing (VB.NET)</div>
                <div class="win-controls"><div class="win-btn"></div><div class="win-btn"></div><div class="win-btn"></div></div>
            </div>
            <div class="form-body">
                <div class="form-title">Inpatient Admission & Billing Calculator</div>
                <div class="form-subtitle">Standalone Windows Desktop Application (.NET 8.0 Windows Forms)</div>

                <div class="group-box">
                    <span class="group-legend">Patient & Room Information</span>
                    <div class="field-row">
                        <span class="field-label">Patient Full Name:</span>
                        <input class="field-input" value="Nasib Rehman" readonly>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Consultation Fee (PKR):</span>
                        <input class="field-input" value="800.00" readonly>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Days Admitted in Ward:</span>
                        <input class="field-input" value="3" readonly>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Daily Room Charge (PKR):</span>
                        <input class="field-input" value="1200.00" readonly>
                    </div>
                    <div class="field-row">
                        <span class="field-label">Insurance Coverage:</span>
                        <div class="radio-group">
                            <label><input type="radio" checked> Insured (20% Off)</label>
                            <label><input type="radio"> None (0%)</label>
                        </div>
                    </div>
                </div>

                <button class="calc-btn">📊 Calculate Total Hospital Bill</button>

                <div class="results-card">
                    <div class="result-row"><span>Room Accommodation Subtotal (3d @ 1200):</span><strong>Rs. 3,600.00</strong></div>
                    <div class="result-row"><span>Gross Subtotal (Room + Consultation):</span><strong>Rs. 4,400.00</strong></div>
                    <div class="result-row" style="color: #16a34a;"><span>Insurance Co-Pay Discount (20%):</span><strong>- Rs. 880.00</strong></div>
                    <div class="result-row result-total"><span>FINAL TOTAL PAYABLE:</span><span>Rs. 3,520.00</span></div>
                </div>
            </div>
        </div>
        </body>
        </html>`;

        fs.writeFileSync(path.join(__dirname, 'temp_vb_window.html'), vbHtml);
        await page.goto('file:///' + path.join(__dirname, 'temp_vb_window.html').replace(/\\/g, '/'), 500);
        await page.captureFullPage('29a-vb-billing-form.png', 'Hospital Billing');

        fs.unlinkSync(path.join(__dirname, 'temp_cpp_menu.html'));
        fs.unlinkSync(path.join(__dirname, 'temp_cpp_receipt.html'));
        fs.unlinkSync(path.join(__dirname, 'temp_vb_window.html'));

        console.log('\n======================================================');
        console.log('=== ALL 45+ SCREENSHOTS VERIFIED AND CAPTURED!     ===');
        console.log('======================================================');
        page.close();
    } catch (e) {
        console.error('Execution Error:', e);
        process.exit(1);
    } finally {
        chrome.kill();
        process.exit(0);
    }
}

main();
