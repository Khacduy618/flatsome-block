// Kịch bản kiểm view.js của Look (chạy bằng Playwright MCP: browser_run_code_unsafe với filename).
// Trang /fbm-stl-front/ trên DDEV: [fbm_shop_look] chứa 2 Look (stl-a, stl-b), mỗi Look 3 pin.
async (page) => {
  const URL = 'https://handdn.ddev.site/fbm-stl-front/';
  const res = {};
  const state = (p, id) => p.evaluate((id) => {
    const root = document.getElementById(id);
    return {
      pressed: [...root.querySelectorAll('.fbm-stl__pin')].map(b => b.getAttribute('aria-pressed')),
      active: [...root.querySelectorAll('.fbm-stl__pin')].map(b => b.classList.contains('is-active')),
      visible: [...root.querySelectorAll('.fbm-stl__card')].map(c => !c.hidden),
      highlight: [...root.querySelectorAll('.fbm-stl__pin')].map(b => b.classList.contains('is-highlight')),
    };
  }, id);
  const browser = page.context().browser();
  const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  await p.goto(URL, { waitUntil: 'networkidle' });
  await p.waitForTimeout(800);
  const s1 = await state(p, 'stl-a');
  res['1 init'] = s1.pressed.join() === 'true,false,false' && s1.active[0] && s1.visible.join() === 'true,false,false';
  await p.locator('#stl-a .fbm-stl__pin').nth(2).click();
  await p.waitForTimeout(300);
  const s2 = await state(p, 'stl-a');
  res['2 click pin 3'] = s2.visible.join() === 'false,false,true' && s2.pressed.join() === 'false,false,true' && s2.active[2] && !s2.active[0];
  const b = await state(p, 'stl-b');
  res['3 other look unchanged'] = b.visible.join() === 'true,false,false' && b.pressed[0] === 'true';
  await p.locator('#stl-a .fbm-stl__pin').nth(1).focus();
  await p.keyboard.press('Enter');
  await p.waitForTimeout(300);
  const s4 = await state(p, 'stl-a');
  res['4 keyboard Enter pin 2'] = s4.visible.join() === 'false,true,false';
  await p.locator('#stl-a .fbm-stl__card:not([hidden])').hover();
  await p.waitForTimeout(200);
  const s5 = await state(p, 'stl-a');
  await p.mouse.move(5, 5);
  await p.waitForTimeout(200);
  const s5b = await state(p, 'stl-a');
  res['5 hover card highlights pin'] = s5.highlight.join() === 'false,true,false' && s5b.highlight.join() === 'false,false,false';
  await ctx.close();
  const m = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 375, height: 700 } });
  const mp = await m.newPage();
  await mp.goto(URL, { waitUntil: 'networkidle' });
  await mp.waitForTimeout(800);
  await mp.locator('#stl-a .fbm-stl__media').scrollIntoViewIfNeeded();
  await mp.evaluate(() => { const r = document.querySelector('#stl-a .fbm-stl__media').getBoundingClientRect(); window.scrollBy(0, r.top - 10); });
  await mp.waitForTimeout(300);
  await mp.locator('#stl-a .fbm-stl__pin').nth(1).click();
  await mp.waitForTimeout(900);
  res['6 mobile scrolls card into view'] = await mp.evaluate(() => { const c = document.querySelector('#stl-a .fbm-stl__card:not([hidden])'); const r = c.getBoundingClientRect(); return r.top < innerHeight && r.bottom > 0 && c.id.endsWith('card-1'); });
  await m.close();
  return res;
}
