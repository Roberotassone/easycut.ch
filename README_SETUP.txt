HAIRSHAKE MVP — SETUP (Infomaniak)

1) Crea un database MySQL in Infomaniak (Manager).
2) Importa /db/database.sql
3) Modifica /api/config.php con credenziali DB e chiavi Stripe.
4) Carica cartelle /public /api /db /docs sul tuo hosting.
5) Apri /public/index.html nel browser.

Pagamenti:
- crea un account Stripe
- imposta webhook verso: https://TUODOMINIO.tld/api/stripe_webhook.php
- eventi: payment_intent.succeeded
- inserisci STRIPE_WEBHOOK_SECRET in /api/config.php

Nota:
- Questo è un MVP “come Pawshake” con funzioni core (account, ricerca, profili, servizi,
  disponibilità, prenotazioni, chat, recensioni, pagamento Stripe via PaymentIntent+webhook).
- Per “100% identico” servono: verifica identità, KYC, payout automatici, dispute, moderazione,
  multi-lingua completa, notifiche email/SMS, pannello admin avanzato.
