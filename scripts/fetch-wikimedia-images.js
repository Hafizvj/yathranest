/**
 * Download place-specific package hero images from Wikimedia Commons.
 *
 * Uses the Commons API for search, caches URLs per destination, then downloads
 * one unique landscape photo per package (90 total).
 *
 * Run:
 *   node scripts/fetch-wikimedia-images.js
 *   node scripts/fetch-wikimedia-images.js --search-only
 *   node scripts/fetch-wikimedia-images.js --download-only
 */
const fs = require("fs");
const path = require("path");
const https = require("https");

const ROOT = path.join(__dirname, "..");
const OUT_DIR = path.join(ROOT, "assets", "images", "packages");
const CACHE_FILE = path.join(__dirname, ".place-image-cache.json");
const ATTR_FILE = path.join(OUT_DIR, "attribution.json");
const API = "https://commons.wikimedia.org/w/api.php";
const USER_AGENT = "YathraNest/1.0 (https://yathranest.com; hello@yathranest.com)";

const PLACE_SEARCHES = {
  wayanad: ["Wayanad Kerala", "Chembra Peak Wayanad", "Edakkal Caves"],
  kozhikode: ["Kozhikode beach", "Kappad beach Kerala", "Calicut"],
  munnar: ["Munnar tea", "Munnar Kerala hills", "Eravikulam"],
  thekkady: ["Thekkady Periyar", "Periyar lake Kerala", "Periyar wildlife"],
  gavi: ["Gavi Kerala", "Periyar forest Kerala", "Gavi ecotourism"],
  alleppey: ["Alleppey backwaters", "Alappuzha houseboat", "Kerala backwaters"],
  athirappilly: ["Athirappilly falls", "Athirappilly waterfall Kerala"],
  kochi: ["Fort Kochi", "Chinese fishing nets Kochi", "Kochi Kerala"],
  vagamon: ["Vagamon Kerala", "Vagamon meadows"],
  varkala: ["Varkala cliff", "Varkala beach Kerala"],
  kovalam: ["Kovalam beach", "Kovalam lighthouse Kerala"],
  trivandrum: ["Thiruvananthapuram", "Padmanabhaswamy temple", "Kerala capital"],
  ooty: ["Ooty lake", "Ooty Nilgiri", "Botanical Garden Ooty"],
  coorg: ["Coorg Karnataka", "Madikeri", "Abbey Falls Coorg"],
  chikmagalur: ["Chikmagalur", "Mullayanagiri", "Karnataka coffee"],
  mysore: ["Mysore palace", "Mysore Karnataka"],
  valparai: ["Valparai", "Anamalai hills"],
  kodaikanal: ["Kodaikanal lake", "Kodaikanal Tamil Nadu"],
  lakshadweep: ["Lakshadweep", "Agatti island", "Lakshadweep lagoon"],
};

const PLACE_BORROW = {
  thekkady: ["gavi", "munnar", "wayanad"],
  gavi: ["thekkady", "munnar", "wayanad"],
  vagamon: ["munnar", "wayanad", "ooty"],
  chikmagalur: ["coorg", "wayanad", "ooty"],
  valparai: ["munnar", "kodaikanal", "ooty"],
  kodaikanal: ["ooty", "munnar", "valparai"],
  wayanad: ["ooty", "coorg", "munnar"],
  coorg: ["chikmagalur", "wayanad", "ooty"],
  varkala: ["kovalam", "kozhikode", "kochi"],
  kozhikode: ["varkala", "kovalam", "kochi"],
  lakshadweep: ["kovalam", "varkala", "alleppey"],
};

const SKIP_TITLE = /\b(map|logo|icon|diagram|chart|seal|coat of arms|location|svg|stamp|emblem|route|border|flag|coat_of_arms)\b/i;
const MIN_POOL = 12;
const SEARCH_DELAY_MS = 800;
const DOWNLOAD_DELAY_MS = 2000;

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

function hashId(id) {
  let h = 0;
  for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) >>> 0;
  return h;
}

function httpsGet(url, headers = {}) {
  return new Promise((resolve, reject) => {
    const req = https.get(
      url,
      { headers: { "User-Agent": USER_AGENT, ...headers } },
      (res) => {
        if ([301, 302, 307, 308].includes(res.statusCode) && res.headers.location) {
          res.resume();
          const next = res.headers.location.startsWith("http")
            ? res.headers.location
            : new URL(res.headers.location, url).href;
          return resolve(httpsGet(next, headers));
        }
        const chunks = [];
        res.on("data", (c) => chunks.push(c));
        res.on("end", () =>
          resolve({
            status: res.statusCode,
            data: Buffer.concat(chunks),
            headers: res.headers,
          })
        );
      }
    );
    req.on("error", reject);
    req.setTimeout(90000, () => req.destroy(new Error("timeout")));
  });
}

