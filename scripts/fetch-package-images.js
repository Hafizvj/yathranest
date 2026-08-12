/**
 * Download place-specific package hero images via the Unsplash API.
 *
 * Demo apps: ~50 requests/hour. This script uses 1 search per place (~19),
 * then 1 download trigger per package (~90). Prefer running search first,
 * waiting an hour if needed, then downloads resume from cache.
 *
 * Setup:
 *   1. Create an app at https://unsplash.com/developers
 *   2. Put key in `.env`: UNSPLASH_ACCESS_KEY=your_key
 *
 * Run:
 *   node scripts/fetch-package-images.js
 *   node scripts/fetch-package-images.js --search-only
 *   node scripts/fetch-package-images.js --download-only
 */
const fs = require("fs");
const path = require("path");
const https = require("https");

const ROOT = path.join(__dirname, "..");
const OUT_DIR = path.join(ROOT, "assets", "images", "packages");
const CACHE_FILE = path.join(__dirname, ".unsplash-place-cache.json");
const ATTR_FILE = path.join(OUT_DIR, "attribution.json");

/** Primary + fallback queries (tried in order until enough results). */
const PLACE_QUERIES = {
  wayanad: ["Wayanad", "Kerala hills forest", "Western Ghats Kerala"],
  kozhikode: ["Kozhikode beach", "Calicut Kerala", "Kerala beach"],
  munnar: ["Munnar", "Munnar tea plantation", "Kerala tea hills"],
  thekkady: ["Thekkady", "Periyar lake", "Kerala wildlife forest"],
  gavi: ["Periyar", "Kerala forest lake", "Western Ghats forest"],
  alleppey: ["Alleppey", "Kerala backwaters", "houseboat Kerala"],
  athirappilly: ["Athirappilly", "Kerala waterfall", "waterfall India Kerala"],
  kochi: ["Fort Kochi", "Kochi Kerala", "Chinese fishing nets"],
  vagamon: ["Vagamon", "Kerala meadows", "Idukki hills"],
  varkala: ["Varkala", "Varkala cliff", "Kerala cliff beach"],
  kovalam: ["Kovalam", "Kovalam beach", "Kerala beach lighthouse"],
  trivandrum: ["Trivandrum", "Kovalam", "Kerala temple"],
  ooty: ["Ooty", "Nilgiri hills", "Ooty lake"],
  coorg: ["Coorg", "Kodagu coffee", "Karnataka coffee hills"],
  chikmagalur: ["Chikmagalur", "Karnataka coffee", "Mullayanagiri"],
  mysore: ["Mysore palace", "Mysore", "Karnataka palace"],
  valparai: ["Valparai", "Anamalai", "Tamil Nadu tea estate"],
  kodaikanal: ["Kodaikanal", "Kodaikanal lake", "Tamil Nadu hills lake"],
  lakshadweep: ["Lakshadweep", "Maldives lagoon", "Indian ocean island"],
};

/** If a place still has too few photos, borrow from related pools. */
const PLACE_BORROW = {
  thekkady: ["gavi", "munnar", "vagamon"],
  gavi: ["thekkady", "munnar", "wayanad"],
  vagamon: ["munnar", "wayanad", "ooty"],
  chikmagalur: ["coorg", "wayanad", "ooty"],
  valparai: ["munnar", "kodaikanal", "ooty"],
  kodaikanal: ["ooty", "munnar", "valparai"],
  wayanad: ["ooty", "coorg", "munnar"],
  coorg: ["chikmagalur", "wayanad", "ooty"],
  varkala: ["kovalam", "kozhikode", "kochi"],
  kozhikode: ["varkala", "kovalam", "kochi"],
};

const MIN_POOL = 8;
const SEARCH_DELAY_MS = 1200;
const DOWNLOAD_DELAY_MS = 800;

