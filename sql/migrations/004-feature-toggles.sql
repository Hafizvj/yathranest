-- Default catalog visibility flags (ON). Safe to re-run.
INSERT INTO settings (setting_key, setting_value) VALUES
  ('feature_packages', '1'),
  ('feature_resorts', '1'),
  ('feature_getaways', '1'),
  ('feature_gift_cards', '1'),
  ('feature_investments', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
