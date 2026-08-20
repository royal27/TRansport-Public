const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await page.goto('http://localhost:8000/public/route.php');
  await page.screenshot({ path: 'route_desktop.png' });
  await page.setViewportSize({ width: 375, height: 812 }); // Mobile
  await page.goto('http://localhost:8000/public/route.php');
  await page.screenshot({ path: 'route_mobile.png' });
  await browser.close();
})();
