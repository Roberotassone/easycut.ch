<?php
// ============================
// DATABASE (INFOMANIAK) ✅
// ============================
define('DB_HOST', 'dd5j3y.myd.infomaniak.com');
define('DB_NAME', 'dd5j3y_easycut_db');
define('DB_USER', 'dd5j3y_easycut');
define('DB_PASS', 'Fantasia1810.');

// ============================
// SITO
// ============================
define('SITE_URL', 'https://www.easycut.ch');

// ============================
// EMAIL SMTP (INFOMANIAK) ✅
// ============================
define('SMTP_HOST', 'mail.infomaniak.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'info@easycut.ch');      // EMAIL REALE
define('SMTP_PASS', 'Fantasia181007'); // <- incolla qui
define('SMTP_FROM', 'info@easycut.ch');

// ============================
// STRIPE (BACKEND) ✅
// ============================
// ⚠️ SOLO sk_live (MAI pk_ QUI)
define('STRIPE_SECRET', 'sk_live_INCOLLA_LA_TUA_SECRET_KEY');

// Webhook secret
define('STRIPE_WEBHOOK_SECRET', 'whsec_LKFgfCoHMWh7fp61JscLV0xaj4My1cDi');
