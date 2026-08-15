<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Taxi Cab Booking | YathraNest';
$metaDescription = 'Request a taxi quote with YathraNest — airport transfers, outstation and local trips. No online payment.';
$enquiryType = 'taxi';
$enquiryInterest = 'Taxi booking';
$enquirySource = 'pages/taxi-booking.php';
$navActive = 'taxi';

$phone = setting('phone', '+91 98765 43210');
$whatsapp = preg_replace('/\D/', '', setting('whatsapp', '919876543210'));

$vehicles = [
    ['value' => 'Sedan', 'label' => 'Sedan', 'note' => 'Comfortable for 3–4 travellers'],
    ['value' => 'SUV', 'label' => 'SUV', 'note' => 'Extra space for luggage'],
    ['value' => 'Innova / Crysta', 'label' => 'Innova / Crysta', 'note' => 'Ideal for families'],
    ['value' => 'Tempo Traveller', 'label' => 'Tempo Traveller', 'note' => 'Groups & larger parties'],
];

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/car-taxi.jpg" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Taxi Booking' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Rides</p>
        <h1>Taxi Cab Booking</h1>
        <p class="page-head__lead">Airport transfers, outstation trips and local travel with vetted drivers. Send an enquiry and we'll confirm availability and pricing.</p>
        <div class="page-head__chips">
          <?= yn_chip('car', 'Sedan to tempo traveller') ?>
          <?= yn_chip('clock', 'Available 24/7') ?>
          <?= yn_chip('wallet', 'No online payment') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="article-layout">
        <div>
          <div class="form-card" id="taxi-form">
            <div class="form-card__head">
              <h2>Get a quote</h2>
              <p>This is an enquiry form — not online booking or payment. Our team contacts you with vehicle options and fares.</p>
            </div>
            <div class="form-card__body">
              <form data-enquiry-form data-success-modal="success-modal" novalidate action="../handlers/enquiry.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="taxi" />
                <input type="hidden" name="source_page" value="pages/taxi-booking.php" />
                <input type="hidden" name="interest" value="Taxi booking" />

                <div class="form-section">
                  <h3 class="form-section__title"><span class="form-section__step">1</span>Trip details</h3>
                  <div class="form-grid form-grid--2">
                    <div class="form-group">
                      <label for="pickup">Pickup location</label>
                      <input class="form-control" id="pickup" name="pickup" type="text" required placeholder="City / airport / address" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="drop">Drop location</label>
                      <input class="form-control" id="drop" name="drop" type="text" required placeholder="Destination" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="date">Date</label>
                      <input class="form-control" id="date" name="date" type="date" required />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="time">Time</label>
                      <input class="form-control" id="time" name="time" type="time" required />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                      <label for="trip-type">Trip type</label>
                      <select class="form-control" id="trip-type" name="tripType" required>
                        <option value="">Select trip type</option>
                        <option>Airport transfer</option>
                        <option>Local (8 hrs / 80 km)</option>
                        <option>Outstation one-way</option>
                        <option>Outstation round trip</option>
                      </select>
                      <span class="field-error"></span>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <h3 class="form-section__title"><span class="form-section__step">2</span>Vehicle preference</h3>
                  <div class="form-group" data-required-group="vehicle">
                    <div class="radio-group">
                      <?php foreach ($vehicles as $i => $vehicle): ?>
                        <label class="choice-card">
                          <span class="choice-card__top">
                            <input type="radio" name="vehicle" value="<?= e($vehicle['value']) ?>"<?= $i === 0 ? ' required' : '' ?> />
                            <strong><?= e($vehicle['label']) ?></strong>
                          </span>
                          <span><?= e($vehicle['note']) ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                    <span class="field-error"></span>
                  </div>
                </div>

                <div class="form-section">
                  <h3 class="form-section__title"><span class="form-section__step">3</span>Traveller &amp; contact details</h3>
                  <div class="form-grid form-grid--2">
                    <div class="form-group">
                      <label for="passengers">Number of passengers</label>
                      <input class="form-control" id="passengers" name="passengers" type="number" min="1" max="20" required value="2" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="name">Full name</label>
                      <input class="form-control" id="name" name="name" type="text" required autocomplete="name" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="phone">Phone</label>
                      <input class="form-control" id="phone" name="phone" type="tel" required data-validate="phone" autocomplete="tel" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group">
                      <label for="email">Email</label>
                      <input class="form-control" id="email" name="email" type="email" required autocomplete="email" />
                      <span class="field-error"></span>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                      <label for="notes">Additional requirements</label>
                      <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Child seat, extra stops, flight number..."></textarea>
                    </div>
                  </div>
                </div>

                <p class="form-note"><?= yn_icon('info') ?>We use your details only to share a quote and coordinate the ride. Fares depend on distance, vehicle and timing.</p>

                <div class="btn-group">
                  <button class="btn btn--primary" type="submit">Get a Quote</button>
                  <a class="btn btn--secondary" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer">Ask on WhatsApp</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <aside class="article-layout__aside">
          <div class="quote-card">
            <p class="quote-card__eyebrow">Need it now?</p>
            <h3>Call our travel desk</h3>
            <p>For same-day pickups and airport runs, a quick call is fastest.</p>
            <div class="btn-group">
              <a class="btn btn--light" href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a>
            </div>
            <p class="quote-card__note"><?= yn_icon('headset') ?>Support available 24/7</p>
          </div>

          <div class="panel" style="margin-top:1rem">
            <div class="info-list">
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('shield') ?></span>
                <span>
                  <span class="info-row__label">Vetted drivers</span>
                  <span class="info-row__value">Verified &amp; experienced</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('route') ?></span>
                <span>
                  <span class="info-row__label">Flexible routes</span>
                  <span class="info-row__value">Add stops on request</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('wallet') ?></span>
                <span>
                  <span class="info-row__label">Transparent fares</span>
                  <span class="info-row__value">Shared before you confirm</span>
                </span>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
