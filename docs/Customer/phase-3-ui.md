# Customer Phase 3 UI Shell

## Scope

Phase 3 adds the reusable Customer-owned visual shell and responsive dashboard.
It intentionally does not query vehicles, create or change bookings, process
payments, send chat messages, or implement any later-feature business rules.

## Design references used

The implementation follows the live Phase 1 PHP structure first, then the
Customer board from `customer.zip`, the available Member 2 context, and the
approved values in the Phase 3 brief. The specifically named
`We need to finalize one UI design s.txt`, `Prototype_Project-main.zip`, and
`LANKA_RENTERS_MASTER_CONTEXT.md` were not present locally.

The board influenced the white left sidebar, compact top bar, blue active
navigation state, verification next-action card, four status cards, progress
timeline, and restrained white-card layout. Prototype ideas were translated
into plain PHP, CSS, and JavaScript; no prototype code or dependency was copied.

## Reusable shell

All guarded Customer pages share:

- `components/layout/header.php` for the document head and Customer assets
- `components/layout/sidebar.php` for real Customer routes and safe logout
- `components/layout/navbar.php` for page context, notifications, and profile
- `components/layout/page-header.php` for page title, description, and action
- `components/layout/footer.php` for the Customer footer
- `assets/css/customer-foundation.css` for the Customer design system
- `assets/js/customer-ui.js` for menu and dropdown disclosure behaviour only

Navigation and asset URLs continue to use `customer_url()` so they work from a
localhost subfolder. PHP includes remain anchored to `__DIR__` or `dirname()`.

## Display-only dashboard data

`CustomerController::dashboard()` contains a clearly marked Phase 3 display
array for verification, zero-value booking statistics, rental readiness
progress, quick links, and reminders. It does not represent database records.
Later phases should replace it with model results only after the shared database
contract is approved.

## Later-feature placeholders

Verification, inspection, driver change, and vehicle return have guarded
Customer-owned placeholder routes. Existing later-feature routes use the same
shell through `_foundation_page.php`. These pages provide meaningful navigation
without pretending that their business operations are complete.

## Accessibility and responsive behaviour

- A skip link and visible keyboard focus styles are provided.
- The active navigation item uses `aria-current="page"` as well as colour.
- The mobile menu and profile menu maintain `aria-expanded` values.
- Escape and outside clicks close disclosure UI.
- Without JavaScript, the mobile sidebar remains in normal document flow.
- Status text accompanies every status colour.
- Dashboard grids reduce from four/two columns to one column on small screens.
- Reduced-motion preferences are respected.

## Shared coordination notes

No protected shared file changed in Phase 3. A common login-page visual refresh
would require coordinated work in protected authentication files and is outside
this phase. The shared PDO/database blocker documented in Phase 1 also remains
for later database-backed phases; it does not prevent this display-only UI.
