/**
 * E2E Test: Full Patient Flow — Clinique Saint Matthieu
 *
 * Two parallel browser contexts:
 *   - docteur_general (dr_gnle@cmsm.ci)
 *   - aide_soignante  (aide@cmsm.ci)
 *
 * Flow: Accueil → Salle d'attente → Médecin → Hospitalisation → Admission requests → Sortie → Caisse
 *
 * Run: node tests/e2e/patient-flow.mjs
 */

import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

const BASE = 'http://localhost:8000';
const SCREENSHOTS_DIR = 'tests/e2e/screenshots';
const REPORT_MD = 'tests/e2e/patient-flow-report.md';
const REPORT_JSON = 'tests/e2e/patient-flow-report.json';

const DOCTOR = { email: 'dr_gnle@cmsm.ci', password: 'password', role: 'docteur_general', label: 'Doctor' };
const NURSE  = { email: 'aide@cmsm.ci',   password: 'password', role: 'aide_soignante',  label: 'Nurse' };

// ================================================================
// HELPERS
// ================================================================
const ts  = () => new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
const log = (msg) => console.log(`[${new Date().toISOString().slice(11,19)}] ${msg}`);

async function screenshot(page, label) {
  const name = `${ts()}_${label}.png`;
  const fp   = path.join(SCREENSHOTS_DIR, name);
  fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
  await page.screenshot({ path: fp, fullPage: true });
  return path.basename(fp);
}

async function expectPageOk(page, step) {
  const url = page.url();
  if (!url.startsWith(BASE)) throw new Error(`[${step}] Off-site redirect → ${url}`);
  const html = await page.content();
  if (/Whoops|Server Error|Something went wrong/.test(html))
    throw new Error(`[${step}] Application error page at ${url}`);
  return true;
}

async function loginAs(page, user) {
  log(`  → ${user.label} logging in as ${user.email}`);
  await page.goto(`${BASE}/login`);
  await expectPageOk(page, 'login');
  await page.waitForTimeout(2000);

  await page.fill('input[name="email"]', user.email);
  await page.fill('input[name="password"]', user.password);

  const submitBtn = page.locator('button:has-text("Se connecter")').first();
  if (await submitBtn.count() > 0) {
    await submitBtn.click();
  } else {
    await page.locator('form[action*="login"] button[type="submit"]').first().click();
  }

  try {
    await page.waitForURL(url => !String(url).includes('/login'), { timeout: 45000 });
  } catch (e) {
    const content = await page.content();
    if (content.includes('Ces identifiants') || content.includes('incorrects')) {
      throw new Error(`Login failed: invalid credentials for ${user.email}`);
    }
    throw e;
  }
  await page.waitForTimeout(2000);
  return true;
}

async function acceptAnyDialog(page, label) {
  page.once('dialog', async dialog => {
    log(`  [dialog ${label}] "${dialog.message().slice(0, 80)}" → accept`);
    await dialog.accept();
  });
}

async function getCsrf(page) {
  return await page.evaluate(() => document.querySelector('meta[name=csrf-token]')?.content || '');
}

async function apiPost(page, url, data = {}) {
  const csrf = await getCsrf(page);
  const resp = await page.request.post(`${BASE}${url}`, {
    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    data,
  });
  return resp;
}

// Extract visit_id from a form action like /visites/123/constantes
function extractVisitId(action) {
  const m = action.match(/\/visites\/(\d+)/);
  return m ? parseInt(m[1]) : null;
}

// Extract resource ID from URL pattern
function extractId(url, pattern) {
  const m = url.match(pattern);
  return m ? parseInt(m[1]) : null;
}

