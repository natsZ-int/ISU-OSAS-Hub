const fs = require("fs");
const path = require("path");
const puppeteer = require(path.join("C:\\Users\\Admin\\AppData\\Local\\Temp\\opencode\\shot", "node_modules", "puppeteer-core"));
const CHROME = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const BASE = "http://127.0.0.1:8123";
const OUT = "documentation/Portfolio/figures";
const CREDS = {
  admin: { email: "admin@isu.local", password: "ChangeMe!Admin2025" },
  student: { email: "fortunatoalindayu@gmail.com", password: "DemoStudent2026!" },
};
async function login(page, who) {
  await page.goto(`${BASE}/login`, { waitUntil: "load" });
  await page.type("#email", CREDS[who].email);
  await page.type("#password", CREDS[who].password);
  await Promise.all([page.waitForNavigation({ waitUntil: "load" }), page.click("button[type=submit]")]);
}
async function shot(page, url, name, full = false) {
  await page.goto(BASE + url, { waitUntil: "load" });
  await new Promise((r) => setTimeout(r, 900));
  await page.screenshot({ path: path.join(OUT, name + ".png"), fullPage: full });
  console.log(name, "|", (await page.title()).slice(0, 60));
}
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ["--no-sandbox", "--disable-dev-shm-usage", "--font-render-hinting=none"] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 980, deviceScaleFactor: 1.5 });
  await login(page, "student");
  await shot(page, "/student/scholarships/search?q=school", "student-scholarship-search");
  await page.deleteCookie(...(await page.cookies()));
  await login(page, "admin");
  await shot(page, "/admin/analytics", "admin-analytics-full", true);
  await shot(page, "/admin/analytics/guidance", "admin-analytics-guidance-full", true);
  await shot(page, "/admin/audit-logs", "admin-audit-full", true);
  await shot(page, "/admin/users", "admin-users-full", true);
  await browser.close();
  console.log("done");
})().catch((e) => { console.error("FAILED:", e.message); process.exit(1); });