function loadEnv() {
  const envPath = path.join(ROOT, ".env");
  if (!fs.existsSync(envPath)) return;
  fs.readFileSync(envPath, "utf8").split(/\r?\n/).forEach((line) => {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) return;
    const eq = trimmed.indexOf("=");
    if (eq === -1) return;
    const key = trimmed.slice(0, eq).trim();
    let val = trimmed.slice(eq + 1).trim();
    val = val.replace(/^['"]|['"]$/g, "");
    if (!process.env[key]) process.env[key] = val;
  });
}

function getAccessKey() {
  loadEnv();
  const arg = process.argv.find((a) => a.startsWith("--key="));
  return process.env.UNSPLASH_ACCESS_KEY || process.env.UNSPLASH_CLIENT_ID || (arg && arg.split("=")[1]);
}

function hashId(id) {
  let h = 0;
  for (let i = 0; i < id.length; i++) h = (h * 31 + id.charCodeAt(i)) >>> 0;
  return h;
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

function httpsRequest(url, options = {}) {
  return new Promise((resolve, reject) => {
    const req = https.request(
      url,
      {
        headers: {
          "User-Agent": "YathraNest/1.0 (package images)",
          Accept: "application/json",
          ...options.headers,
        },
        method: options.method || "GET",
      },
      (res) => {
        if ([301, 302, 307, 308].includes(res.statusCode) && res.headers.location) {
          res.resume();
          const next = res.headers.location.startsWith("http")
            ? res.headers.location
            : new URL(res.headers.location, url).href;
          return resolve(httpsRequest(next, options));
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
    req.setTimeout(60000, () => req.destroy(new Error("timeout")));
    req.end();
  });
}

function rateLimitInfo(headers) {
  const remaining = Number(headers["x-ratelimit-remaining"]);
  const limit = Number(headers["x-ratelimit-limit"]);
  return {
    remaining: Number.isFinite(remaining) ? remaining : null,
    limit: Number.isFinite(limit) ? limit : null,
  };
}

async function waitForRateLimit(headers, label) {
  const { remaining, limit } = rateLimitInfo(headers);
  if (remaining != null) {
    process.stdout.write(` [rate ${remaining}/${limit ?? "?"}]`);
  }
  if (remaining != null && remaining <= 1) {
    console.log(`\nNear rate limit after ${label}. Waiting 65s...`);
    await sleep(65000);
  }
}

async function unsplashSearch(query, accessKey) {
  const url =
    "https://api.unsplash.com/search/photos?" +
    `query=${encodeURIComponent(query)}&page=1&per_page=30&orientation=landscape`;

  for (let attempt = 1; attempt <= 4; attempt++) {
    const res = await httpsRequest(url, {
      headers: { Authorization: `Client-ID ${accessKey}` },
    });

    if (res.status === 401) throw new Error("Invalid Unsplash Access Key (401)");

    if (res.status === 403 || res.status === 429) {
      const wait = attempt * 65000;
      console.log(`\nRate limited (${res.status}) on "${query}". Waiting ${Math.round(wait / 1000)}s (attempt ${attempt}/4)...`);
      await sleep(wait);
      continue;
    }

    if (res.status !== 200) {
      throw new Error(`Unsplash search HTTP ${res.status}: ${res.data.toString("utf8").slice(0, 160)}`);
    }

    await waitForRateLimit(res.headers, query);
    const json = JSON.parse(res.data.toString("utf8"));
    return (json.results || []).map((photo) => ({
      id: photo.id,
      url: `${photo.urls.raw}&w=800&h=500&fit=crop&q=80&auto=format`,
      downloadLocation: photo.links.download_location,
      photographer: photo.user && photo.user.name,
    }));
  }

  throw new Error(`Unsplash rate limit exhausted for query: ${query}`);
}

async function triggerDownload(downloadLocation, accessKey) {
  const sep = downloadLocation.includes("?") ? "&" : "?";
  const url = `${downloadLocation}${sep}client_id=${accessKey}`;

  for (let attempt = 1; attempt <= 4; attempt++) {
    const res = await httpsRequest(url, {
      headers: { Authorization: `Client-ID ${accessKey}` },
    });

    if (res.status === 403 || res.status === 429) {
      const wait = attempt * 65000;
      console.log(`\nRate limited on download trigger. Waiting ${Math.round(wait / 1000)}s...`);
      await sleep(wait);
      continue;
    }

    if (res.status !== 200) throw new Error(`Download trigger HTTP ${res.status}`);
    await waitForRateLimit(res.headers, "download");
    const json = JSON.parse(res.data.toString("utf8"));
    return json.url;
  }

  throw new Error("Download trigger rate limit exhausted");
}

async function downloadFile(url, dest) {
  const res = await httpsRequest(url);
  if (res.status !== 200) throw new Error(`File download HTTP ${res.status}`);
  fs.writeFileSync(dest, res.data);
  if (fs.statSync(dest).size < 3000) throw new Error("file too small");
}

function loadCache() {
  if (!fs.existsSync(CACHE_FILE)) return {};
  try {
    return JSON.parse(fs.readFileSync(CACHE_FILE, "utf8"));
  } catch {
    return {};
  }
}

function saveCache(cache) {
  fs.writeFileSync(CACHE_FILE, JSON.stringify(cache, null, 2));
}

async function fetchPlacePool(place, accessKey) {
  const queries = PLACE_QUERIES[place] || [`${place} India`];
  const pool = [];
  const seen = new Set();

  for (const query of queries) {
    if (pool.length >= MIN_POOL) break;
    const batch = await unsplashSearch(query, accessKey);
    batch.forEach((photo) => {
      if (!seen.has(photo.id)) {
        seen.add(photo.id);
        pool.push(photo);
      }
    });
    await sleep(SEARCH_DELAY_MS);
  }

  return pool;
}

async function buildPlaceCache(accessKey) {
  const cache = loadCache();
  const places = Object.keys(PLACE_QUERIES);
  const pending = places.filter((p) => !(cache[p] && cache[p].length >= MIN_POOL));

  if (!pending.length) {
    console.log("Using cached Unsplash results for all places");
    return cache;
  }

  console.log(`Fetching Unsplash pools (${pending.length} places need work)...`);
  for (const place of pending) {
    try {
      cache[place] = await fetchPlacePool(place, accessKey);
      console.log(`\n  ${place}: ${cache[place].length} photos`);
      saveCache(cache);
      await sleep(SEARCH_DELAY_MS);
    } catch (e) {
      saveCache(cache);
      console.error(`\nStopped while fetching ${place}: ${e.message}`);
      console.error("Partial cache saved. Re-run later to continue.");
      throw e;
    }
  }

  // Borrow related photos into thin pools (no extra API calls).
  for (const place of places) {
    if ((cache[place] || []).length >= MIN_POOL) continue;
    const seen = new Set((cache[place] || []).map((p) => p.id));
    const merged = [...(cache[place] || [])];
    for (const other of PLACE_BORROW[place] || []) {
      for (const photo of cache[other] || []) {
        if (!seen.has(photo.id)) {
          seen.add(photo.id);
          merged.push(photo);
        }
      }
    }
    cache[place] = merged;
    console.log(`  ${place}: padded to ${merged.length} via related places`);
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

function pickPhoto(pool, id, usedIds) {
  if (!pool.length) return null;
  let idx = hashId(id) % pool.length;
  let tries = 0;
  while (usedIds.has(pool[idx].id) && tries < pool.length) {
    idx = (idx + 1) % pool.length;
    tries++;
  }
  // Prefer unique photos; if exhausted, allow reuse rather than fail.
  if (usedIds.has(pool[idx].id)) {
    return pool[hashId(id + ":reuse") % pool.length];
  }
  usedIds.add(pool[idx].id);
  return pool[idx];
}

async function downloadPackages(accessKey, cache) {
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
  const doneIds = new Set(attribution.map((a) => a.package));
  const usedIds = new Set(attribution.map((a) => a.photoId).filter(Boolean));

  let ok = 0;
  let skip = 0;
  let fail = 0;

  for (const pkg of window.YNPackages.all) {
    const dest = path.join(OUT_DIR, `${pkg.id}.jpg`);
    if (doneIds.has(pkg.id) && fs.existsSync(dest) && fs.statSync(dest).size > 3000) {
      skip++;
      continue;
    }

    const place = pickPlace(pkg.stays, pkg.destinations);
    const pool = resolvePool(cache, place);
    const photo = pickPhoto(pool, pkg.id, usedIds);

    if (!photo) {
      fail++;
      console.error("\nNo photo for", pkg.id, place);
      continue;
    }

    try {
      const fileUrl = await triggerDownload(photo.downloadLocation, accessKey);
      await downloadFile(fileUrl, dest);
      attribution = attribution.filter((a) => a.package !== pkg.id);
      attribution.push({
        package: pkg.id,
        place,
        photoId: photo.id,
        photographer: photo.photographer,
        unsplash: `https://unsplash.com/photos/${photo.id}`,
      });
      fs.writeFileSync(ATTR_FILE, JSON.stringify(attribution, null, 2));
      ok++;
      process.stdout.write(".");
      await sleep(DOWNLOAD_DELAY_MS);
    } catch (e) {
      fail++;
      console.error("\nFAIL", pkg.id, e.message);
      if (/rate limit/i.test(e.message)) {
        console.error("Stopping downloads. Re-run with --download-only after the hourly reset.");
        break;
      }
    }
  }

  console.log(`\nDownloads: ${ok} new, ${skip} skipped, ${fail} failed`);
  console.log("Attribution:", ATTR_FILE);
}

async function main() {
  const accessKey = getAccessKey();
  if (!accessKey) {
    console.error(
      "Missing Unsplash Access Key.\n" +
        "Get one at https://unsplash.com/developers then set UNSPLASH_ACCESS_KEY in .env"
    );
    process.exit(1);
  }

  const searchOnly = process.argv.includes("--search-only");
  const downloadOnly = process.argv.includes("--download-only");

  let cache;
  if (downloadOnly) {
    cache = loadCache();
    if (!Object.keys(cache).length) {
      console.error("No search cache found. Run without --download-only first.");
      process.exit(1);
    }
    console.log("Using existing Unsplash cache");
  } else {
    cache = await buildPlaceCache(accessKey);
  }

  if (searchOnly) {
    console.log("Search-only done. Run again (or --download-only) to save images.");
    return;
  }

  await downloadPackages(accessKey, cache);
}

main().catch((e) => {
  console.error(e.message || e);
  process.exit(1);
});
