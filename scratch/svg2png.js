import { chromium } from 'playwright';
import fs from 'fs';

const svgPath = process.argv[2];
const pngPath = process.argv[3];

if (!svgPath || !pngPath) {
    process.exit(1);
}

(async () => {
    const browser = await chromium.launch({
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    try {
        const page = await browser.newPage();
        const svgContent = fs.readFileSync(svgPath, 'utf8');
        
        // Extract dimensions
        const widthMatch = svgContent.match(/width="(\d+)"/);
        const heightMatch = svgContent.match(/height="(\d+)"/);
        const width = widthMatch ? parseInt(widthMatch[1]) : 1200;
        const height = heightMatch ? parseInt(heightMatch[1]) : 1200;

        await page.setViewportSize({ width, height });
        await page.setContent(`
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { margin: 0; padding: 0; overflow: hidden; background: transparent; }
                    svg { display: block; }
                </style>
            </head>
            <body>
                ${svgContent}
            </body>
            </html>
        `);

        // Wait for a brief moment for fonts to render
        await page.waitForTimeout(500);

        await page.screenshot({ 
            path: pngPath, 
            omitBackground: true,
            type: 'png'
        });
    } catch (e) {
        console.error(e);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
