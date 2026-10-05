-- Resorts a gift card can be redeemed at (list of resort ids).
ALTER TABLE gift_cards ADD COLUMN resorts_json JSON NULL AFTER features_json;