async function commonsApi(params) {
  const qs = new URLSearchParams({
    format: "json",
    origin: "*",
    ...params,
  });
  const url = `${API}?${qs}`;
  for (let attempt = 1; attempt <= 4; attempt++) {
    const res = await httpsGet(url);
    if (res.status === 429) {
      const wait = attempt * 15000;
      console.log(`\nCommons API rate limited. Waiting ${wait / 1000}s...`);
      await sleep(wait);
      continue;
    }
    if (res.status !== 200) {
      throw new Error(`Commons API HTTP ${res.status}`);
    }
    return JSON.parse(res.data.toString("utf8"));
  }
  throw new Error("Commons API rate limit exhausted");
}

function cleanThumbUrl(url) {
  return url.split("?")[0];
}

function isGoodImage(title, info) {
  if (!info || !info.thumburl) return false;
  if (SKIP_TITLE.test(title)) return false;
  const mime = (info.mime || "").toLowerCase();
  if (mime && !mime.startsWith("image/")) return false;
  if (mime === "image/svg+xml") return false;
  const w = info.thumbwidth || info.width || 0;
  const h = info.thumbheight || info.height || 0;
  if (w < 640 || h < 400) return false;
  if (h > w * 1.4) return false; // skip very tall portraits
  return true;
}

async function searchPlaceImages(place) {
  const queries = PLACE_SEARCHES[place] || [`${place} India`];
  const pool = [];
  const seen = new Set();

  for (const query of queries) {
    if (pool.length >= 50) break;
    const json = await commonsApi({
      action: "query",
      generator: "search",
      gsrsearch: query,
      gsrnamespace: "6",
      gsrlimit: "40",
      prop: "imageinfo",
      iiprop: "url|thumburl|size|mime|extmetadata",
      iiurlwidth: "960",
    });

    const pages = json.query && json.query.pages;
    if (!pages) continue;

    Object.values(pages).forEach((page) => {
      const info = page.imageinfo && page.imageinfo[0];
      if (!info || !isGoodImage(page.title, info)) return;
      const url = cleanThumbUrl(info.thumburl || info.url);
      if (seen.has(url)) return;
      seen.add(url);
      const meta = (info.extmetadata || {});
      pool.push({
        url,
        title: page.title.replace(/^File:/, ""),
        artist: meta.Artist && meta.Artist.value ? meta.Artist.value.replace(/<[^>]+>/g, "").trim() : "",
        license: meta.LicenseShortName && meta.LicenseShortName.value,
        pageUrl: `https://commons.wikimedia.org/wiki/${encodeURIComponent(page.title)}`,
      });
    });
    await sleep(SEARCH_DELAY_MS);
  }

  return pool;
}

function loadCache() {
  if (!fs.existsSync(CACHE_FILE)) return {};
  try {
    const raw = JSON.parse(fs.readFileSync(CACHE_FILE, "utf8"));
    const cache = {};
    for (const [place, entries] of Object.entries(raw)) {
      cache[place] = entries.map((e) =>
        typeof e === "string"
          ? { url: cleanThumbUrl(e), title: "", artist: "", license: "", pageUrl: "" }
          : { ...e, url: cleanThumbUrl(e.url) }
      );
    }
    return cache;
  } catch {
    return {};
  }
}

function saveCache(cache) {
  fs.writeFileSync(CACHE_FILE, JSON.stringify(cache, null, 2));
}

async function buildPlaceCache() {
  const cache = loadCache();
  const places = Object.keys(PLACE_SEARCHES);
  const pending = places.filter((p) => !(cache[p] && cache[p].length >= MIN_POOL));

  if (!pending.length) {
    console.log("Using cached Wikimedia results for all places");
    return cache;
  }

  console.log(`Searching Wikimedia Commons (${pending.length} places)...`);
  for (const place of pending) {
    try {
      cache[place] = await searchPlaceImages(place);
      console.log(`  ${place}: ${cache[place].length} images`);
      saveCache(cache);
      await sleep(SEARCH_DELAY_MS);
    } catch (e) {
      saveCache(cache);
      throw new Error(`Stopped at ${place}: ${e.message}`);
    }
  }

  for (const place of places) {
    if ((cache[place] || []).length >= MIN_POOL) continue;
    const seen = new Set((cache[place] || []).map((i) => i.url));
    const merged = [...(cache[place] || [])];
    for (const other of PLACE_BORROW[place] || []) {
      for (const img of cache[other] || []) {
        if (!seen.has(img.url)) {
          seen.add(img.url);
          merged.push(img);
        }
      }
    }
    if (merged.length > (cache[place] || []).length) {
      cache[place] = merged;
      console.log(`  ${place}: padded to ${merged.length} via related places`);
    }
  }
  saveCache(cache);
  return cache;
}

