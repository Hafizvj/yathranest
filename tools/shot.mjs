/**
 * Dev helper: screenshot pages with real device-metric emulation and report
 * horizontal overflow. Usage: node tools/shot.mjs <width> <label:path> [...]
 */
import { spawn } from "node:child_process";
import { mkdirSync, writeFileSync } from "node:fs";
import { setTimeout as sleep } from "node:timers/promises";

const CHROME = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const PORT = 9333 + Math.floor(Math.random() * 400);
const BASE = "http://localhost/yathranest/";
const OUT = "tools/shots";

const width = Number(process.argv[2] || 390);
const targets = process.argv.slice(3).map((arg) => {
  const idx = arg.indexOf(":");
  return { label: arg.slice(0, idx), path: arg.slice(idx + 1) };
});

mkdirSync(OUT, { recursive: true });

const chrome = spawn(CHROME, [
  "--headless=new",
  "--disable-gpu",
  "--no-sandbox",
  "--no-first-run",
  `--remote-debugging-port=${PORT}`,
  `--user-data-dir=${process.env.TEMP}\\yn-cdp-${PORT}`,
  "about:blank",
]);
chrome.stderr.on("data", () => {});

async function targetUrl() {
  for (let i = 0; i < 60; i++) {
    try {
      const res = await fetch(`http://127.0.0.1:${PORT}/json/list`);
      const list = await res.json();
      const page = list.find((t) => t.type === "page");
      if (page) return page.webSocketDebuggerUrl;
    } catch {}
    await sleep(250);
  }
  throw new Error("Chrome DevTools endpoint not reachable");
}

const ws = new WebSocket(await targetUrl());
await new Promise((resolve) => ws.addEventListener("open", resolve));

let msgId = 0;
const pending = new Map();
ws.addEventListener("message", (event) => {
  const msg = JSON.parse(event.data);
  if (msg.id && pending.has(msg.id)) {
    pending.get(msg.id)(msg.result);
    pending.delete(msg.id);
  }
});

const send = (method, params = {}) =>
  new Promise((resolve) => {
    const id = ++msgId;
    pending.set(id, resolve);
    ws.send(JSON.stringify({ id, method, params }));
  });

await send("Page.enable");
await send("Runtime.enable");
const viewportHeight = Number(process.env.SHOT_H || 900);
const fullPage = process.env.SHOT_FULL !== "0";

await send("Emulation.setDeviceMetricsOverride", {
  width,
  height: viewportHeight,
  deviceScaleFactor: 1,
  mobile: width < 700,
});

for (const { label, path } of targets) {
  await send("Page.navigate", { url: BASE + path });
  await sleep(2200);

  const probe = await send("Runtime.evaluate", {
    expression: `JSON.stringify({
      scroll: document.documentElement.scrollWidth,
      client: document.documentElement.clientWidth,
      wide: [...document.querySelectorAll('body *')]
        .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth + 1)
        .slice(0, 6)
        .map((el) => el.tagName.toLowerCase() + '.' + (el.className || '').toString().split(' ')[0] +
          ' @' + Math.round(el.getBoundingClientRect().right))
    })`,
    returnByValue: true,
  });
  const info = JSON.parse(probe.result.value);
  const overflow = info.scroll > info.client + 1;
  console.log(
    `${label.padEnd(12)} ${info.client}px viewport, scrollWidth ${info.scroll}` +
      (overflow ? `  OVERFLOW -> ${info.wide.join(" | ")}` : "  ok")
  );

  if (process.env.SHOT_CLICK) {
    await send("Runtime.evaluate", {
      expression: `document.querySelector(${JSON.stringify(process.env.SHOT_CLICK)})?.click()`,
    });
    await sleep(900);
  }

  if (process.env.SHOT_SCROLL === "bottom") {
    await send("Runtime.evaluate", { expression: "window.scrollTo(0, document.body.scrollHeight)" });
    await sleep(1200);
  }

  const shot = await send("Page.captureScreenshot", { format: "png", captureBeyondViewport: fullPage });
  writeFileSync(`${OUT}/${label}.png`, Buffer.from(shot.data, "base64"));
}

ws.close();
chrome.kill();
