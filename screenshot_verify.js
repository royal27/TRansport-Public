const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

  await page.goto('http://localhost:8000/bypass_admin.php');
  await page.waitForTimeout(2000); // Wait for map to load
  await page.screenshot({ path: 'admin_draw_lines.png', fullPage: true });

  await browser.close();
})();
