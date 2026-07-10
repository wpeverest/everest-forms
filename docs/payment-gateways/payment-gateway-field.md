---
title: Payment Gateway Field
slug: payment-gateway-field
category: Payment Gateways
doc_category: payment-gateways
plugin: Everest Forms Pro
since: 1.9.15
publish_url: https://docs.everestforms.net/docs/payment-gateway-field/
---

# Payment Gateway Field

The **Payment Gateway** field lets visitors choose how they pay on a single form—Stripe, PayPal, Square, Authorize.Net, Mollie, or Razorpay—without building separate forms or wiring Conditional Logic between payment methods.

This replaces the older workflow where you added a Multiple Choice field and used Conditional Logic on the Payments tab and Credit Card field. With the Payment Gateway field, one drag-and-drop field handles gateway selection, card UI, and redirect messaging.

```
Prerequisites:
- Everest Forms Pro
- At least one payment gateway add-on installed and activated
- Global credentials configured for each gateway you want to offer
- SSL (HTTPS) recommended for card-based gateways (Stripe, Square, Authorize.Net)
```

## What's New in Recent Versions? #

With **Everest Forms Pro 1.9.15+**, the **Payment Gateway** field is available under **Payment Fields** in the form builder.

| Before (legacy) | With Payment Gateway field |
|-----------------|----------------------------|
| Multiple Choice for PayPal / Stripe | Dedicated **Payment Gateway** field with branded cards |
| Enable each gateway on the **Payments** tab | Gateways appear when the add-on is active and credentials are saved |
| Conditional Logic on Payments tab + Credit Card field | Card fields show/hide automatically when the user picks a gateway |
| One Credit Card field per Stripe form | Stripe, Square, and Authorize.Net card UI mounts inside the selector when needed |

**One field per form:** Only one Payment Gateway field can be added to a form. After you add it, legacy **Credit Card**, **Square Payment**, and **Authorize.Net** fields are hidden from the field list to avoid duplicate payment UIs.

## Supported Payment Gateways #

| Gateway | Add-on | Checkout type |
|---------|--------|----------------|
| Stripe | Everest Forms Stripe | On-page card (Stripe Elements) |
| PayPal Standard | Everest Forms PayPal Standard | Redirect to PayPal |
| Square | Everest Forms Square (Pro) | On-page card (Square Web Payments SDK) |
| Authorize.Net | Everest Forms Authorize.Net | On-page card (Accept.js) |
| Mollie | Everest Forms Mollie (Pro) | Redirect to Mollie |
| Razorpay | Everest Forms Razorpay | Redirect / Razorpay checkout |

A gateway is selectable on the frontend only when:

1. Its add-on is **active**
2. **Global credentials** are configured (PayPal can also use per-form email when global settings are disabled)
3. The gateway is **enabled** in the Payment Gateway field **Gateways** list

## Installation #

