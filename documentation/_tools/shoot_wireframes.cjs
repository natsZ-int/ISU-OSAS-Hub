// shoot.js - screenshot every wireframe at desktop, tablet and mobile widths.
// Usage: node shoot.js <wireframesDir> <exportDir> [id ...]
const fs = require("fs");
const path = require("path");
const puppeteer = require(path.join(
  "C:\\Users\\Admin\\AppData\\Local\\Temp\\opencode\\shot", "node_modules", "puppeteer-core"));

const CHROME = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";

const [, , wfDir, outDir, ...only] = process.argv;
const VIEWPORTS = [
  { name: "desktop", width: 1440, height: 900 },
  { name: "tablet", width: 1024, height: 800 },
  { name: "mobile", width: 390, height: 844 },
];
// Optional overrides, used for single plates that must be captured at their own size.
if (process.env.SHOT_WIDTH || process.env.SHOT_HEIGHT) {
  const v = VIEWPORTS[0];
  if (process.env.SHOT_WIDTH) v.width = parseInt(process.env.SHOT_WIDTH, 10);
  if (process.env.SHOT_HEIGHT) v.height = parseInt(process.env.SHOT_HEIGHT, 10);
}

function walk(dir, acc = []) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === "export") continue;
      walk(p, acc);
    } else if (e.name.endsWith(".html") && e.name !== "index.html") {
      acc.push(p);
    }
  }
  return acc;
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  let files = walk(wfDir).sort();
  if (only.length) files = files.filter((f) => only.some((o) => path.basename(f, ".html") === o));

  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: true,
    args: ["--no-sandbox", "--disable-dev-shm-usage", "--font-render-hinting=none"],
  });
  const page = await browser.newPage();

  let n = 0;
  const failures = [];
  for (const file of files) {
    const id = path.basename(file, ".html");
    for (const vp of VIEWPORTS) {
      const outName = `${id}--${vp.name}.png`;
      const outPath = path.join(outDir, outName);
      // skip existing unless the source is newer
      if (fs.existsSync(outPath) && fs.statSync(outPath).mtimeMs > fs.statSync(file).mtimeMs) {
        n++;
        continue;
      }
      await page.setViewport({
        width: vp.width,
        height: vp.height,
        deviceScaleFactor: 1,
      });
      const url = "file:///" + path.resolve(file).replace(/\\/g, "/") + "?embed=1&vp=" + vp.name;
      try {
        await page.goto(url, { waitUntil: "load", timeout: 30000 });
        await new Promise((r) => setTimeout(r, 120));
        const h = await page.evaluate(() => document.documentElement.scrollHeight);
        if (!h || h < 300) throw new Error("empty render, height=" + h);
        await page.screenshot({ path: outPath, fullPage: true, type: "png" });
        n++;
      } catch (err) {
        failures.push(`${outName}: ${err.message}`);
      }
    }
    process.stdout.write(`\r${path.basename(file)}            `);
  }

  await browser.close();
  console.log(`\nshots written: ${n}   failures: ${failures.length}`);
  failures.forEach((f) => console.log("  FAIL " + f));
  process.exit(failures.length ? 1 : 0);
})();
