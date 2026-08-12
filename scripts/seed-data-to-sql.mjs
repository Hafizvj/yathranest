/**
 * Build sql/seed-import.sql from seed-data.json for phpMyAdmin import.
 * No PHP or remote MySQL needed — run on HostMaria after schema.sql.
 *
 * Usage: node scripts/seed-data-to-sql.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const jsonPath = path.join(root, 'sql', 'seed-data.json');
const outPath = path.join(root, 'sql', 'seed-import.sql');

if (!fs.existsSync(jsonPath)) {
  console.error('Missing sql/seed-data.json — run: node scripts/export-packages-json.mjs');
  process.exit(1);
}

const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));

function esc(v) {
  if (v === null || v === undefined) return 'NULL';
  if (typeof v === 'number') return String(v);
  if (typeof v === 'boolean') return v ? '1' : '0';
  return "'" + String(v).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'";
}

function j(obj) {
  return esc(JSON.stringify(obj ?? []));
}

// Generated with: php -r "echo password_hash('ChangeMe123!', PASSWORD_DEFAULT);"
const ADMIN_HASH = '$2y$10$h6wujRTo4T3k5b/9ZyDesu15PDGhAHEhmEki6iSZUcthpGtEnBPeC';

const lines = [
  '-- YathraNest seed data — import AFTER sql/schema.sql',
  'SET NAMES utf8mb4;',
  'SET FOREIGN_KEY_CHECKS = 0;',
  '',
  'DELETE FROM admins;',
  'DELETE FROM places;',
  'DELETE FROM packages;',
  'DELETE FROM resorts;',
  'DELETE FROM getaways;',
  'DELETE FROM gift_cards;',
  'DELETE FROM investment_plans;',
  'DELETE FROM settings;',
  'DELETE FROM page_content;',
  '',
];

const admin = data.admin || {};
const email = admin.email || 'admin@yathranest.com';
const name = admin.name || 'Admin';
const pass = admin.password || 'ChangeMe123!';

lines.push(
  `INSERT INTO admins (email, password_hash, name) VALUES (${esc(email)}, ${esc(ADMIN_HASH)}, ${esc(name)});`,
  `-- Default login: ${email} / ${pass}`,
  ''
);

let i = 0;
for (const [slug, p] of Object.entries(data.places || {})) {
  lines.push(
    `INSERT INTO places (slug, label, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (${esc(p.slug || slug)}, ${esc(p.label)}, ${j(p.tags)}, ${esc(p.arrive || '')}, ${esc(p.sightseeing || '')}, ${j(p.images)}, ${i++});`
  );
}
lines.push('');

for (const p of data.packages || []) {
  lines.push(
    `INSERT INTO packages (slug, sheet, group_name, pickup, drop_point, pickup_slug, days, nights, stay_split, stay_summary, destinations_json, dest_line, pages_json, type, state, duration_bucket, title, overview, card_text, highlights_json, itinerary_json, image, gallery_json, has_houseboat, accommodation, is_published, sort_order) VALUES (${esc(p.slug)}, ${esc(p.sheet)}, ${esc(p.group_name)}, ${esc(p.pickup)}, ${esc(p.drop_point)}, ${esc(p.pickup_slug)}, ${p.days}, ${p.nights}, ${esc(p.stay_split)}, ${esc(p.stay_summary)}, ${j(p.destinations)}, ${esc(p.dest_line)}, ${j(p.pages)}, ${esc(p.type)}, ${esc(p.state)}, ${esc(p.duration_bucket)}, ${esc(p.title)}, ${esc(p.overview)}, ${esc(p.card_text)}, ${j(p.highlights)}, ${j(p.itinerary)}, ${esc(p.image)}, ${j(p.gallery)}, ${p.has_houseboat ? 1 : 0}, ${esc(p.accommodation)}, ${p.is_published ?? 1}, ${p.sort_order ?? 0});`
  );
}
lines.push('');

for (const r of data.resorts || []) {
  lines.push(
    `INSERT INTO resorts (slug, title, location, category, summary, body, image, gallery_json, amenities_json, is_published, sort_order) VALUES (${esc(r.slug)}, ${esc(r.title)}, ${esc(r.location || '')}, ${esc(r.category || '')}, ${esc(r.summary || '')}, ${esc(r.body || '')}, ${esc(r.image || '')}, ${j(r.gallery)}, ${j(r.amenities)}, 1, 0);`
  );
}
lines.push('');

for (const g of data.getaways || []) {
  lines.push(
    `INSERT INTO getaways (slug, title, location, duration, summary, body, image, is_published, sort_order) VALUES (${esc(g.slug)}, ${esc(g.title)}, ${esc(g.location || '')}, ${esc(g.duration || '')}, ${esc(g.summary || '')}, ${esc(g.body || '')}, ${esc(g.image || '')}, 1, 0);`
  );
}
lines.push('');

for (const c of data.gift_cards || []) {
  lines.push(
    `INSERT INTO gift_cards (slug, title, blurb, features_json, image, is_published, sort_order) VALUES (${esc(c.slug)}, ${esc(c.title)}, ${esc(c.blurb || '')}, ${j(c.features)}, ${esc(c.image || '')}, 1, 0);`
  );
}
lines.push('');

for (const p of data.investment_plans || []) {
  lines.push(
    `INSERT INTO investment_plans (slug, title, blurb, features_json, image, is_published, sort_order) VALUES (${esc(p.slug)}, ${esc(p.title)}, ${esc(p.blurb || '')}, ${j(p.features)}, ${esc(p.image || '')}, 1, 0);`
  );
}
lines.push('');

for (const [k, v] of Object.entries(data.settings || {})) {
  lines.push(`INSERT INTO settings (setting_key, setting_value) VALUES (${esc(k)}, ${esc(v)});`);
}
lines.push('');

for (const [key, page] of Object.entries(data.page_content || {})) {
  lines.push(
    `INSERT INTO page_content (page_key, title, sections_json) VALUES (${esc(key)}, ${esc(page.title || key)}, ${j(page.sections)});`
  );
}

lines.push('', 'SET FOREIGN_KEY_CHECKS = 1;', '');

fs.writeFileSync(outPath, lines.join('\n'), 'utf8');
console.log('Wrote', outPath);
console.log('Import in phpMyAdmin: 1) schema.sql  2) seed-import.sql');
console.log('Admin:', email, '/', pass);
