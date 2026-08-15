-- Adds the travel start date captured by the Request Pricing form.
-- Safe to re-run: drops out with a duplicate column error if already applied.

ALTER TABLE inquiries
  ADD COLUMN travel_date DATE NULL AFTER interest;
