/**
 * Convert taxi-booking.html → taxi-booking.php
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const src = path.join(root, 'pages', 'taxi-booking.html');
let html = fs.readFileSync(src, 'utf8');
html = html.replace(/\.html/g, '.php');
html = html.replace('<!DOCTYPE html>', `<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$csrf = csrf_token();
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = preg_replace('/\\D/', '', setting('whatsapp', '919876543210'));
?>
<!DOCTYPE html>`);
html = html.replace(
  /<form data-enquiry-form([^>]*)>/g,
  `<form data-enquiry-form$1 action="../handlers/enquiry.php" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="type" value="taxi" />
        <input type="hidden" name="source_page" value="pages/taxi-booking.php" />
        <input type="hidden" name="interest" value="Taxi booking" />`
);
html = html.replace(/\+91 98765 43210/g, '<?= e($phone) ?>');
html = html.replace(/hello@yathranest\.com/g, '<?= e($email) ?>');
html = html.replace(/wa\.me\/919876543210/g, 'wa.me/<?= e($whatsapp) ?>');
fs.writeFileSync(path.join(root, 'pages', 'taxi-booking.php'), html);
console.log('Wrote taxi-booking.php');
