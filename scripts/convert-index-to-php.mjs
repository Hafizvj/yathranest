/**
 * Convert index.html → index.php with .php links and enquiry handler hooks.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
let html = fs.readFileSync(path.join(root, 'index.html'), 'utf8');

html = html.replace(/\.html/g, '.php');
html = html.replace('<!DOCTYPE html>', `<?php
require_once __DIR__ . '/includes/bootstrap.php';
$assetDepth = '';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = preg_replace('/\\D/', '', setting('whatsapp', '919876543210'));
$home = page_content('home');
$sections = $home['sections'] ?? [];
$csrf = csrf_token();
?>
<!DOCTYPE html>`);

// Inject CSRF + action into enquiry forms that use data-enquiry-form
html = html.replace(
  /<form data-enquiry-form([^>]*)>/g,
  `<form data-enquiry-form$1 action="handlers/enquiry.php" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="type" value="general" />
        <input type="hidden" name="source_page" value="index.php" />`
);

// Replace hardcoded phone/email/whatsapp in footer-ish areas with PHP echoes where obvious
html = html.replace(/\+91 98765 43210/g, '<?= e($phone) ?>');
html = html.replace(/hello@yathranest\.com/g, '<?= e($email) ?>');
html = html.replace(/wa\.me\/919876543210/g, 'wa.me/<?= e($whatsapp) ?>');
html = html.replace(/tel:\+919876543210/g, 'tel:<?= e(preg_replace(\'/[^\\d+]/\', \'\', $phone)) ?>');
html = html.replace(/mailto:hello@yathranest\.com/g, 'mailto:<?= e($email) ?>');

fs.writeFileSync(path.join(root, 'index.php'), html);
console.log('Wrote index.php');
