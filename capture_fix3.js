// =====================================================
// capture_fix3.js — Fix 3 Screenshot Groups with Real Data
// =====================================================
// Captures exactly these files:
//   Group 1: 10a, 10b, 11a, 11b  (Booking Step 2 interactions)
//   Group 2: 16a, 16b, 17a        (Doctor queue + completion guard)
//   Group 3: 12a, 13b             (Patient appointments + disabled reschedule)
// =====================================================

const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
const BASE_URL = 'http://localhost/healthcare';

// Test credentials
const PATIENT_EMAIL = 'nasibrehman187@gmail.com';
const PATIENT_PASS  = 'password123';
const DOCTOR_EMAIL  = 'engrazhariqbal34@gmail.com';
const DOCTOR_PASS   = 'password123';

// Ensure screenshots dir
if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

// =====================================================
// CDP Helpers
// =====================================================
function getJson(url) {
    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('HTTP timeout')), 6000);
        http.get(url, res => {
            let data = '';
            res.on('data', c => data += c);
            res.on('end', () => { clearTimeout(timeout); try { resolve(JSON.parse(data)); } catch(e) { reject(e); } });
        }).on('error', e => { clearTimeout(timeout); reject(e); });
    });
}

class CDPPage {
    constructor(wsUrl) {
        this.wsUrl = wsUrl;
        this.ws = null;
        this.id = 0;
        this.callbacks = new Map();
    }

    connect() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('WS connect timeout')), 8000);
            this.ws = new WebSocket(this.wsUrl);
            this.ws.onopen = async () => {
                clearTimeout(timeout);
                await this.send('Page.enable');
                await this.send('Runtime.enable');
                await this.send('DOM.enable');
                resolve();
            };
            this.ws.onmessage = evt => {
                const msg = JSON.parse(evt.data);
                if (msg.id !== undefined && this.callbacks.has(msg.id)) {
                    const cb = this.callbacks.get(msg.id);
                    this.callbacks.delete(msg.id);
                    if (msg.error) cb.reject(new Error(msg.error.message));
                    else cb.resolve(msg.result);
                }
            };
            this.ws.onerror = e => { clearTimeout(timeout); reject(e); };
        });
    }

    send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const reqId = ++this.id;
            this.callbacks.set(reqId, { resolve, reject });
            this.ws.send(JSON.stringify({ id: reqId, method, params }));
        });
    }

    async eval(expr) {
        const result = await this.send('Runtime.evaluate', {
            expression: expr,
            awaitPromise: true,
            returnByValue: true
        });
        if (result.exceptionDetails) {
            throw new Error('Eval error: ' + JSON.stringify(result.exceptionDetails));
        }
        return result.result?.value;
    }

    async goto(url, delay = 1400) {
        await this.send('Page.navigate', { url });
        await new Promise(r => setTimeout(r, delay));
    }

    async setViewport(w, h) {
        await this.send('Emulation.setDeviceMetricsOverride', {
            width: w, height: h, deviceScaleFactor: 1, mobile: false
        });
    }

    async captureFullPage(filename, expectedText, expectedUrlSubstr) {
        // Assertions
        const url = await this.eval('window.location.href');
        if (expectedUrlSubstr && !url.includes(expectedUrlSubstr)) {
            console.error(`  [WARN] URL mismatch for ${filename}: expected "${expectedUrlSubstr}" in "${url}"`);
        }
        if (expectedText) {
            const bodyText = await this.eval('document.body.innerText');
            if (!bodyText.includes(expectedText)) {
                console.error(`  [WARN] Text "${expectedText}" not found on page for ${filename}`);
            }
        }

        // Get full page dimensions
        const metrics = await this.send('Page.getLayoutMetrics');
        const cw = Math.max(1280, Math.ceil(metrics.contentSize.width));
        const ch = Math.max(800, Math.ceil(metrics.contentSize.height));
        await this.setViewport(cw, ch);
        await new Promise(r => setTimeout(r, 300));

        const shot = await this.send('Page.captureScreenshot', {
            format: 'png', captureBeyondViewport: true
        });
        const buf = Buffer.from(shot.data, 'base64');
        const filePath = path.join(SCREENSHOT_DIR, filename);
        fs.writeFileSync(filePath, buf);
        console.log(`  ✅ Saved ${filename} (${buf.length} bytes)`);

        // Reset viewport
        await this.setViewport(1280, 900);
        return buf.length;
    }

    async captureViewport(filename, expectedText, expectedUrlSubstr) {
        const url = await this.eval('window.location.href');
        if (expectedUrlSubstr && !url.includes(expectedUrlSubstr)) {
            console.error(`  [WARN] URL mismatch for ${filename}: expected "${expectedUrlSubstr}" in "${url}"`);
        }
        if (expectedText) {
            const bodyText = await this.eval('document.body.innerText');
            if (!bodyText.includes(expectedText)) {
                console.error(`  [WARN] Text "${expectedText}" not found on page for ${filename}`);
            }
        }

        await this.setViewport(1280, 900);
        await new Promise(r => setTimeout(r, 200));

        const shot = await this.send('Page.captureScreenshot', {
            format: 'png', captureBeyondViewport: false
        });
        const buf = Buffer.from(shot.data, 'base64');
        const filePath = path.join(SCREENSHOT_DIR, filename);
        fs.writeFileSync(filePath, buf);
        console.log(`  ✅ Saved ${filename} (${buf.length} bytes)`);
        return buf.length;
    }

    close() { if (this.ws) this.ws.close(); }
}