1. Purchase the **Everest Forms Pro** plugin from [WPEverest](https://wpeverest.com/wordpress-plugins/everest-forms/pricing/).
2. Install and activate **Everest Forms Pro** from your WordPress dashboard.
3. Install and activate the payment add-ons you need (Stripe, PayPal Standard, Square, etc.) from **Everest Forms → Add-ons**.
4. Configure each gateway under **Everest Forms → Settings → Payments**.

```
For a detailed guide, read our documentation on how to install and activate Everest Forms Pro.
```

## Configure Global Payment Credentials #

The Payment Gateway field does not replace global payment settings. Each add-on still needs API keys, merchant email, or tokens saved once site-wide.

Navigate to **Everest Forms → Settings → Payments** and configure:

| Gateway | Settings to complete |
|---------|---------------------|
| Stripe | Publishable key and Secret key (Live/Test) |
| PayPal Standard | PayPal email, Mode (Sandbox/Production), Payment type |
| Square | Application ID, Access token, Location ID |
| Authorize.Net | API Login ID and Transaction Key |
| Mollie | API key |
| Razorpay | Key ID and Secret |

Also set **Currency** under the General payments section so all gateways charge in the correct currency.

Gateway-specific setup guides:

- [PayPal Standard](https://docs.everestforms.net/docs/paypal-standard/)
- [Stripe](https://docs.everestforms.net/docs/stripe/)
- [Square](https://docs.everestforms.net/docs/square/)
- [Authorize.Net](https://docs.everestforms.net/docs/authorize-net/)
- [Mollie](https://docs.everestforms.net/docs/mollie/)
- [Razorpay](https://docs.everestforms.net/docs/razorpay/)

## Add the Payment Gateway Field to Your Form #

1. Open **Everest Forms → All Forms** and edit your form (or create a new one).
2. In the builder, open **Fields → Payment Fields**.
3. Drag **Payment Gateway** into the form.
4. Add your payment line items (for example **Single Item**, **Multiple Choice**, **Total**).
5. Click **Save**.

The field is required by default so visitors must pick a payment method before submitting.

## Choose Which Gateways Appear on the Form #

1. Click the **Payment Gateway** field in the builder.
2. In **Field Options** on the left, open **Gateways**.
3. For each gateway row:
   - Use the **toggle** to include or exclude it on the frontend.
   - **Drag** the handle to change the order of cards on the form.
4. Expand a gateway row (chevron) to open per-gateway settings (email, recurring options, field mapping, and more).

**Notes:**

- Gateways without configured credentials show as disabled in the builder until you connect them globally.
- **PayPal** is unchecked by default if no global PayPal email is saved. Configure PayPal under **Settings → Payments** or set a per-form email in the PayPal accordion.
- If every gateway toggle is off and you save, the form shows a message asking you to enable at least one gateway.

## Per-Gateway Settings in the Form Builder #

When you expand a gateway in the **Gateways** list, settings from that add-on appear inline—similar to the old **Payments** tab, but scoped to this form and gateway.

| Gateway | Typical accordion options |
|---------|---------------------------|
| PayPal | Use global settings, PayPal email, Sandbox/Production, Cancel URL |
| Stripe | Recurring subscription toggle, iDEAL, map customer fields to Stripe |
| Authorize.Net | Map Customer Email (required for subscriptions) |
| Square | Subscription-related options when a Subscription Plan field is present |
| Mollie | Subscription description and recurring defaults |

You do **not** need to enable **Enable PayPal** or **Enable Stripe** on the top **Payments** tab when using the Payment Gateway field. Selection is driven by the field allowlist and global credentials.

## More on Setup and Configuration #

### Payment Fields #

Add payment fields the same way as other Everest Forms payment forms.

#### Single Item

Set a fixed price or allow user-defined amounts (donations). Item types:

- **Pre-Defined:** Users cannot change the price on the frontend.
- **User Defined:** Users enter the amount (suitable for donations).
- **Hidden:** Price is not shown; the configured item price is charged.

#### Multiple Choice / Checkbox / Quantity / Total

Use these fields to build carts with multiple line items. The **Total** field displays the sum of selected payment fields.

### Build a One-Time Payment Form #

Example: sell a single product for a fixed price with PayPal or Stripe.

1. Add **Payment Gateway** and enable **Stripe** and **PayPal** (or any combination).
2. Add **Single Item** with your price.
3. Optionally add **Total** to display the order sum.
4. Add contact fields (Name, Email, and so on).
5. Save and embed the form with its shortcode or block.

**Checkout behavior:**

- **Stripe / Square / Authorize.Net:** Card fields appear below the gateway cards when the user selects that method.
- **PayPal / Mollie / Razorpay:** A secure redirect message appears. The user completes payment on the provider site and returns to your confirmation URL.

### Subscription Plan on Form #

Use the **Subscription Plan** field together with the Payment Gateway field.

1. Add **Payment Gateway** and enable gateways that support subscriptions.
2. Add **Subscription Plan** and configure plans (price, billing period, trial, expiry date).
3. Configure gateway-specific subscription options in each gateway accordion (for example map **Customer Email** for Authorize.Net).
4. Save the form.

When both **Payment Gateway** and **Subscription Plan** are on the form, Everest Forms treats checkout as subscription mode for the selected gateway. You do **not** need to turn on **Enable recurring subscription payments** on the legacy Payments tab for selector-based forms.

Subscription features (trial period, expiry date, prorated billing) follow the same rules as each gateway documentation:

- [PayPal Standard – Subscription Plan](https://docs.everestforms.net/docs/paypal-standard/)
- [Stripe – Stripe Subscription for Recurring Payments](https://docs.everestforms.net/docs/stripe/)

## Frontend View #

On the live form, visitors see a card grid of payment methods (logos for Stripe, PayPal, Square, and others).

- Users must choose one gateway before submit (unless only one gateway is enabled—it is auto-selected).
- Selecting Stripe, Square, or Authorize.Net reveals the card form below without a separate Credit Card field.
- Selecting PayPal, Mollie, or Razorpay shows: *You'll be redirected to [Gateway] to complete your purchase securely.*

Works with shortcode embeds, the Gutenberg block, and AJAX form submission.

## Legacy Payments Tab vs Payment Gateway Field #

| Scenario | Recommended approach |
|----------|---------------------|
| One gateway only, simple form | Legacy **Payments** tab + Credit Card (Stripe) or PayPal enable toggle |
| Visitor chooses PayPal or Stripe (or more) | **Payment Gateway** field |
| Subscriptions with plan choices | **Subscription Plan** + **Payment Gateway** |
| Old forms with Conditional Logic | Keep working; migrate when convenient |

Do not add a Payment Gateway field **and** standalone Credit Card, Square Payment, or Authorize.Net fields on the same form. The builder hides those fields when a Payment Gateway field exists.

### How to add Stripe as a payment option with PayPal? (legacy method) #

Older forms used a Multiple Choice field plus Conditional Logic on the Payments tab. New forms should use the **Payment Gateway** field instead.

If you maintain legacy forms, see:

- [PayPal Standard – How to add PayPal as a payment method with Stripe?](https://docs.everestforms.net/docs/paypal-standard/)
- [Stripe – How to add Stripe as a payment option with PayPal?](https://docs.everestforms.net/docs/stripe/)

## Troubleshooting #

### No payment methods appear on the form

- Confirm the payment add-on is active under **Everest Forms → Add-ons**.
- Confirm global credentials are saved under **Settings → Payments**.
- In the Payment Gateway field options, ensure at least one **Gateways** toggle is on.
- For PayPal, set a global PayPal email or enter a per-form email in the PayPal accordion.

### Please choose a valid payment method on submit

- The selected gateway must be in the field allowlist and connected globally.
- Users must select a valid gateway radio option (do not rely on CSS-only hiding).

### PayPal shows Things don't appear to be working

- Verify Sandbox mode matches your PayPal sandbox business email under **Settings → Payments → PayPal**.
- Ensure the form total is greater than zero.
- Check **Everest Forms → Tools → Payment Log** when logging is enabled.

### Stripe, Square, or Authorize.Net card form does not show

- Use **HTTPS** on the form page.
- Confirm the gateway toggle is on in the Payment Gateway field.
- Check the browser console for JavaScript conflicts with caching or minify plugins.

### Authorize.Net subscriptions fail

- Open the **Authorize.Net** accordion under the Payment Gateway field and map **Customer Email (Subscriptions)** to your form Email field.

## Finishing up #

Once your form is configured, click **Save** on the top right of the form builder.

Embed the form using the shortcode next to the Save button, or add the Everest Forms block in the block editor.

Payment entries appear under **Everest Forms → Entries** with gateway, amount, and status details.

---

*Category: [Payment Gateways](https://docs.everestforms.net/docs-category/payment-gateways/) · Last updated: June 2026*
