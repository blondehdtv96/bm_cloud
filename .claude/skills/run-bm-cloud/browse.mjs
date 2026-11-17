// Zero-dependency headless-Chrome driver for bm_cloud (Chrome DevTools Protocol
// over Node's built-in WebSocket; needs Node >= 22 and Google Chrome installed).
//
// Logs in through the real login form, then visits each route given and
// screenshots it.
//
//   node .claude/skills/run-bm-cloud/browse.mjs [route ...]
//   node .claude/skills/run-bm-cloud/browse.mjs dashboard drive admin/users
//
// Env: BM_URL (default http://127.0.0.1:8000), BM_USER/BM_PASS (admin/admin123),
//      CHROME (path to chrome), SHOTS (output dir, default <unit>/storage/logs/shots (gitignored)),
//      BM_WIDTH/BM_HEIGHT (viewport, default 1366x850; use 390x844 for mobile),
//      BM_EVAL (async JS body run on each route after its screenshot, e.g. to
//      change a filter; may call sleep(ms); return value printed; then <name>_after.png).
import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, existsSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';

const BASE = process.env.BM_URL || 'http://127.0.0.1:8000';
const USER = process.env.BM_USER || 'admin';
const PASS = process.env.BM_PASS || 'admin123';
const W = +(process.env.BM_WIDTH || 1366), H = +(process.env.BM_HEIGHT || 850);
const SHOTS = resolve(process.env.SHOTS || 'storage/logs/shots');
// Routes may be given without the leading slash ("dashboard"): Git Bash/MSYS rewrites
// "/dashboard" into "C:/Program Files/Git/dashboard" before node ever sees it.
const routes = (process.argv.slice(2).length ? process.argv.slice(2) : ['dashboard', 'drive'])
  .map((r) => '/' + r.replace(/^[A-Za-z]:[\\/].*?Git[\\/]/, '').replace(/^\/+/, ''));
const CHROME = process.env.CHROME || [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
].find(existsSync);
if (!CHROME) throw new Error('Chrome not found; set CHROME=/path/to/chrome');
mkdirSync(SHOTS, { recursive: true });

const port = 9300 + Math.floor(Math.random() * 500);
const profile = mkdtempSync(join(tmpdir(), 'bmcloud-chrome-'));
const chrome = spawn(CHROME, [
  '--headless=new', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`,
  '--no-first-run', '--no-default-browser-check', '--disable-gpu', '--no-sandbox',
  `--window-size=${W},${H}`, 'about:blank',
], { stdio: 'ignore' });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let target;
for (let i = 0; i < 50 && !target; i++) {
  try {
    const list = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    target = list.find((t) => t.type === 'page');
  } catch { await sleep(200); }
}
if (!target) { chrome.kill(); throw new Error('Chrome DevTools did not come up'); }

const ws = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((r, j) => { ws.onopen = r; ws.onerror = j; });
let id = 0; const pending = new Map(); const errors = [];
ws.onmessage = (m) => {
  const msg = JSON.parse(m.data);
  if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); }
  if (msg.method === 'Runtime.exceptionThrown') errors.push(msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text);
  if (msg.method === 'Runtime.consoleAPICalled' && msg.params.type === 'error') errors.push(msg.params.args.map((a) => a.value ?? a.description).join(' '));
};
const send = (method, params = {}) => new Promise((r, j) => {
  const n = ++id; pending.set(n, (msg) => (msg.error ? j(new Error(`${method}: ${msg.error.message}`)) : r(msg.result)));
  ws.send(JSON.stringify({ id: n, method, params }));
});
const evaluate = async (expr) => (await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true })).result.value;
const waitFor = async (expr, ms = 15000) => {
  for (const end = Date.now() + ms; Date.now() < end; await sleep(200)) if (await evaluate(expr)) return true;
  throw new Error(`timeout waiting for: ${expr}`);
};
const shot = async (name) => {
  const { data } = await send('Page.captureScreenshot', { format: 'png' });
  const file = join(SHOTS, `${name}.png`); writeFileSync(file, Buffer.from(data, 'base64'));
  console.log('screenshot', file);
};
const go = async (path) => { await send('Page.navigate', { url: BASE + path }); await waitFor('document.readyState === "complete"'); };

try {
  await send('Page.enable'); await send('Runtime.enable');
  await send('Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: 1, mobile: W < 600 });

  // Login through the real form (Vue v-model listens to `input` events).
  await go('/login');
  await waitFor('!!document.querySelector("input[placeholder=\'Masukkan username\']")');
  await shot('login');
  await evaluate(`(() => {
    const set = (el, v) => { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); };
    set(document.querySelector("input[placeholder='Masukkan username']"), ${JSON.stringify(USER)});
    set(document.querySelector("input[placeholder='Masukkan kata sandi']"), ${JSON.stringify(PASS)});
    document.querySelector('button[type=submit]').click();
  })()`);
  await waitFor('!!localStorage.getItem("bmclouds_token") && !location.pathname.startsWith("/login")');
  console.log('logged in as', USER, '->', await evaluate('location.pathname'));

  for (const r of routes) {
    await go(r);
    await sleep(1500); // let lazy-loaded view + API calls settle
    console.log('at', await evaluate('location.pathname'), '|', (await evaluate('document.body.innerText')).replace(/\s+/g, ' ').slice(0, 120));
    const name = r.replace(/^\//, '').replace(/[\/:?]/g, '_') || 'home';
    await shot(name);
    if (process.env.BM_EVAL) {
      // Async function body run in the page; may use `sleep(ms)`. Return value is printed.
      const out = await evaluate(`(async () => { const sleep = (ms) => new Promise(r => setTimeout(r, ms)); ${process.env.BM_EVAL} })()`);
      console.log('eval ->', typeof out === 'string' ? out : JSON.stringify(out));
      await shot(`${name}_after`);
    }
  }
  if (errors.length) console.log('PAGE ERRORS:\n  ' + errors.join('\n  ')); else console.log('no page errors');
} finally {
  ws.close(); chrome.kill();
}
