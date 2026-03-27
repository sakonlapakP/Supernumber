import { readFile } from 'node:fs/promises';
import process from 'node:process';
import { chromium } from 'playwright';

async function main() {
  const [, , htmlPath, outputPath, selectorArg] = process.argv;
  const selector = selectorArg || '.article-lottery-poster__frame';

  if (!htmlPath || !outputPath) {
    console.error('Usage: node scripts/render_lottery_cover.mjs <htmlPath> <outputPath> [selector]');
    process.exit(1);
  }

  const html = await readFile(htmlPath, 'utf8');
  const browser = await chromium.launch({ headless: true });

  try {
    const page = await browser.newPage({
      viewport: { width: 1200, height: 1200 },
      deviceScaleFactor: 2,
    });

    await page.setContent(html, { waitUntil: 'load' });
    await page.evaluate(async () => {
      if (document.fonts && document.fonts.ready) {
        await document.fonts.ready;
      }
    });
    await page.waitForTimeout(350);

    const target = await page.$(selector);
    if (!target) {
      throw new Error(`Target selector not found: ${selector}`);
    }

    await target.screenshot({
      path: outputPath,
      type: 'png',
    });
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  const message = error instanceof Error ? error.stack || error.message : String(error);
  console.error(message);
  process.exit(1);
});
