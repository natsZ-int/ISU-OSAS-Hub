// Capture real screenshots of the running ISU OSAS Hub for the portfolio.
// Runs against the temporary copy on 127.0.0.1:8123; writes into documentation/Portfolio/figures.
const fs = require("fs");
const path = require("path");
const puppeteer = require(path.join(
  "C:\\Users\\Admin\\AppData\\Local\\Temp\\opencode\\shot", "node_modules", "puppeteer-core"));

const CHROME = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const BASE = "http://127.0.0.1:8123";
const OUT = process.argv[2];
fs.mkdirSync(OUT, { recursive: true });

const CREDS = {
  admin: { email: "admin@isu.local", password: "ChangeMe!Admin2025" },
  counselor: { email: "counselor@isu.local", password: "ChangeMe!Counselor2025" },
  student: { email: "fortunatoalindayu@gmail.com", password: "DemoStudent2026!" },
};

async function login(page, who) {
  await page.goto(`${BASE}/login`, { waitUntil: "load" });
  await page.type("#email", CREDS[who].email);
  await page.type("#password", CREDS[who].password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: "load" }),
    page.click("button[type=submit]"),
  ]);
  const url = page.url();
  if (url.includes("/login")) throw new Error(`login failed for ${who}`);
  return url;
}

async function shot(page, url, name, opts = {}) {
  await page.goto(BASE + url, { waitUntil: "load", timeout: 30000 });
  await new Promise((r) => setTimeout(r, opts.delay ?? 650));
  await page.screenshot({
    path: path.join(OUT, `${name}.png`),
    fullPage: opts.full === true,
  });
  const title = await page.title();
  console.log(`  ${name.padEnd(32)} ${url}  | ${title.slice(0, 48)}`);
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: true,
    args: ["--no-sandbox", "--disable-dev-shm-usage", "--font-render-hinting=none"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 980, deviceScaleFactor: 1.5 });

  console.log("guest");
  await shot(page, "/login", "login");
  await shot(page, "/register", "register");

  console.log("student");
  console.log("  landed", await login(page, "student"));
  await shot(page, "/student/dashboard", "student-dashboard");
  await shot(page, "/student/scholarships", "student-scholarships");
  await shot(page, "/student/scholarships/4", "student-scholarship-show");
  await shot(page, "/student/scholarships/4/eligibility", "student-eligibility");
  await shot(page, "/student/recommended-scholarships", "student-recommendations");
  await shot(page, "/student/recommended-scholarships/matches", "student-matches");
  await shot(page, "/student/applications", "student-applications");
  await shot(page, "/student/applications/1", "student-application-show");
  await shot(page, "/student/guidance/counselors", "student-counselors");
  await shot(page, "/student/guidance/counselors/2/slots?date=2026-11-16", "student-slots");
  await shot(page, "/student/services", "student-services");
  await shot(page, "/student/services/1/apply", "student-service-create");
  await shot(page, "/student/service-requests", "student-service-index");
  await shot(page, "/student/service-requests/2", "student-service-show");
  await shot(page, "/student/profile", "student-profile");
  await shot(page, "/student/profile/security", "student-profile-security");
  await shot(page, "/notifications", "student-notifications");

  // book one appointment through the interface so the counselor queue is real
  console.log("  booking an appointment through the UI");
  await page.goto(`${BASE}/student/guidance/counselors/2/slots?date=2026-11-16`, { waitUntil: "load" });
  await page.type("textarea[name=reason]", "Consultation about scholarship requirements for the next term.");
  const book = await page.$("form[action$='appointments'] button[type=submit]");
  if (book) {
    await Promise.all([page.waitForNavigation({ waitUntil: "load" }), book.click()]);
    console.log("  booked:", page.url());
  } else {
    console.log("  NO BOOKING BUTTON FOUND");
  }
  await shot(page, "/student/guidance/appointments", "student-appointments");
  const stAppt = await page.$$eval("a[href*='/student/guidance/appointments/']",
    (as) => as.map((a) => a.getAttribute("href")).find((h) => /\/appointments\/\d+$/.test(h)));
  if (stAppt) await shot(page, stAppt.replace(BASE, ""), "student-appointment-show");
  else console.log("  no student appointment detail link");

  await page.deleteCookie(...(await page.cookies()));
  console.log("counselor");
  console.log("  landed", await login(page, "counselor"));
  await shot(page, "/counselor/dashboard", "counselor-dashboard");
  await shot(page, "/counselor/guidance/appointments", "counselor-appointments");
  await page.goto(`${BASE}/counselor/guidance/appointments`, { waitUntil: "load" });
  const apptHref = await page.$$eval("a[href*='/counselor/guidance/appointments/']",
    (as) => as.map((a) => a.getAttribute("href")).find((h) => /\/appointments\/\d+$/.test(h)));
  if (apptHref) await shot(page, apptHref.replace(BASE, ""), "counselor-appointment-show");
  else console.log("  no appointment detail link");
  await shot(page, "/counselor/guidance/availability", "counselor-availability");

  await page.deleteCookie(...(await page.cookies()));
  console.log("admin");
  console.log("  landed", await login(page, "admin"));
  await shot(page, "/admin/dashboard", "admin-dashboard");
  await shot(page, "/admin/users", "admin-users");
  await shot(page, "/admin/users/create", "admin-user-create");
  await shot(page, "/admin/student-profiles", "admin-profiles");
  await shot(page, "/admin/student-profiles/1", "admin-profile-show");
  await shot(page, "/enrollment-verifications", "admin-enrollments");
  await shot(page, "/enrollment-verifications/1", "admin-enrollment-show");
  await shot(page, "/admin/scholarships", "admin-scholarships");
  await shot(page, "/admin/scholarships/create", "admin-scholarship-create");
  await shot(page, "/admin/scholarships/1/requirements", "admin-scholarship-requirements");
  await shot(page, "/admin/scholarship-applications", "admin-applications");
  await shot(page, "/admin/scholarship-applications/1", "admin-application-show");
  await shot(page, "/admin/scholarship-matching/weights", "admin-weights");
  await shot(page, "/admin/guidance/appointment-types", "admin-guidance-types");
  await shot(page, "/admin/guidance/appointments", "admin-appointments");
  await page.goto(`${BASE}/admin/guidance/appointments`, { waitUntil: "load" });
  const adminAppt = await page.$$eval("a[href*='/admin/guidance/appointments/']",
    (as) => as.map((a) => a.getAttribute("href")).find((h) => /\/appointments\/\d+$/.test(h)));
  if (adminAppt) await shot(page, adminAppt.replace(BASE, ""), "admin-appointment-show");
  else console.log("  no admin appointment detail link");
  await shot(page, "/admin/guidance/scheduling-settings", "admin-guidance-settings");
  await shot(page, "/admin/service-types", "admin-service-types");
  await shot(page, "/admin/student-service-requests", "admin-service-requests");
  await shot(page, "/admin/student-service-requests/2", "admin-service-request-show");
  await shot(page, "/admin/analytics", "admin-analytics");
  await shot(page, "/admin/analytics/scholarships", "admin-analytics-scholarships");
  await shot(page, "/admin/analytics/guidance", "admin-analytics-guidance");
  await shot(page, "/admin/analytics/student-services", "admin-analytics-services");
  await shot(page, "/admin/audit-logs", "admin-audit");
  await shot(page, "/admin/audit-logs/1", "admin-audit-show");
  await shot(page, "/notifications", "admin-notifications");
  await shot(page, "/admin/quick-search/users?q=alindayu", "admin-search");

  await browser.close();
  console.log("done");
})().catch((e) => {
  console.error("FAILED:", e.message);
  process.exit(1);
});
