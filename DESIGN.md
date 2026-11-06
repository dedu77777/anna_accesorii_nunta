# Anna Accesorii Nuntă design context

The site combines a wedding-accessory storefront with practical shopping flows. The checkout should feel like the existing cart: quiet, readable, and focused on completing an order.

## Existing visual system

- Runtime color tokens live in `web/css/global.css`: background `#faf8f5`, white surfaces, text `#292625`, muted text `#80766e`, border `#e9e3dc`, and warm accent `#c3a779`.
- Headings use Georgia and body copy uses Arial. Preserve this pairing across cart, checkout, and confirmation.
- Product photography carries the visual character. Forms use white panels, restrained borders, and clear labels.
- Customer-facing language is Romanian. Prices display in MDL.

## Checkout behavior and layout

- Desktop: customer details and a separate order summary. Phone: stacked form and summary without horizontal overflow.
- Native controls own typing, radio selection, and checkbox behavior. The delivery address appears only for home delivery and is checked again on the server.
- Validation stays beside its field; a short summary announces errors. The submit button shows pending state by disabling during a valid submission.
- The confirmation page shows only the order number, product total, delivery method, and pending status. The server authorizes it by session.

Use `web/css/global.css` as the canonical color source. Checkout-specific spacing and composition live in `web/css/checkout.css`; this file does not define a new site-wide palette.
