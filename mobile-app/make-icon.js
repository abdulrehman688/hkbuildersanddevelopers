// Draws the HK CRM app icon and splash screen (navy and gold) into assets/,
// then `npm run icons` lets @capacitor/assets make every Android size.
const fs = require('fs');
const sharp = require('sharp');

const NAVY = '#0a1628', GOLD = '#c9a84c';
const mark = (size, ring) => `
  <circle cx="${size / 2}" cy="${size / 2}" r="${size * 0.30}" fill="none" stroke="${GOLD}" stroke-width="${size * 0.022}"/>
  <text x="50%" y="50%" dy="0.35em" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif"
        font-weight="700" font-size="${size * 0.25}" fill="${GOLD}" letter-spacing="${size * 0.01}">HK</text>
  ${ring ? `<text x="50%" y="${size * 0.88}" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700"
        font-size="${size * 0.05}" fill="${GOLD}" letter-spacing="${size * 0.012}">CRM</text>` : ''}`;
const svg = (size, bg, ring) => Buffer.from(
  `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}">
     ${bg ? `<rect width="100%" height="100%" fill="${NAVY}"/>` : ''}${mark(size, ring)}</svg>`);

(async () => {
  fs.mkdirSync('assets', { recursive: true });
  await sharp(svg(1024, true, false)).png().toFile('assets/icon-only.png');
  // Adaptive icon: the mark kept inside the centre safe zone, navy behind it
  await sharp(Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024">
      <g transform="translate(205 205) scale(0.6)">${mark(1024, false)}</g></svg>`)).png().toFile('assets/icon-foreground.png');
  await sharp({ create: { width: 1024, height: 1024, channels: 4, background: NAVY } }).png().toFile('assets/icon-background.png');
  const splash = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="2732" height="2732">
      <rect width="100%" height="100%" fill="${NAVY}"/>
      <g transform="translate(966 966) scale(0.78)">${mark(1024, true)}</g></svg>`);
  await sharp(splash).png().toFile('assets/splash.png');
  await sharp(splash).png().toFile('assets/splash-dark.png');
  console.log('assets ready');
})();
