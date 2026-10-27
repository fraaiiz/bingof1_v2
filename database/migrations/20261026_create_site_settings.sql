CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(50) NOT NULL,
    setting_value VARCHAR(20) NOT NULL,
    PRIMARY KEY (setting_key)
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('maintenance', '0'),
    ('chat_open', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
