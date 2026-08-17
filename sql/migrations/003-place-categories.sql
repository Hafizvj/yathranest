-- A place can belong to several listing categories (Kerala, South India,
-- Domestic, International), so packages visiting it appear on each of them.
-- catalog_scope is kept in sync with the first category for older queries.
-- Safe to re-run: the ALTER drops out with a duplicate column error if applied.

ALTER TABLE places
  ADD COLUMN catalog_scopes_json JSON NULL AFTER catalog_scope;

UPDATE places
  SET catalog_scopes_json = JSON_ARRAY(IF(catalog_scope IS NULL OR catalog_scope = '', 'kerala', catalog_scope))
  WHERE catalog_scopes_json IS NULL OR JSON_LENGTH(catalog_scopes_json) = 0;
