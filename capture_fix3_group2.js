// =====================================================
// capture_fix3_group2.js — Fix Group 2 Only (Doctor Queue)
// =====================================================
// Captures: 16a, 16b, 17a

const http = require('http');
const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
const BASE_URL = 'http://localhost/healthcare';
const DOCTOR_EMAIL = 'engrazhariqbal34@gmail.com';
const DOCTOR_PASS = 'password123';

if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

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
    constructor(wsUrl) { this.wsUrl = wsUrl; this.ws = null; this.id = 0; this.callbacks = new Map(); }
    connect() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('WS timeout')), 8000);
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
        const result = await this.send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true });
        if (result.exceptionDetails) throw new Error('Eval error: ' + JSON.stringify(result.exceptionDetails));
        return result.result?.value;
    }
    async goto(url, delay = 1400) {
        await this.send('Page.navigate', { url });
        await new Promise(r => setTimeout(r, delay));
    }
    async setViewport(w, h) {
        await this.send('Emulation.setDeviceMetricsOverride', { width: w, height: h, deviceScaleFactor: 1, mobile: false });
    }
    async captureFullPage(filename, expectedText, expectedUrlSubstr) {
        const url = await this.eval('window.location.href');
        if (expectedUrlSubstr && !url.includes(expectedUrlSubstr))
            console.error(`  [WARN] URL mismatch for ${filename}: expected "${expectedUrlSubstr}" in "${url}"`);
        if (expectedText) {
            const bodyText = await this.eval('document.body.innerText');
            if (!bodyText.includes(expectedText))
                console.error(`  [WARN] Text "${expectedText}" not found on page for ${filename}`);
        }
        const metrics = await this.send('Page.getLayoutMetrics');
        const cw = Math.max(1280, Math.ceil(metrics.contentSize.width));
        const ch = Math.max(800, Math.ceil(metrics.contentSize.height));
        await this.setViewport(cw, ch);
        await new Promise(r => setTimeout(r, 300));
        const shot = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
        const buf = Buffer.from(shot.data, 'base64');
        fs.writeFileSync(path.join(SCREENSHOT_DIR, filename), buf);
        console.log(`  ✅ Saved ${filename} (${buf.length} bytes)`);
        await this.setViewport(1280, 900);
        return buf.length;
    }
    async captureViewport(filename, expectedText, expectedUrlSubstr) {
        const url = await this.eval('window.location.href');
        if (expectedUrlSubstr && !url.includes(expectedUrlSubstr))
            console.error(`  [WARN] URL mismatch for ${filename}: expected "${expectedUrlSubstr}" in "${url}"`);
        if (expectedText) {
            const bodyText = await this.eval('document.body.innerText');
            if (!bodyText.includes(expectedText))
                console.error(`  [WARN] Text "${expectedText}" not found on page for ${filename}`);
        }
        await this.setViewport(1280, 900);
        await new Promise(r => setTimeout(r, 200));
        const shot = await this.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
        const buf = Buffer.from(shot.data, 'base64');
        fs.writeFileSync(path.join(SCREENSHOT_DIR, filename), buf);
        console.log(`  ✅ Saved ${filename} (${buf.length} bytes)`);
        return buf.length;
    }
    close() { if (this.ws) this.ws.close(); }
}

