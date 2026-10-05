<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Gift Cards | YathraNest';
$metaDescription = 'YathraNest travel gift cards — enquire for personalised options. No online pricing.';
$enquiryType = 'gift';
$enquiryInterest = 'Gift card';
$enquirySource = 'pages/gift-cards.php';
$navActive = 'gift';
$giftsOn = feature_enabled('gift_cards');
$bodyAttrs = $giftsOn ? '' : 'data-auto-enquiry="1"';

$cards = [];
$resortsById = [];
// Resort pages auto-open the enquiry form while resort listings are hidden, so only link to them when shown.
$resortLinks = feature_enabled('resorts');
try {
    if ($giftsOn) {
        $cards = catalog_list('gift_cards');
        foreach (catalog_list('resorts') as $resort) {
            $resortsById[(int) $resort['id']] = $resort;
        }
    }
} catch (Throwable $e) {
}
$giftResortModals = [];

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/gift.jpg" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Gift Cards' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Gifting</p>
        <h1>Give the Gift of Travel</h1>
        <p class="page-head__lead">Let someone choose the journey that suits them — packages, stays or weekend escapes, wrapped in a YathraNest gift card.</p>
        <div class="page-head__chips">
          <?= yn_chip('gift', 'Any occasion') ?>
          <?= yn_chip('calendar', 'Flexible redemption') ?>
          <?= yn_chip('tag', 'Value on enquiry') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">Options</p>
          <h2>Gift card options</h2>
          <p>Choose a style of gift card — value, delivery and fulfilment details are shared after your enquiry.</p>
        </div>
      </div>

      <?php if (!$giftsOn): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('chat') ?></div>
          <h2>Gift cards on request</h2>
          <p>Tell us the occasion and who it's for — we'll put together the right gift.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
          </div>
        </div>
      <?php elseif (!$cards): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('gift') ?></div>
          <h2>Gift cards on request</h2>
          <p>Tell us the occasion and who it's for — we'll put together the right gift.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
          </div>
        </div>
      <?php else: ?>
        <div class="tile-grid tile-grid--3">
          <?php foreach ($cards as $i => $card): ?>
            <article class="tile<?= $i === 1 ? ' tile--featured' : '' ?>" data-reveal>
              <?php if ($i === 1): ?><span class="tile__ribbon">Most gifted</span><?php endif; ?>
              <span class="tile__icon"><?= yn_icon('gift') ?></span>
              <h3><?= e($card['title']) ?></h3>
              <p><?= e($card['blurb']) ?></p>
              <?php if (!empty($card['features'])): ?>
                <ul class="check-list">
                  <?php foreach ($card['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <?php
                $cardResorts = gift_card_resorts($card, $resortsById);
                $modalId = 'gift-resorts-' . (int) $card['id'];
                if ($cardResorts) {
                    $giftResortModals[] = ['id' => $modalId, 'card' => $card, 'resorts' => $cardResorts];
                }
              ?>
              <div class="tile__foot<?= $cardResorts ? ' tile__foot--split' : '' ?>">
                <a class="btn btn--<?= $i === 1 ? 'primary' : 'secondary' ?> btn--block" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($card['title']) ?> gift card">Request Information</a>
                <?php if ($cardResorts): ?>
                  <button class="btn btn--outline btn--block gift-resorts-btn" type="button" data-open-modal="<?= e($modalId) ?>" aria-haspopup="dialog">
                    <?= yn_icon('bed') ?>
                    View available resorts
                    <span class="gift-resorts-btn__count"><?= count($cardResorts) ?></span>
                  </button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php foreach ($giftResortModals as $gm):
          $gTitle = (string) $gm['card']['title'];
          $gCount = count($gm['resorts']);
          ?>
          <div class="modal gift-resorts" id="<?= e($gm['id']) ?>" role="dialog" aria-modal="true" aria-labelledby="<?= e($gm['id']) ?>-title">
            <div class="modal__backdrop"></div>
            <div class="modal__dialog modal__dialog--xl gift-resorts__dialog">
              <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
              <header class="gift-resorts__head">
                <p class="gift-resorts__eyebrow"><?= yn_icon('gift') ?><?= e($gTitle) ?></p>
                <h2 id="<?= e($gm['id']) ?>-title">Available resorts</h2>
                <p class="gift-resorts__lead">Redeem this gift card at any of these <?= $gCount ?> stay<?= $gCount === 1 ? '' : 's' ?>. Dates and availability are confirmed after your enquiry.</p>
              </header>
              <div class="gift-resorts__body">
                <div class="gift-resorts__grid">
                  <?php foreach ($gm['resorts'] as $r):
                    $rImg = media_src((string) ($r['image'] ?? ''), '../', 'resort.jpg');
                    $rHref = 'resort-details.php?resort=' . rawurlencode((string) $r['slug']);
                    ?>
                    <article class="card gift-resort-card">
                      <<?= $resortLinks ? 'a href="' . e($rHref) . '" tabindex="-1" aria-hidden="true"' : 'div' ?> class="card__media">
                        <img src="<?= e($rImg) ?>" alt="" loading="lazy" />
                        <?php if (!empty($r['category'])): ?>
                          <span class="card__badge"><?= e($r['category']) ?></span>
                        <?php endif; ?>
                      </<?= $resortLinks ? 'a' : 'div' ?>>
                      <div class="card__body">
                        <?php if (!empty($r['location'])): ?>
                          <p class="card__meta"><?= e($r['location']) ?></p>
                        <?php endif; ?>
                        <h3 class="card__title">
                          <?php if ($resortLinks): ?><a href="<?= e($rHref) ?>"><?= e($r['title']) ?></a><?php else: ?><?= e($r['title']) ?><?php endif; ?>
                        </h3>
                        <?php if (!empty($r['summary'])): ?>
                          <p class="card__text"><?= e($r['summary']) ?></p>
                        <?php endif; ?>
                        <?php if ($resortLinks): ?>
                          <a class="link-arrow gift-resort-card__link" href="<?= e($rHref) ?>">View resort</a>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
              <footer class="gift-resorts__foot">
                <span class="gift-resorts__count"><?= yn_icon('bed') ?><?= $gCount ?> resort<?= $gCount === 1 ? '' : 's' ?></span>
                <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($gTitle) ?> gift card">Enquire about this gift card</a>
              </footer>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="section-head" style="margin-top:3.5rem">
        <div>
          <p class="section-head__eyebrow">How it works</p>
          <h2>Gifting made simple</h2>
        </div>
      </div>
      <div class="tile-grid tile-grid--3">
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('chat') ?></span>
          <h3>1 · Tell us the occasion</h3>
          <p>Share who it's for, the occasion and the value you have in mind.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('mail') ?></span>
          <h3>2 · We prepare the card</h3>
          <p>We personalise the card and send it digitally or as a printed keepsake.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('plane') ?></span>
          <h3>3 · They travel</h3>
          <p>The recipient redeems it against any package, stay or getaway with us.</p>
        </div>
      </div>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Corporate gifting</p>
        <h2>Gifting for a team or event?</h2>
        <p>We arrange bulk travel gift cards for teams, weddings and celebrations — tell us the details.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Request Information
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
