const {webkit,devices} = require('playwright');

(async () => {
  const browser = await webkit.launch(); // or 'firefox','chromium'
  const iPhone11 = devices['iPhone 11']
  const url = 'https://www.google.com'
  const context = await browser.newContext({
    ...iPhone11,
    locale: 'ja'
  });
  const page = await context.newPage();
  await page.goto(url);
  await page.screenshot({ path: 'data/example.png' });
  await browser.close();
})().catch(error => { console.log(error) });