async function main() {
    console.log('=== capture_fix3_group2.js — Starting ===');
    const userDataDir = path.join(process.env.TEMP, 'chrome_fix3g2_' + Date.now());
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new', '--remote-debugging-port=9222', '--no-sandbox', '--disable-gpu',
        '--disable-extensions', '--disable-component-extensions-with-background-pages',
        '--window-size=1280,900', `--user-data-dir=${userDataDir}`,
        `${BASE_URL}/logout.php`
    ]);
    chrome.stderr.on('data', () => {});
    await new Promise(r => setTimeout(r, 2500));

    let page;
    const sizes = {};

    try {
        const targets = await getJson('http://127.0.0.1:9222/json/list');
        const target = targets.find(t => t.type === 'page' && !t.url.startsWith('chrome-extension://')) || targets[0];
        console.log(`Connecting to: ${target.webSocketDebuggerUrl}`);
        page = new CDPPage(target.webSocketDebuggerUrl);
        await page.connect();
        await page.setViewport(1280, 900);
        console.log('Connected!\n');

        // Login as Doctor
        console.log('  Logging in as Doctor...');
        await page.goto(`${BASE_URL}/logout.php`, 800);
        await page.goto(`${BASE_URL}/login.php`, 1200);
        await page.eval(`
            document.getElementById('email').value = '${DOCTOR_EMAIL}';
            document.getElementById('password').value = '${DOCTOR_PASS}';
            document.querySelector('form').submit();
        `);
        await new Promise(r => setTimeout(r, 1800));
        const loginUrl = await page.eval('window.location.href');
        console.log(`  Login redirect URL: ${loginUrl}`);
        if (!loginUrl.includes('dashboard.php')) throw new Error('Doctor login failed');
        console.log('  ✅ Logged in as Doctor\n');

        // Navigate to today's appointments
        await page.goto(`${BASE_URL}/doctor-appointments.php?filter=today`, 2000);

        // Verify queue
        const queueRows = await page.eval('document.querySelectorAll("table tbody tr").length');
        console.log(`  Queue rows found: ${queueRows}`);

        // Check first row severity
        const firstSev = await page.eval(`
            (function() {
                var rows = document.querySelectorAll('table tbody tr');
                if (rows.length > 0) {
                    var badges = rows[0].querySelectorAll('.badge');
                    for (var i = 0; i < badges.length; i++) {
                        if (badges[i].textContent.trim() === 'Emergency') return 'Emergency';
                    }
                }
                return 'not_found';
            })()
        `);
        console.log(`  First row severity: ${firstSev}`);

        // ── 16a: Doctor queue ──
        console.log('\n  📸 16a: Doctor appointments queue...');
        sizes['16a'] = await page.captureFullPage('16a-doctor-appointments-queue.png', 'Appointments', 'doctor-appointments.php');

        // ── 16b: Auto-refresh indicator ──
        console.log('  📸 16b: Auto-refresh indicator...');
        await page.eval(`
            (function() {
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
            })()
        `);
        await new Promise(r => setTimeout(r, 300));
        sizes['16b'] = await page.captureViewport('16b-doctor-appointments-refresh-indicator.png', 'auto-refreshes', 'doctor-appointments.php');

        // ── 17a: Completion time guard ──
        console.log('  📸 17a: Completion time guard (disabled Mark Complete)...');
        // Remove previous highlight
        await page.eval(`
            (function() {
                var allPs = document.querySelectorAll('p');
                for (var i = 0; i < allPs.length; i++) {
                    if (allPs[i].textContent.includes('auto-refreshes')) {
                        allPs[i].style.outline = '';
                        allPs[i].style.boxShadow = '';
                        break;
                    }
                }
            })()
        `);
        
        // Highlight disabled Mark Complete button
        await page.eval(`
            (function() {
                var btns = document.querySelectorAll('button[disabled]');
                var found = false;
                for (var i = 0; i < btns.length; i++) {
                    if (btns[i].textContent.includes('Mark Complete')) {
                        btns[i].style.outline = '3px solid #ef4444';
                        btns[i].style.boxShadow = '0 0 14px rgba(239,68,68,0.45)';
                        var row = btns[i].closest('tr');
                        if (row) row.scrollIntoView({ behavior: 'instant', block: 'center' });
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    // Maybe all buttons with the ⏳ icon
                    var allBtns = document.querySelectorAll('button');
                    for (var j = 0; j < allBtns.length; j++) {
                        if (allBtns[j].textContent.includes('⏳') || allBtns[j].textContent.includes('Mark Complete')) {
                            allBtns[j].style.outline = '3px solid #ef4444';
                            allBtns[j].style.boxShadow = '0 0 14px rgba(239,68,68,0.45)';
                            break;
                        }
                    }
                }
            })()
        `);
        await new Promise(r => setTimeout(r, 400));
        sizes['17a'] = await page.captureFullPage('17a-doctor-completion-time-guard.png', 'Appointments', 'doctor-appointments.php');

        // Verification
        console.log('\n=== VERIFICATION ===');
        for (const [name, size] of Object.entries(sizes)) {
            console.log(`  ${name}: ${size} bytes`);
        }
        
        const sizeArr = Object.values(sizes);
        let hasDupes = false;
        const keys = Object.keys(sizes);
        for (let i = 0; i < keys.length; i++) {
            for (let j = i + 1; j < keys.length; j++) {
                if (sizes[keys[i]] === sizes[keys[j]]) {
                    console.error(`  ❌ DUPLICATE: ${keys[i]} and ${keys[j]} both ${sizes[keys[i]]} bytes`);
                    hasDupes = true;
                }
            }
        }
        if (!hasDupes) console.log('  ✅ All unique!');

        console.log('\n=== capture_fix3_group2.js COMPLETE ===');

    } catch (err) {
        console.error('FATAL ERROR:', err);
    } finally {
        if (page) page.close();
        chrome.kill();
        try { fs.rmSync(userDataDir, { recursive: true, force: true }); } catch(e) {}
    }
}

main();