// =====================================================
// Auth Helpers
// =====================================================
async function logout(page) {
    await page.goto(`${BASE_URL}/logout.php`, 800);
}

async function loginAs(page, email, password, roleName) {
    console.log(`  Logging in as ${roleName} (${email})...`);
    await logout(page);
    await page.goto(`${BASE_URL}/login.php`, 1200);
    
    await page.eval(`
        document.getElementById('email').value = '${email}';
        document.getElementById('password').value = '${password}';
        document.querySelector('form').submit();
    `);
    await new Promise(r => setTimeout(r, 1800));
    
    const url = await page.eval('window.location.href');
    if (!url.includes('dashboard.php')) {
        const snippet = await page.eval('document.body.innerText.substring(0,200)');
        throw new Error(`[LOGIN FAILED] ${roleName}: URL=${url}, snippet=${snippet}`);
    }
    console.log(`  ✅ Logged in as ${roleName}`);
}

// =====================================================
// Main Capture Routine
// =====================================================
async function main() {
    console.log('=== capture_fix3.js — Starting ===');
    console.log('Launching Chrome...');

    const userDataDir = path.join(process.env.TEMP, 'chrome_fix3_' + Date.now());
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        '--remote-debugging-port=9222',
        '--no-sandbox',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-component-extensions-with-background-pages',
        '--window-size=1280,900',
        `--user-data-dir=${userDataDir}`,
        `${BASE_URL}/logout.php`
    ]);
    chrome.stderr.on('data', () => {}); // suppress noise

    await new Promise(r => setTimeout(r, 2500));

    let page;
    const sizes = {};

    try {
        // Connect to Chrome
        const targets = await getJson('http://127.0.0.1:9222/json/list');
        const target = targets.find(t => t.type === 'page' && !t.url.startsWith('chrome-extension://')) || targets[0];
        console.log(`Connecting to: ${target.webSocketDebuggerUrl}`);
        
        page = new CDPPage(target.webSocketDebuggerUrl);
        await page.connect();
        await page.setViewport(1280, 900);
        console.log('Connected!\n');

        // =====================================================
        // GROUP 1: Booking Step 2 — Doctor Select, Time Picker, Conflicts
        // Need to go through the booking flow to reach Step 2
        // =====================================================
        console.log('=== GROUP 1: Booking Step 2 Screenshots ===');
        await loginAs(page, PATIENT_EMAIL, PATIENT_PASS, 'Patient');

        // Navigate to booking page and submit Step 1 (symptom assessment)
        await page.goto(`${BASE_URL}/book-appointment.php`, 1500);
        console.log('  Submitting Step 1 symptom assessment...');
        
        // Check symptoms and submit
        await page.eval(`
            // Check fever and cough
            var cbs = document.querySelectorAll('input[name="symptoms[]"]');
            for (var i = 0; i < cbs.length; i++) {
                if (cbs[i].value === 'fever' || cbs[i].value === 'cough' || cbs[i].value === 'headache') {
                    cbs[i].checked = true;
                }
            }
            // Set duration
            document.getElementById('days_duration').value = '3';
            // Submit
            document.querySelector('form').submit();
        `);
        await new Promise(r => setTimeout(r, 2000));
        
        // Should now be at step 1 result view — click "Yes, Book an Appointment"
        console.log('  Clicking "Yes, Book an Appointment"...');
        const hasBookBtn = await page.eval(`
            var links = document.querySelectorAll('a');
            for (var i = 0; i < links.length; i++) {
                if (links[i].textContent.includes('Book an Appointment') || links[i].href.includes('step=2')) {
                    links[i].click();
                    'found';
                    break;
                }
            }
        `);
        await new Promise(r => setTimeout(r, 1800));
        
        // Verify we're on Step 2
        const step2Url = await page.eval('window.location.href');
        console.log(`  Current URL: ${step2Url}`);
        
        if (!step2Url.includes('step=2')) {
            // Alternative: direct navigate if we have pending_diagnosis in session
            console.log('  Attempting direct navigation to step=2...');
            await page.goto(`${BASE_URL}/book-appointment.php?step=2`, 1500);
        }

        // ── 10a: Doctor Select (before selecting) ──
        // First, capture the empty state (no doctor selected yet)
        console.log('\n  📸 10a: Doctor select dropdown...');
        // Open the dropdown to show options
        await page.eval(`
            var sel = document.getElementById('doctor_id');
            if (sel) {
                // Select the first doctor option
                sel.selectedIndex = 1;
                sel.dispatchEvent(new Event('change'));
            }
        `);
        await new Promise(r => setTimeout(r, 600));
        
        // Highlight the doctor selection area
        await page.eval(`
            var sel = document.getElementById('doctor_id');
            if (sel) {
                sel.style.outline = '3px solid #0ea5e9';
                sel.style.boxShadow = '0 0 12px rgba(14,165,233,0.3)';
            }
            var addrBox = document.getElementById('doctor_address_box');
            if (addrBox && addrBox.style.display !== 'none') {
                addrBox.style.outline = '2px solid #10b981';
            }
        `);
        await new Promise(r => setTimeout(r, 300));
        sizes['10a'] = await page.captureFullPage('10a-appointment-step2-doctor-select.png', 'Select Doctor', 'step=2');
        
        // Remove highlight
        await page.eval(`
            var sel = document.getElementById('doctor_id');
            if (sel) { sel.style.outline = ''; sel.style.boxShadow = ''; }
            var addrBox = document.getElementById('doctor_address_box');
            if (addrBox) { addrBox.style.outline = ''; }
        `);

        // ── 10b: Time picker with date filled ──
        console.log('  📸 10b: Time picker with value...');
        // Set severity
        await page.eval(`
            var sev = document.getElementById('severity_level');
            if (sev) {
                sev.value = 'Normal';
                sev.dispatchEvent(new Event('change'));
            }
        `);
        await new Promise(r => setTimeout(r, 200));
        
        // Set a valid appointment time (today at 3:30 PM — within working hours)
        await page.eval(`
            var timeInput = document.getElementById('appointment_time');
            if (timeInput) {
                timeInput.value = '2026-08-18T15:30';
                timeInput.dispatchEvent(new Event('change'));
                timeInput.dispatchEvent(new Event('input'));
                // Highlight the time input
                timeInput.style.outline = '3px solid #0ea5e9';
                timeInput.style.boxShadow = '0 0 12px rgba(14,165,233,0.3)';
            }
        `);
        await new Promise(r => setTimeout(r, 1200)); // Wait for availability check
        sizes['10b'] = await page.captureFullPage('10b-appointment-step2-time-picker.png', 'Appointment Date', 'step=2');
        
        // Remove highlight
        await page.eval(`
            var timeInput = document.getElementById('appointment_time');
            if (timeInput) { timeInput.style.outline = ''; timeInput.style.boxShadow = ''; }
        `);

        // ── 11a: Working hours conflict ──
        console.log('  📸 11a: Working hours conflict warning...');
        // Set time OUTSIDE working hours (e.g., 8:00 AM — doctor works 2-5 PM)
        await page.eval(`
            var timeInput = document.getElementById('appointment_time');
            if (timeInput) {
                timeInput.value = '2026-08-19T08:00';
                timeInput.dispatchEvent(new Event('change'));
                timeInput.dispatchEvent(new Event('input'));
            }
        `);
        await new Promise(r => setTimeout(r, 1500)); // Wait for AJAX response
        
        // Highlight the warning box
        await page.eval(`
            var warn = document.getElementById('time_availability_warning');
            if (warn && warn.style.display !== 'none') {
                warn.style.outline = '3px solid #ef4444';
                warn.style.boxShadow = '0 0 12px rgba(239,68,68,0.35)';
            }
        `);
        await new Promise(r => setTimeout(r, 300));
        
        // Verify the warning is actually showing
        const warnText11a = await page.eval(`
            var warn = document.getElementById('time_availability_warning');
            warn ? warn.innerText : 'NOT_FOUND';
        `);
        console.log(`  Warning text (11a): "${warnText11a}"`);
        
        sizes['11a'] = await page.captureFullPage('11a-appointment-conflict-working-hours.png', '', 'step=2');
        
        // Remove highlight
        await page.eval(`
            var warn = document.getElementById('time_availability_warning');
            if (warn) { warn.style.outline = ''; warn.style.boxShadow = ''; }
        `);

        // ── 11b: 5-minute buffer conflict ──
        console.log('  📸 11b: 5-minute buffer conflict warning...');
        // We seeded an appointment at 15:00 (3:00 PM). Set time to 15:03 (within 5 min)
        await page.eval(`
            var timeInput = document.getElementById('appointment_time');
            if (timeInput) {
                timeInput.value = '2026-08-18T15:03';
                timeInput.dispatchEvent(new Event('change'));
                timeInput.dispatchEvent(new Event('input'));
            }
        `);
        await new Promise(r => setTimeout(r, 1500)); // Wait for AJAX response
        
        // Highlight warning
        await page.eval(`
            var warn = document.getElementById('time_availability_warning');
            if (warn && warn.style.display !== 'none') {
                warn.style.outline = '3px solid #f59e0b';
                warn.style.boxShadow = '0 0 12px rgba(245,158,11,0.35)';
            }
        `);
        await new Promise(r => setTimeout(r, 300));
        
        const warnText11b = await page.eval(`
            var warn = document.getElementById('time_availability_warning');
            warn ? warn.innerText : 'NOT_FOUND';
        `);
        console.log(`  Warning text (11b): "${warnText11b}"`);
        
        sizes['11b'] = await page.captureFullPage('11b-appointment-conflict-5min-buffer.png', '', 'step=2');

        // =====================================================
        // GROUP 3: Patient's My Appointments + Disabled Reschedule
        // (Do this BEFORE doctor group since we're already logged in as patient)
        // =====================================================
        console.log('\n=== GROUP 3: My Appointments + Disabled Reschedule ===');
        
        // ── 12a: My Appointments list ──
        console.log('  📸 12a: My Appointments list...');
        await page.goto(`${BASE_URL}/my-appointments.php`, 1500);
        sizes['12a'] = await page.captureFullPage('12a-my-appointments-list.png', 'My Appointments', 'my-appointments.php');

        // ── 13b: Disabled reschedule button ──
        console.log('  📸 13b: Disabled reschedule button...');
        // The appointment at 11:05 AM (appointment #30) is ~25 min from now — reschedule should be disabled
        // Find and highlight the disabled reschedule span
        await page.eval(`
            // Find the disabled reschedule buttons (spans with opacity 0.5)
            var spans = document.querySelectorAll('span.btn.btn-secondary');
            for (var i = 0; i < spans.length; i++) {
                if (spans[i].textContent.includes('Reschedule') && spans[i].style.opacity === '0.5') {
                    spans[i].style.outline = '3px solid #ef4444';
                    spans[i].style.boxShadow = '0 0 12px rgba(239,68,68,0.4)';
                    spans[i].scrollIntoView({ behavior: 'instant', block: 'center' });
                    break;
                }
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        sizes['13b'] = await page.captureFullPage('13b-reschedule-appointment-disabled.png', 'My Appointments', 'my-appointments.php');

        // =====================================================
        // GROUP 2: Doctor Queue + Completion Guard
        // =====================================================
        console.log('\n=== GROUP 2: Doctor Queue + Completion Guard ===');
        await loginAs(page, DOCTOR_EMAIL, DOCTOR_PASS, 'Doctor');

        // ── 16a: Doctor's today queue with Emergency on top ──
        console.log('  📸 16a: Doctor appointments queue (Emergency on top)...');
        await page.goto(`${BASE_URL}/doctor-appointments.php?filter=today`, 1800);
        
        // Verify the queue has rows
        const queueRows = await page.eval(`document.querySelectorAll('table tbody tr').length`);
        console.log(`  Queue rows found: ${queueRows}`);
        
        // Verify Emergency is first
        const firstSeverity = await page.eval(`
            var rows = document.querySelectorAll('table tbody tr');
            if (rows.length > 0) {
                var badges = rows[0].querySelectorAll('.badge');
                for (var i = 0; i < badges.length; i++) {
                    if (badges[i].textContent.trim() === 'Emergency') return 'Emergency';
                }
            }
            return 'not_found';
        `);
        console.log(`  First row severity: ${firstSeverity}`);
        
        sizes['16a'] = await page.captureFullPage('16a-doctor-appointments-queue.png', 'Appointments', 'doctor-appointments.php');

        // ── 16b: Auto-refresh indicator ──
        console.log('  📸 16b: Auto-refresh indicator...');
        // Highlight the auto-refresh text
        await page.eval(`
            var refreshNote = document.querySelector('p');
            var allPs = document.querySelectorAll('p');
            for (var i = 0; i < allPs.length; i++) {
                if (allPs[i].textContent.includes('auto-refreshes')) {
                    allPs[i].style.outline = '3px solid #0ea5e9';
                    allPs[i].style.boxShadow = '0 0 12px rgba(14,165,233,0.35)';
                    allPs[i].style.borderRadius = '6px';
                    allPs[i].style.padding = '0.5rem 0.75rem';
                    allPs[i].scrollIntoView({ behavior: 'instant', block: 'center' });
                    break;
                }
            }
        `);
        await new Promise(r => setTimeout(r, 300));
        sizes['16b'] = await page.captureViewport('16b-doctor-appointments-refresh-indicator.png', 'auto-refreshes', 'doctor-appointments.php');

        // ── 17a: Completion time guard (disabled button) ──
        console.log('  📸 17a: Completion time guard...');
        // Remove previous highlight first
        await page.eval(`
            var allPs = document.querySelectorAll('p');
            for (var i = 0; i < allPs.length; i++) {
                if (allPs[i].textContent.includes('auto-refreshes')) {
                    allPs[i].style.outline = '';
                    allPs[i].style.boxShadow = '';
                    break;
                }
            }
        `);
        
        // Find and highlight the disabled "⏳ Mark Complete" button 
        await page.eval(`
            var btns = document.querySelectorAll('button[disabled]');
            for (var i = 0; i < btns.length; i++) {
                if (btns[i].textContent.includes('Mark Complete')) {
                    btns[i].style.outline = '3px solid #ef4444';
                    btns[i].style.boxShadow = '0 0 14px rgba(239,68,68,0.45)';
                    // Scroll the row into view
                    var row = btns[i].closest('tr');
                    if (row) row.scrollIntoView({ behavior: 'instant', block: 'center' });
                    break;
                }
            }
        `);
        await new Promise(r => setTimeout(r, 400));
        sizes['17a'] = await page.captureFullPage('17a-doctor-completion-time-guard.png', 'Appointments', 'doctor-appointments.php');

        // =====================================================
        // VERIFICATION: All screenshots must have unique sizes
        // =====================================================
        console.log('\n=== VERIFICATION ===');
        const allSizes = Object.entries(sizes);
        const sizeValues = allSizes.map(([k, v]) => v);
        
        console.log('File sizes:');
        for (const [name, size] of allSizes) {
            console.log(`  ${name}: ${size} bytes`);
        }
        
        // Check for duplicates within groups
        const groups = [
            ['10a', '10b', '11a', '11b'],
            ['16a', '16b', '17a'],
            ['12a', '13b']
        ];
        
        let hasDupes = false;
        for (const group of groups) {
            const groupSizes = group.map(k => sizes[k]);
            for (let i = 0; i < group.length; i++) {
                for (let j = i + 1; j < group.length; j++) {
                    if (groupSizes[i] === groupSizes[j]) {
                        console.error(`  ❌ DUPLICATE SIZE: ${group[i]} and ${group[j]} are both ${groupSizes[i]} bytes!`);
                        hasDupes = true;
                    }
                }
            }
        }
        
        if (!hasDupes) {
            console.log('  ✅ All files in each group have unique sizes — no duplicates!');
        }

        console.log('\n=== capture_fix3.js COMPLETE ===');

    } catch (err) {
        console.error('FATAL ERROR:', err);
    } finally {
        if (page) page.close();
        chrome.kill();
        // Cleanup temp dir
        try { fs.rmSync(userDataDir, { recursive: true, force: true }); } catch(e) {}
    }
}

main();
