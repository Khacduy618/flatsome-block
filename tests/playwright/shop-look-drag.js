// Kịch bản kiểm kéo pin trong canvas UX Builder (Playwright MCP: browser_run_code_unsafe với filename).
// Trang nháp 17759 trên DDEV: [fbm_shop_look] với 2 Look; user admin tạm fbmqa_l. Sau khi chạy, đọc DB bằng wp-cli.
async (page) => {
  const ctx = await page.context().browser().newContext({ ignoreHTTPSErrors: true, viewport: { width: 1400, height: 900 } });
  const p = await ctx.newPage();
  const errors = [];
  p.on('pageerror', (e) => errors.push(e.message));
  await p.goto('https://handdn.ddev.site/wp-login.php');
  await p.fill('#user_login', 'fbmqa_l'); await p.fill('#user_pass', '40ec204c1f4e1f11');
  await p.click('#wp-submit'); await p.waitForLoadState('networkidle');
  await p.goto('https://handdn.ddev.site/wp-admin/post.php?post=17759&action=edit&app=uxbuilder&type=editor');
  let f = null;
  for (let i = 0; i < 40 && !f; i++) { await p.waitForTimeout(500); f = p.frames().find((fr) => fr.url().includes('uxb_iframe=1')); }
  await p.waitForTimeout(8000);
  const pinPct = (slot) => f.evaluate((slot) => {
    const root = document.querySelector('.fbm-stl'); const m = root.querySelector('.fbm-stl__media').getBoundingClientRect();
    const b = root.querySelector('.fbm-stl__pin[data-slot="' + slot + '"]').getBoundingClientRect();
    return [Math.round(((b.left + b.width / 2) - m.left) / m.width * 100), Math.round(((b.top + b.height / 2) - m.top) / m.height * 100)];
  }, slot);
  const media = await f.locator('.fbm-stl').first().locator('.fbm-stl__media').boundingBox();
  const at = (x, y) => [media.x + media.width * x / 100, media.y + media.height * y / 100];
  const res = { before: { pin1: await pinPct(1), pin2: await pinPct(2) } };
  // 1. Kéo pin 2 tới (70, 30).
  const p2 = await f.locator('.fbm-stl').first().locator('.fbm-stl__pin[data-slot="2"]').boundingBox();
  await p.mouse.move(p2.x + p2.width / 2, p2.y + p2.height / 2);
  await p.mouse.down();
  const [tx, ty] = at(70, 30);
  await p.mouse.move(tx, ty, { steps: 12 });
  await p.mouse.up();
  await p.waitForTimeout(3000);
  res['1 drag pin2'] = await pinPct(2);
  // 2. Chọn Look 1 (bấm vào khối thẻ), chọn pin 1, rồi bấm chỗ trống (20, 80).
  const cards = await f.locator('.fbm-stl').first().locator('.fbm-stl__cards').boundingBox();
  await p.mouse.click(cards.x + 20, cards.y + 20);
  await p.waitForTimeout(1200);
  const p1 = await f.locator('.fbm-stl').first().locator('.fbm-stl__pin[data-slot="1"]').boundingBox();
  await p.mouse.click(p1.x + p1.width / 2, p1.y + p1.height / 2);
  await p.waitForTimeout(800);
  const [cx, cy] = at(20, 80);
  await p.mouse.click(cx, cy);
  await p.waitForTimeout(3000);
  res['2 click places pin1'] = await pinPct(1);
  res['2 pin2 unchanged'] = await pinPct(2);
  // 3. Đóng bảng option (Apply) nếu đang mở, rồi lưu nháp.
  try {
    const apply = p.locator('button', { hasText: /^\s*Apply\s*$/ }).first();
    if (await apply.isVisible().catch(() => false)) { await apply.click(); await p.waitForTimeout(1000); }
    await p.locator('button', { hasText: 'Save Draft' }).first().click({ timeout: 10000 });
    await p.waitForTimeout(4000);
    res.saved = true;
  } catch (e) { res.saved = 'save failed: ' + e.message.split('\n')[0]; }
  res.errors = errors.filter((e) => !/rdt|snaptr/.test(e)).slice(0, 5);
  await ctx.close();
  return res;
}
