-- Supports the minimal package form: multiple travel types, per-night stays,
-- brochure PDFs, featured ranking, and a catalog scope per place so listing
-- pages can be derived from the destinations chosen on a package.
-- Safe to re-run: each statement drops out with a duplicate error if applied.

ALTER TABLE places
  ADD COLUMN catalog_scope VARCHAR(40) NOT NULL DEFAULT 'kerala' AFTER label;

ALTER TABLE packages
  ADD COLUMN types_json JSON NULL AFTER type;

ALTER TABLE packages
  ADD COLUMN stays_json JSON NULL AFTER stay_summary;

ALTER TABLE packages
  ADD COLUMN itinerary_pdf VARCHAR(255) NOT NULL DEFAULT '' AFTER gallery_json;

ALTER TABLE packages
  ADD COLUMN price_chart_pdf VARCHAR(255) NOT NULL DEFAULT '' AFTER itinerary_pdf;

ALTER TABLE packages
  ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER accommodation;

ALTER TABLE packages
  ADD KEY idx_packages_featured (is_featured);

-- Seed the new columns from the single-value data they replace.
UPDATE packages
  SET types_json = JSON_ARRAY(IF(type IS NULL OR type = '', 'leisure', type))
  WHERE types_json IS NULL OR JSON_LENGTH(types_json) = 0;

-- Only rows still on the default are touched, so a re-run leaves scopes
-- an admin has since changed alone.
UPDATE places
  SET catalog_scope = 'south'
  WHERE catalog_scope = 'kerala'
    AND slug IN ('mysore', 'ooty', 'coorg', 'chikmagalur', 'valparai', 'kodaikanal');

UPDATE places
  SET catalog_scope = 'domestic'
  WHERE catalog_scope = 'kerala'
    AND slug IN ('lakshadweep', 'goa', 'andaman');