function pickPlace(stays, dests) {
  const overnight = stays.filter((s) => s.nights > 0);
  if (overnight.length) return overnight[0].place;
  if (dests.length) return dests[0];
  return "kochi";
}

function resolvePool(cache, place) {
  if (cache[place] && cache[place].length) return cache[place];
  for (const other of PLACE_BORROW[place] || []) {
    if (cache[other] && cache[other].length) return cache[other];
  }
  return cache.munnar || cache.kochi || [];
}

function pickImage(pool, id, usedUrls) {
  if (!pool.length) return null;
  let idx = hashId(id) % pool.length;
  let tries = 0;
  while (usedUrls.has(pool[idx].url) && tries < pool.length) {
    idx = (idx + 1) % pool.length;
    tries++;
  }
  if (usedUrls.has(pool[idx].url)) {
    return pool[hashId(id + ":reuse") % pool.length];
  }
  usedUrls.add(pool[idx].url);
  return pool[idx];
}

async function downloadFile(url, dest) {
  for (let attempt = 1; attempt <= 5; attempt++) {
    const res = await httpsGet(url);
    if (res.status === 429) {
      const retryAfter = Number(res.headers["retry-after"]) || attempt * 30;
      console.log(`\n  429 — waiting ${retryAfter}s before retry...`);
      await sleep(retryAfter * 1000);
      continue;
    }
    if (res.status !== 200) throw new Error(`HTTP ${res.status}`);
    if (res.data.length < 4000) throw new Error("file too small");
    fs.writeFileSync(dest, res.data);
    if (!fs.existsSync(dest) || fs.statSync(dest).size < 4000) {
      throw new Error("file not saved");
    }
    return;
  }
  throw new Error("download rate limit exhausted");
}

async function downloadPackages(cache) {
  const dataJs = fs.readFileSync(path.join(ROOT, "js", "packages-data.js"), "utf8");
  const window = {};
  eval(dataJs);

  if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

  let attribution = [];
  if (fs.existsSync(ATTR_FILE)) {
    try {
      attribution = JSON.parse(fs.readFileSync(ATTR_FILE, "utf8"));
    } catch {
      attribution = [];
    }
  }

  const doneIds = new Set(
    attribution
      .filter((a) => {
        const p = path.join(OUT_DIR, `${a.package}.jpg`);
        return fs.existsSync(p) && fs.statSync(p).size > 4000;
      })
      .map((a) => a.package)
  );
  const usedUrls = new Set(attribution.map((a) => a.url).filter(Boolean));

  let ok = 0;
  let skip = 0;
  let fail = 0;

  for (const pkg of window.YNPackages.all) {
    const dest = path.join(OUT_DIR, `${pkg.id}.jpg`);
    if (doneIds.has(pkg.id)) {
      skip++;
      continue;
    }

    const place = pickPlace(pkg.stays, pkg.destinations);
    const pool = resolvePool(cache, place);
    const image = pickImage(pool, pkg.id, usedUrls);

    if (!image) {
      fail++;
      console.error(`\nNo image for ${pkg.id} (${place})`);
      continue;
    }

    try {
      await downloadFile(image.url, dest);
      attribution = attribution.filter((a) => a.package !== pkg.id);
      attribution.push({
        package: pkg.id,
        place,
        url: image.url,
        title: image.title,
        artist: image.artist,
        license: image.license,
        pageUrl: image.pageUrl,
        source: "Wikimedia Commons",
      });
      fs.writeFileSync(ATTR_FILE, JSON.stringify(attribution, null, 2));
      ok++;
      process.stdout.write(".");
      await sleep(DOWNLOAD_DELAY_MS);
    } catch (e) {
      fail++;
      console.error(`\nFAIL ${pkg.id}: ${e.message}`);
    }
  }

  console.log(`\nDownloads: ${ok} new, ${skip} skipped, ${fail} failed`);
  console.log("Attribution:", ATTR_FILE);
}

async function main() {
  const searchOnly = process.argv.includes("--search-only");
  const downloadOnly = process.argv.includes("--download-only");

  let cache;
  if (downloadOnly) {
    cache = loadCache();
    if (!Object.keys(cache).length) {
      console.error("No cache found. Run without --download-only first.");
      process.exit(1);
    }
    console.log("Using existing Wikimedia cache");
  } else {
    cache = await buildPlaceCache();
  }

  if (searchOnly) {
    console.log("Search complete. Run again (or --download-only) to download images.");
    return;
  }

  await downloadPackages(cache);
}

main().catch((e) => {
  console.error(e.message || e);
  process.exit(1);
});
