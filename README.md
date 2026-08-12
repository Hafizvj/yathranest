# YathraNest — Frontend Website

Static, frontend-only prototype for **YathraNest**, a travel company offering packages, taxi services, resort stays, weekend getaways, gift cards, and investment plan enquiries.

## Stack

- HTML5
- CSS3
- Vanilla JavaScript

No frameworks, no backend, no payments, no authentication.

## Business rule

**Pricing is never displayed online.** CTAs use enquiry language only:

- Request Pricing
- Enquire Now
- Get a Quote
- Check Availability
- I'm Interested
- Request Information

Flow: **Browse → Explore → Enquire → YathraNest provides pricing**

## Quick start

Open `index.html` in a browser, or serve the folder locally:

```bash
# Python
python -m http.server 8080

# Node (if available)
npx serve .
```

Then visit `http://localhost:8080`.

## Structure

```text
yn/
├── index.html
├── pages/                  # All inner pages
├── css/
│   ├── style.css           # Tokens, base, layout, header/footer
│   ├── components.css      # Cards, forms, modals, buttons
│   └── responsive.css      # Breakpoints
├── js/
│   ├── main.js
│   ├── navigation.js
│   ├── filters.js
│   ├── forms.js
│   └── gallery.js
├── assets/
│   ├── images/
│   ├── icons/
│   └── logo/
└── README.md
```

## Pages

| Page | File |
|------|------|
| Homepage | `index.html` |
| Kerala Packages | `pages/kerala-packages.html` |
| South Indian Packages | `pages/south-indian-packages.html` |
| Domestic Packages | `pages/domestic-packages.html` |
| International Packages | `pages/international-packages.html` |
| Package Details | `pages/package-details.html` |
| Taxi Booking (enquiry) | `pages/taxi-booking.html` |
| Resort Booking | `pages/resort-booking.html` |
| Resort Details | `pages/resort-details.html` |
| Weekend Getaways | `pages/weekend-getaways.html` |
| Gift Cards | `pages/gift-cards.html` |
| Investment Plans | `pages/investment-plans.html` |
| About / Contact / FAQ / Legal | `pages/about.html`, `contact.html`, `faq.html`, `terms.html`, `privacy.html` |

## Interactions (frontend-only)

- Mobile navigation drawer
- Mock filters / search / pagination
- FAQ & itinerary accordions
- Image lightbox gallery
- Enquiry forms with validation + success modal
- Smooth in-page scrolling

## Design tokens

Defined in `css/style.css` as CSS variables (`--primary`, `--accent`, `--background`, etc.) for easy theming.

## Images

Travel photos live in `assets/images/` (local JPG files). The UI does **not** depend on external CDNs.

To replace any image later, swap the file in `assets/images/` while keeping the same filename, or update the `src` paths in the HTML.

## Next steps for production

1. Replace sample phone/email/WhatsApp details
2. Connect enquiry forms to an API / CRM
3. Swap Unsplash images for owned media
4. Add real map embeds on resort pages
5. Have legal review Terms & Privacy
