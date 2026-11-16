// Render an SVG to PNG at 2x for print, using the local Chrome.
// usage: node render_svg.cjs <input.svg> <output.png>
const fs = require("fs");
const path = require("path");
const puppeteer = require(path.join(
  "C:\\Users\\Admin\\AppData\\Local\\Temp\\opencode\\shot", "node_modules", "puppeteer-core"));

const CHROME = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";

(async () => {
  const [input, output] = process.argv.slice(2);
  if (!input || !output) {
    console.error("usage: node render_svg.cjs <input.svg> <output.png>");
    process.exit(2);
  }
  const svg = fs.readFileSync(input, "utf8");
  const width = Number((svg.match(/width="([\d.]+)"/) || [])[1]);
  const height = Number((svg.match(/height="([\d.]+)"/) || [])[1]);
  if (!width || !height) {
    console.error("could not read width/height from the SVG");
    process.exit(3);
  }
  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: true,
    args: ["--no-sandbox", "--disable-dev-shm-usage", "--font-render-hinting=none"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 2 });
  await page.setContent(
    `<style>html,body{margin:0;padding:0;background:#fff}svg{display:block}</style>${svg}`,
    { waitUntil: "load" });
  await page.screenshot({ path: output, clip: { x: 0, y: 0, width, height } });
  await browser.close();
  console.log(`rendered ${output} from ${width}x${height} at 2x`);
})().catch((error) => {
  console.error("FAILED:", error.message);
  process.exit(1);
});