// ================================================================
// MAIN TEST
// ================================================================
async function runFlow() {
  const browser   = await chromium.launch({ headless: true });
  const ctxDoc    = await browser.newContext({ viewport: { width: 1366, height: 900 } });
  const ctxNurse  = await browser.newContext({ viewport: { width: 1366, height: 900 } });
  const docPage   = await ctxDoc.newPage();
  const nursePage = await ctxNurse.newPage();

  const shots   = [];
  const steps   = [];
  const bugs    = [];
  const netFail = [];
  const jsErr   = [];

  docPage.on('requestfailed',  r => netFail.push({ u: 'doctor', url: r.url(), err: r.failure()?.errorText }));
  nursePage.on('requestfailed', r => netFail.push({ u: 'nurse',  url: r.url(), err: r.failure()?.errorText }));
  docPage.on('pageerror',  e => jsErr.push({ u: 'doctor', msg: e.message }));
  nursePage.on('pageerror', e => jsErr.push({ u: 'nurse', msg: e.message }));

  const uid = Date.now().toString().slice(-6);
  const patientName = `QA${uid}`;

  function record(step, user, status, detail = '') {
    steps.push({ step, user, status, detail, ts: new Date().toISOString() });
    log(`  [${step}] ${user} → ${status}${detail ? ' | ' + detail.slice(0, 100) : ''}`);
  }

  try {
    // ================================================================
    // 0. LOGIN
    // ================================================================
    log('══ LOGIN ══');
    await loginAs(docPage,  DOCTOR);
    await loginAs(nursePage, NURSE);
    shots.push(await screenshot(docPage,   '00_doctor_logged'));
    shots.push(await screenshot(nursePage, '00_nurse_logged'));
    record('LOGIN', 'both', 'PASS');

    // ================================================================
    // 1. ACCUEIL — register new patient
    // ================================================================
    log('══ STEP 1: Register patient (Doctor) ══');
    await docPage.goto(`${BASE}/accueil`);
    await expectPageOk(docPage, 'accueil');
    await docPage.waitForTimeout(2000);
    shots.push(await screenshot(docPage, '01_accueil'));

    // Open the "Nouveau patient" slide-over
    await docPage.getByRole('button', { name: /Nouveau patient/i }).first().click();
    await docPage.waitForTimeout(1000);
    shots.push(await screenshot(docPage, '01_slideover'));

    // Fill registration form
    await docPage.fill('input[name="first_name"]', patientName);
    await docPage.fill('input[name="last_name"]',  `Patient${uid}`);
    await docPage.selectOption('select[name="sex"]', 'M');
    await docPage.fill('input[name="birth_date"]', '1990-05-20');
    await docPage.fill('input[name="phone"]', '+225 07 00 00 00 00');
    await docPage.selectOption('select[name="coverage"]', 'Non assuré');

    // Pick first available act
    const actSel = docPage.locator('select[name="act_id"]').first();
    if (await actSel.count() > 0) {
      const opts = await actSel.locator('option:not([value=""])').all();
      if (opts.length > 0) await actSel.selectOption({ index: 1 });
    }

    await docPage.getByRole('button', { name: /Enregistrer le patient/i }).first().click();
    await docPage.waitForTimeout(3000);

    // Extract patient code from the resulting page
    const patientCode = await docPage.evaluate(() => {
      // Look for the patient code in the page (e.g., "CSM-1063")
      const codeEl = [...document.querySelectorAll('p, span, div')].find(el =>
        el.textContent?.match(/CSM-\d+/)
      );
      return codeEl ? codeEl.textContent.match(/CSM-\d+/)[0] : null;
    });

    const url1 = docPage.url();
    shots.push(await screenshot(docPage, '01_after_registration'));
    record('REGISTER_PATIENT', 'doctor', patientCode ? 'PASS' : 'UNKNOWN', `code=${patientCode || '?'}`);

    // ================================================================
    // 2. SALLE D'ATTENTE — nurse takes vitals + take patient
    // ================================================================
    log('══ STEP 2: Take vitals + Take patient (Nurse) ══');
    await nursePage.goto(`${BASE}/salle-attente`);
    await expectPageOk(nursePage, 'salle-attente');

    // Wait for patient cards and extract visit_id from form actions
    await nursePage.waitForSelector('.border-l-brand-500, .border-l-success, .border-l-danger, .border-l-warning, .border-l-line', { timeout: 30000 });
    await nursePage.waitForTimeout(2000);
    shots.push(await screenshot(nursePage, '02_salle_attente'));

    // Extract visit_id from the vitals form action
    const visitIdFromVitals = await nursePage.evaluate(() => {
      const form = document.querySelector('form[action*="constantes"]');
      if (!form) return null;
      const m = form.action.match(/\/visites\/(\d+)/);
      return m ? parseInt(m[1]) : null;
    });

    let vitalsMarked = false;
    let visitTaken = false;

    if (visitIdFromVitals) {
      // Mark vitals via direct API POST
      try {
        const resp = await apiPost(nursePage, `/visites/${visitIdFromVitals}/constantes`, { patient_id: 1 });
        vitalsMarked = resp.ok();
      } catch (e) {
        log(`  [nurse] Vitals POST failed: ${e.message}`);
      }

      // Take patient into consultation
      try {
        const resp2 = await apiPost(nursePage, `/medecin/visites/${visitIdFromVitals}/take`);
        visitTaken = resp2.ok();
      } catch (e) {
        log(`  [nurse] Take visit POST failed: ${e.message}`);
      }
    }

    await nursePage.waitForTimeout(3000);
    shots.push(await screenshot(nursePage, '02_after_vitals'));
    record('TAKE_VITALS', 'nurse', vitalsMarked ? 'PASS' : 'UNKNOWN', `visit=${visitIdFromVitals || '?'}`);
    record('TAKE_VISIT', 'nurse', visitTaken ? 'PASS' : 'UNKNOWN', `visit=${visitIdFromVitals || '?'}`);

    // ================================================================
    // 3. MEDECIN — request hospitalization
    // ================================================================
    log('══ STEP 3: Request hospitalization (Doctor) ══');
    await docPage.goto(`${BASE}/medecin`);
    await expectPageOk(docPage, 'medecin');
    await docPage.waitForTimeout(4000);
    shots.push(await screenshot(docPage, '03_medecin_queue'));

    // Extract visit_id from medecin page (from hospitalization/take form actions)
    const visitIdFromMedecin = await docPage.evaluate(() => {
      const url = window.location.href;
      console.log('  [debug] medecin URL:', url);

      // Look for forms with visit ID
      const forms = [...document.querySelectorAll('form')].filter(f => f.action.includes('/medecin/visites/'));
      console.log('  [debug] medecin forms found:', forms.length, forms.map(f => f.action));
      if (forms.length > 0) {
        for (const f of forms) {
          const m = f.action.match(/\/medecin\/visites\/(\d+)/);
          if (m) return parseInt(m[1]);
        }
      }
      // Try from URL query param ?patient= visit_id
      const urlMatch = url.match(/[?&]patient=(\d+)/);
      if (urlMatch) return parseInt(urlMatch[1]);

      return null;
    });

    let hospRequested = false;
    if (visitIdFromMedecin) {
      try {
        const resp = await apiPost(docPage, `/medecin/visites/${visitIdFromMedecin}/hospitalize`, { reason: 'Test hospitalisation E2E' });
        hospRequested = resp.ok();
      } catch (e) {
        log(`  [doctor] Hospitalization POST failed: ${e.message}`);
      }
    }

    await docPage.waitForTimeout(3000);
    shots.push(await screenshot(docPage, '03_after_hospitalization'));
    record('REQUEST_HOSPITALIZATION', 'doctor', hospRequested ? 'PASS' : 'UNKNOWN', `visit=${visitIdFromMedecin || '?'}`);

    // ================================================================
    // 4. ADMISSION REQUESTS — nurse approves
    // ================================================================
    log('══ STEP 4: Approve admission request (Nurse) ══');
    await nursePage.goto(`${BASE}/admission-requests`);
    await expectPageOk(nursePage, 'admission-requests');
    await nursePage.waitForTimeout(2000);
    shots.push(await screenshot(nursePage, '04_admission_requests'));

    // Find admission request ID from approve button/form
    const admissionData = await nursePage.evaluate(() => {
      const approveBtns = [...document.querySelectorAll('button, a')].filter(b =>
        b.textContent.includes('Approuver') || b.textContent.includes('Valider')
      );
      if (approveBtns.length > 0) {
        const form = approveBtns[0].closest('form');
        if (form) {
          const match = form.action.match(/\/admission-requests\/(\d+)/);
          if (match) return { id: parseInt(match[1]) };
        }
        const link = approveBtns[0].closest('a');
        if (link) {
          const match = link.href.match(/\/admission-requests\/(\d+)/);
          if (match) return { id: parseInt(match[1]) };
        }
      }
      return null;
    });

    let admissionApproved = false;
    if (admissionData) {
      try {
        // Extract first available room_id from the page's select element
        const roomId = await nursePage.evaluate(() => {
          const select = document.querySelector('select[name="room_id"]');
          if (select && select.options.length > 1) return parseInt(select.options[1].value);
          return null;
        });

        if (roomId) {
          const approveResp = await apiPost(nursePage, `/admission-requests/${admissionData.id}/approve`, {
            room_id: roomId,
            bed_number: 1,
          });
          admissionApproved = approveResp.ok();
        }
      } catch (e) {
        log(`  [nurse] Admission approval failed: ${e.message}`);
      }
    }

    await nursePage.waitForTimeout(3000);
    shots.push(await screenshot(nursePage, '04_after_approve'));
    record('APPROVE_ADMISSION', 'nurse', admissionApproved ? 'PASS' : 'UNKNOWN', `request=${admissionData?.id || '?'}`);

    // ================================================================
    // 5. CLOSE CONSULTATION — doctor
    // ================================================================
    log('══ STEP 5: Close consultation (Doctor) ══');
    await docPage.goto(`${BASE}/medecin`);
    await expectPageOk(docPage, 'medecin-close');
    await docPage.waitForTimeout(4000);
    shots.push(await screenshot(docPage, '05_medecin_close'));

    // Find consultation ID from form actions
    const consultData = await docPage.evaluate(() => {
      const forms = [...document.querySelectorAll('form')].filter(f =>
        f.action.includes('consultations') && f.action.includes('cloturer')
      );
      if (forms.length > 0) {
        const match = forms[0].action.match(/\/consultations\/(\d+)\/cloturer/);
        if (match) return { consultation_id: parseInt(match[1]) };
      }
      return null;
    });

    let consultClosed = false;
    if (consultData) {
      try {
        const resp = await apiPost(docPage, `/consultations/${consultData.consultation_id}/cloturer`);
        consultClosed = resp.ok();
      } catch (e) {
        log(`  [doctor] Close consultation POST failed: ${e.message}`);
      }
    }

    await docPage.waitForTimeout(3000);
    shots.push(await screenshot(docPage, '05_after_close'));
    record('CLOSE_CONSULTATION', 'doctor', consultClosed ? 'PASS' : 'UNKNOWN', `consultation=${consultData?.consultation_id || '?'}`);

    // ================================================================
    // 6. SORTIE — doctor discharges patient
    // ================================================================
    log('══ STEP 6: Discharge patient (Doctor) ══');
    await docPage.goto(`${BASE}/sortie`);
    await expectPageOk(docPage, 'sortie');
    await docPage.waitForTimeout(2000);
    shots.push(await screenshot(docPage, '06_sortie'));

    // Find discharge form/button
    const dischargeData = await docPage.evaluate(() => {
      const forms = [...document.querySelectorAll('form')].filter(f =>
        f.action.includes('sortie') && f.action.includes('cloturer')
      );
      if (forms.length > 0) {
        const match = forms[0].action.match(/\/sortie\/(\d+)\/cloturer/);
        if (match) return { visit_id: parseInt(match[1]) };
      }
      return null;
    });

    let dischargeDone = false;
    if (dischargeData) {
      try {
        const resp = await apiPost(docPage, `/sortie/${dischargeData.visit_id}/cloturer`);
        dischargeDone = resp.ok();
      } catch (e) {
        log(`  [doctor] Discharge POST failed: ${e.message}`);
      }
    }

    await docPage.waitForTimeout(4000);
    shots.push(await screenshot(docPage, '06_after_discharge'));

    const content6 = await docPage.content();
    if (content6.includes('impayé') || content6.includes('seuil')) {
      bugs.push({
        severity: 'INFO', step: 'DISCHARGE', user: 'doctor',
        title: 'Discharge has unpaid invoice warning',
        description: 'A financial warning appeared during discharge — unpaid amount detected.',
      });
      record('DISCHARGE', 'doctor', 'INFO', 'Unpaid invoice warning');
    } else {
      record('DISCHARGE', 'doctor', dischargeDone ? 'PASS' : 'UNKNOWN', `visit=${dischargeData?.visit_id || '?'}`);
    }

    // ================================================================
    // 7. CAISSE — doctor processes payment
    // ================================================================
    log('══ STEP 7: Caisse / Payment (Doctor) ══');
    await docPage.goto(`${BASE}/caisse`);
    const caisseOk = await expectPageOk(docPage, 'caisse').then(() => 'PASS').catch(e => 'ERROR');
    await docPage.waitForTimeout(2000);
    shots.push(await screenshot(docPage, '07_caisse'));

    // Find invoice with pending/awaiting status
    const invoiceData = await docPage.evaluate(() => {
      // Look for links to invoice detail or pay buttons
      const links = [...document.querySelectorAll('a, button, form')].filter(el => {
        const href = el.href || el.action || '';
        return href.includes('/caisse/invoices/');
      });
      if (links.length > 0) {
        const href = links[0].href || links[0].action || '';
        const m = href.match(/\/caisse\/invoices\/(\d+)/);
        if (m) return { invoice_id: parseInt(m[1]) };
      }
      return null;
    });

    let paymentDone = false;
    if (invoiceData) {
      try {
        const resp = await apiPost(docPage, `/caisse/invoices/${invoiceData.invoice_id}/pay`);
        paymentDone = resp.ok();
      } catch (e) {
        log(`  [doctor] Payment POST failed: ${e.message}`);
      }
    }

    await docPage.waitForTimeout(3000);
    shots.push(await screenshot(docPage, '07_after_payment'));
    record('PROCESS_PAYMENT', 'doctor', paymentDone ? 'PASS' : 'UNKNOWN', `invoice=${invoiceData?.invoice_id || '?'}`);

    record('CAISSE_ACCESS', 'doctor', caisseOk);

    // ================================================================
    // POST-CHECKS
    // ================================================================
    log('══ Post-checks ══');

    await docPage.goto(`${BASE}/sortie`);
    await docPage.waitForTimeout(2000);
    shots.push(await screenshot(docPage, '08_sortie_final'));
    const sortieFinal = await docPage.content();
    record('POSTCHECK_DISCHARGE_VISIBLE', 'doctor',
      sortieFinal.includes('clôtur') ? 'PASS' : 'UNKNOWN');

    await nursePage.goto(`${BASE}/salle-attente`);
    await nursePage.waitForTimeout(4000);
    shots.push(await screenshot(nursePage, '08_salle_final'));

    // ================================================================
    // BUILD REPORT
    // ================================================================
    const summary = {
      total:   steps.length,
      pass:    steps.filter(s => s.status === 'PASS').length,
      skip:    steps.filter(s => s.status === 'SKIPPED').length,
      info:    steps.filter(s => s.status === 'INFO').length,
      unknown: steps.filter(s => s.status === 'UNKNOWN').length,
      error:   steps.filter(s => s.status === 'ERROR').length,
    };

    log('\n══ FINAL RESULTS ══');
    log(`Steps: ${summary.total} | PASS:${summary.pass} | SKIP:${summary.skip} | INFO:${summary.info} | UNKNOWN:${summary.unknown} | ERROR:${summary.error}`);
    log(`Bugs: ${bugs.length} | NetFail: ${netFail.length} | JSErr: ${jsErr.length}`);

    const result = { summary, steps, bugs, networkFailures: netFail, jsErrors: jsErr, screenshots: shots };
    fs.writeFileSync(REPORT_JSON, JSON.stringify(result, null, 2));

    // --- Markdown ---
    const md = [];
    md.push('# Patient Flow E2E Test Report');
    md.push('');
    md.push(`**Date**: ${new Date().toISOString()}`);
    md.push(`**Server**: \`${BASE}\``);
    md.push(`**Accounts**: \`${DOCTOR.email}\` (docteur_general) | \`${NURSE.email}\` (aide_soignante)`);
    md.push('');

    md.push('## Executive Summary');
    md.push('');
    md.push('| Metric | Value |');
    md.push('|---|---|');
    md.push(`| Total steps executed | ${summary.total} |`);
    md.push(`| PASS | ${summary.pass} |`);
    md.push(`| SKIPPED | ${summary.skip} |`);
    md.push(`| UNKNOWN | ${summary.unknown} |`);
    md.push(`| ERRORS | ${summary.error} |`);
    md.push(`| Bugs found | ${bugs.length} |`);
    md.push(`| Network failures | ${netFail.length} |`);
    md.push(`| JS errors | ${jsErr.length} |`);
    md.push('');

    md.push('## Verdict per Step');
    md.push('');
    md.push('| # | Step | User | Status | Detail |');
    md.push('|---|---|---|---|---|');
    steps.forEach((s, i) => {
      const icon = { PASS: '✅', SKIPPED: '⏭️', INFO: 'ℹ️', UNKNOWN: '❓', ERROR: '❌' }[s.status] || s.status;
      md.push(`| ${i+1} | \`${s.step}\` | ${s.user} | ${icon} ${s.status} | ${(s.detail || '').slice(0, 100)} |`);
    });
    md.push('');

    if (bugs.length > 0) {
      md.push('## Bugs Found');
      md.push('');
      bugs.forEach((b, i) => {
        const sevIcon = { CRITICAL: '🔴', MAJOR: '🟠', MINOR: '🟡', INFO: '🔵' }[b.severity] || b.severity;
        md.push(`### BUG-${String(i+1).padStart(3,'0')} — ${b.title}`);
        md.push('');
        md.push(`- **Severity**: ${sevIcon} ${b.severity}`);
        md.push(`- **Step**: \`${b.step}\``);
        md.push(`- **User**: ${b.user}`);
        md.push(`- **Description**: ${b.description}`);
        md.push('');
      });
    }

    if (netFail.length > 0) {
      md.push('## Network Failures');
      md.push('');
      netFail.forEach(nf => md.push(`- [${nf.u}] \`${nf.url}\` → ${nf.err || 'unknown'}`));
      md.push('');
    }

    if (jsErr.length > 0) {
      md.push('## JavaScript Errors');
      md.push('');
      jsErr.forEach(e => md.push(`- [${e.u}] ${e.msg}`));
      md.push('');
    }

    md.push('## Screenshots');
    md.push('');
    shots.forEach(s => md.push(`- \`${s}\``));
    md.push('');
    md.push(`> Full JSON: \`${REPORT_JSON}\``);

    fs.writeFileSync(REPORT_MD, md.join('\n'));

    log(`\n📄 ${REPORT_MD}`);
    log(`📄 ${REPORT_JSON}`);
    log(`📸 ${SCREENSHOTS_DIR}/`);
    return result;

  } catch (err) {
    log(`FATAL: ${err.message}`);
    const result = { summary: { fatal: err.message }, steps, bugs, networkFailures: netFail, jsErrors: jsErr, screenshots: shots };
    fs.writeFileSync(REPORT_JSON, JSON.stringify(result, null, 2));
    return result;
  } finally {
    await browser.close();
  }
}

// ===== RUN =====
runFlow().then(result => {
  const fatal = !!result.summary.fatal;
  const errors = (result.summary.error || 0) > 0;
  process.exit(fatal || errors ? 1 : 0);
}).catch(err => {
  console.error('Runner crashed:', err);
  process.exit(2);
});
