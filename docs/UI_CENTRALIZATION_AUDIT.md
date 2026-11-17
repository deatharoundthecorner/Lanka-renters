# Lanka Renters — UI Centralization, Navigability, Button Safety, and Chat Audit

Audit date: 2026-08-16  
Mode: read-only application audit; this document is the only file created.  
Implementation status: **NOT IMPLEMENTED — awaiting review**.

`docs/UI_CENTRALIZATION_AUDIT.md` was not present in this workspace at audit start, so this requested addendum is recorded as the initial audit document.

## 1. Executive finding

The interim functional core remains intact: authentication and the four assigned CRUDs are already runtime-verified, the major role routes load, and no raw SQL/PDO errors were observed in the completed runtime QA. The UI, however, is not yet one centralized shell. Customer, Owner, Driver, and Admin each use their own sidebar/header/component CSS implementation.

The current palette and general visual direction are already close enough that centralization should preserve the approved appearance. The work should consolidate shared tokens and shell behavior, not redesign the product.

Current interim UI-safety result:

| Priority | Current status | Evidence |
|---|---|---|
| Consistent UI across roles | **PARTIAL** | Similar blue/neutral palette, but four separate authenticated shells, logo treatments, typography choices, component classes, spacing, and chat layouts. |
| Complete navigation | **PASS with label/content exceptions** | The prior live smoke test passed 54 major Guest/role routes. Some Guest footer labels point to generic anchors, and some Admin dashboard links do not land on the specifically described filtered state. |
| Every visible action behaves safely | **PARTIAL** | Real mutations are generally POST/CSRF protected. Eight always-visible controls are [D] not implemented: six Customer incident-demo controls, the Owner bell, and the Admin bell. No visible [E] action was found in the audited current/empty-data UI. |
| No fatal errors | **PASS in current runtime** | Existing final runtime QA passed all major pages and PHP lint. |
| No SQL/PHP warnings exposed | **PASS in current runtime** | Existing runtime QA reports no PDO/SQLSTATE/stack-trace output. |
| Authentication | **PASS** | Customer, Owner, Driver, and Admin login/session/redirect/logout flows are verified. |
| Four assigned CRUDs | **PASS — preserve** | Driver Leave, Owner Vehicle, Customer Review, and Admin User C/R/U/D are verified against MariaDB. |
| Important workflows understandable | **PARTIAL** | Core rental and CRUD paths are understandable; Customer Incidents mixes a hard-coded demo page with separate real incident routes, and chat participant/support claims exceed current behavior. |

## 2. Existing UI architecture

The application currently has five visual surfaces rather than one shared visual shell.

| Surface | Shell/assets | Logo/identity | Notable difference |
|---|---|---|---|
| Guest | Global `public/assets/css/*`; top navigation and marketing footer | Circular `L` plus both “Lanka Renters” and “LankaRenters” spellings | No sidebar; several footer labels reuse two generic homepage anchors. |
| Customer | Ten Customer CSS files; shared Customer sidebar/navbar/layout components | `LR` mark; “Customer workspace” | Most structured component system; 272px sidebar, Customer-specific class names and CSRF bootstrap. |
| Owner | One large `owner-style.css`; Owner sidebar/header includes | Circular `L`; `LankaRenters`; separate “Vehicle Owner” chip | Uses Outfit and Owner-only components; header notification is an inert emoji button. |
| Driver | `driver.css`; Driver sidebar/navbar/header includes | Plain “Lanka Renters” text | Uses Inter, 240px sidebar, square/compact controls and a different header/profile arrangement. |
| Admin | `admin/assets/css/style.css`; Admin sidebar/header partials | Gradient circular `L`; `LankaRenters`; “Admin Workspace” | Uses Outfit/Inter, inline brand styling, hard-coded Admin identity, separate card/table/badge/modal system. |

The colors are already broadly compatible: Customer/Driver use `#1357C8`, Owner/Admin/Guest commonly use `#2563EB`, and all use light slate backgrounds. Centralization can therefore start from shared tokens and markup contracts while retaining the approved theme.

### Centralization target after review

Share the following implementation contracts across authenticated roles:

- one logo/wordmark treatment and spelling;
- one typography stack;
- one sidebar width, responsive drawer behavior, header height, content gutter, and page-header pattern;
- one button, form control, card, table, badge, alert, empty-state, modal, pagination, and avatar vocabulary;
- one icon treatment without decorative colored bubbles unless the color communicates status;
- one notification control behavior;
- one chat component;
- role differences limited to navigation entries, role label, data, permitted actions, and page content.

This is a consolidation target, not authorization to redesign or replace working pages.

## 3. Button/action classification rules

| Code | Meaning |
|---|---|
| [A] | Real UI action with an implemented server/client path and real application effect. |
| [B] | Partial or explicitly demo-safe action; it gives a truthful, contained result but is not final backend functionality. |
| [C] | Navigation/filter/read-only control only. |
| [D] | Not implemented; no real effect or the destination does not fulfill the label. |
| [E] | Broken, fatal, warning-producing, unsafe, or misleadingly reports success for an operation that did not occur. |

The inventory below groups repeated sidebar links, pagination links, row-level instances, and the same action rendered for multiple records. Visibility is data/status-dependent in many tables.

## 4. Guest action inventory

| Page/surface | Visible actions | Class | Finding |
|---|---|---|---|
| Header/navigation | Home, Vehicles, About, How It Works, Login/Register or Dashboard/Sign Out | [C], with authentication actions [A] | Targets load. Sign Out uses the shared GET logout endpoint; functional, but a future shared shell should prefer POST/CSRF consistently. |
| Home | Search Vehicles, Explore Vehicles, View All, vehicle View Details | [A] for search; [C] for destinations | Real database vehicle catalogue and detail routes. |
| Vehicles | Apply Filters, Clear All Filters, vehicle View Details | [A] filter/read path; [C] destinations | Safe GET filtering and real DB reads. |
| Vehicle Details | Login/Register/booking continuation according to session and role | [C] | Routes to authentication or the Customer booking path; no fake booking success. |
| Login/Register | Sign In, Register Account, cross-links | [A]/[C] | Runtime-verified authentication; public Admin registration is not exposed. |
| Footer | Support, Contact, FAQ, Privacy Policy, User Guidelines, Rental Rules | [C] | No 404, but multiple distinct labels point only to `#how-it-works` or `#why-us`; this is navigable but semantically incomplete. |

No Guest [E] action was found.

## 5. Customer action inventory

| Area | Visible actions | Class | Finding |
|---|---|---|---|
| Shared shell | Sidebar destinations, menu open/close, notification link, profile menu, Profile settings, Logout | [C], Logout [A] | Shared Customer JS handles menu/profile controls; notifications and POST/CSRF logout are real. |
| Dashboard | Browse vehicles, verification next step, latest booking/details, View All/quick links | [C] | Destinations are computed from real account/booking state. |
| Vehicle catalogue/details | Apply/Clear filters, pagination, View Details, Continue to Booking, View Verification | [A]/[C] | Database-backed and ownership/eligibility rechecked server-side. |
| Bookings | Apply/Clear filters, Create, View, Edit, Cancel, Payment, Inspection, Chat, Incident, Review links | [A]/[C] | Create/read/update/cancel paths are real. Cancel is POST/CSRF and preserves history. |
| Payments | Submit evidence, filters, View Details, evidence download, summary, booking/chat links | [A]/[C] | Database-backed payment path with safe empty/eligibility states. |
| Chat | Open booking chat, room list, Booking Details, Send message | [A] | Real booking-scoped MariaDB conversation with Customer ownership and participant checks. |
| Real incident routes | `incidents/create.php`: Save incident; `incidents/details.php`: read incident/booking | [A]/[C] | Real `incidents` persistence is available from an ongoing owned booking. |
| Incidents navigation page | Complete verification, add photos, add map link, submit report, choose replacement, choose refund | **[D]** | `incidents/index.php` is a hard-coded demo preview. Buttons are `type="button"` with `aria-disabled`, but not native `disabled`; they do nothing. It also shows fabricated settlement amounts. |
| Incident replacement decision | Record demo decision | [B] | Truthfully labeled session-only demo; it does not alter the replacement request. |
| Driver Change | New/Save/View Demo Request | [B] | Explicitly session-only, clearly labeled prototype; no Driver assignment is changed. |
| Returns | Start/Save/View Demo Return | [B] | Explicitly session-only, clearly labeled prototype. |
| Reviews | Write, View, Update, Delete Review | [A] | Verified Customer-owned database CRUD with POST/CSRF for mutations. |
| Notifications | All/Unread, related record, Mark Read/Mark All Read | [A]/[C] | Database-backed and Customer-scoped. |
| Profile/verification | Save profile, Save verification, cross-links | [A]/[C] | Database-backed Customer-owned updates. |
| Inspection | View Inspection/back links | [C] | Read-only database result and safe empty state. |

Customer interim recommendation: do not build a new incident/replacement system. After review, either route the visible Incidents navigation to the existing database-backed incident list/empty state, or make every demo control natively disabled with persistent “Available in final release” text and remove the fabricated settlement figures.

No visible Customer [E] action was found; the [D] incident controls are currently non-mutating but do not meet the requested final interim button standard.

## 6. Vehicle Owner action inventory

| Area | Visible actions | Class | Finding |
|---|---|---|---|
| Sidebar | Dashboard, Vehicles, Drivers, Bookings, Earnings, Inspections, Chat, Replacement Requests, Profile, Logout | [C], Logout [A] | Routes load; logout is POST/CSRF. |
| Header notification bell | Bell emoji button | **[D]** | No link, event handler, notification page, or safe informational state. |
| Vehicles | Add, Edit, status change, Deactivate, Delete, document view, dismiss alert | [A]/[C] | Verified Owner-scoped vehicle CRUD; deletion protects booking history. Document links depend on stored uploads. |
| Drivers | Send Connection Request | [A] | POST/CSRF through Owner Driver controller/model; no direct-chat action is provided. |
| Bookings/Earnings/Inspections | View records, status filters, Add Vehicle empty-state link | [C] | Real DB read/filter views. |
| Chat | Select room, Send message | [A] with partial behavior | Real participant-scoped messages. Opening the page auto-creates/enrolls Owner rooms during GET, which is a state change and should eventually move to an explicit/controlled path. |
| Replacement Requests | status filters, Approve, Reject | [A]/[C] | Real Owner-scoped POST/CSRF handlers. |
| Profile | Save changes | [A] | Authenticated profile update. |

No visible Owner [E] action was found. The notification bell is the only always-visible Owner [D] control.

## 7. Driver action inventory

| Area | Visible actions | Class | Finding |
|---|---|---|---|
| Sidebar/header | All role routes, mobile menu, notification link, Logout | [C], Logout [A] | Routes load; logout is POST/CSRF. |
| Dashboard cards | Availability, Owners, Documents, Leave, Safety, Trips, Incident | [C] | Valid role destinations; the waving-hand emoji is decorative only. |
| Availability | Update Status | [A] | Real Driver-owned update with CSRF. |
| Documents | Upload Version, View, Delete | [A]/[C] | Real upload/delete paths with role/ownership checks. |
| Owners/notifications | Accept/Reject connection, phone/email links, Mark Read | [A]/[C] | Database-backed relationship and notification operations. |
| Leave | Request, Edit, Cancel/Delete | [A] | Verified Driver Leave CRUD with POST/CSRF and ownership checks. |
| Safety checks | Create/Edit/Delete | [A] | Database-backed Driver-assignment checks. |
| Trips | Update pickup status | [A] | Real assigned-trip status action. |
| Incidents | Submit Incident Report | [A] | Real assigned-booking incident insertion. |
| Profile/security | Submit profile change, Deactivate, credential-change request, Update Password | [A] | Real server handlers; password path retains password verification/hashing. |
| Messages | Initialize Chat, Open Chat, Send | [A] with partial UX | Booking assignment is checked server-side and room participation is required. UI text incorrectly claims direct chat with Owners/Admin although only assigned-booking Customer enrollment is implemented here. |
| Invalid/tampered chat room | Direct `chat.php?room_id=...` denial | [B] | Unauthorized room IDs are denied, so transcript privacy holds. The page uses `die("Error loading chat...")` instead of the shared shell-safe empty/error state. |

No visible Driver [D] or [E] action was found in normal navigation. The Messages participant claim must be corrected or the missing paths clearly marked before it is demonstrated.

## 8. Admin action inventory

| Area | Visible actions | Class | Finding |
|---|---|---|---|
| Sidebar | Dashboard, Users, Owners, Drivers, Vehicles, Bookings, Payments, Incidents, Replacements, Settlements, Reports, Email Logs, Announcements, Logout | [C], Logout [B] | Routes load. Admin logout uses the shared GET logout link, unlike Customer/Owner/Driver POST logout. |
| Header notification bell | Bell with “11 pending items” toast | **[D]** | Count is hard-coded and no notification data/page is opened; misleading operational feedback. |
| Dashboard quick/queue links | Review Users/Vehicles/Payments, Incidents, Add Announcement | [C] | Valid destinations. |
| Dashboard “Review approvals” | Users destination | [B] | Users is now Platform User CRUD, not the former dedicated Customer-verification queue. Status can be edited, but the label/landing context no longer matches exactly. |
| Registered Users statistic | `users.php?view=registered` | [B] | Page loads, but Platform Users does not implement this segmented filter; query is ignored. Owner/Driver/Vehicle registered views do implement the view switch. |
| Users | Create, Edit, Update, Delete | [A]/[C] | Verified Admin User CRUD; current Admin self-delete/access removal is blocked. |
| Owner/Driver/Vehicle verification | Pending/Registered tabs, Approve, Reject | [A]/[C] | Real POST/CSRF verification handlers. |
| Payments | Pending/History tabs, Approve, Reject | [A]/[C] | Real POST/CSRF verification. |
| Incidents | Update status/decision | [A] | Real POST/CSRF handler. |
| Replacements | Assign & Approve | [A] | Real handler and DB operation. |
| Settlements | Mark Paid | [A] | Real POST/CSRF handler. |
| Announcements | Publish/Create, Delete | [A] | Real DB-backed action handlers. |
| Bookings, Reports, Email Logs | filters/read-only tables | [C] | Real database reads; no visible export/retry/send control is wired. |

The shared Admin modal/JavaScript bundle contains dormant fake-success functions for approvals, email send/retry, announcement changes, report download with fabricated rows, and status changes. No audited current page exposes triggers for those functions, so they are not counted as visible [E] actions. They must not be wired into visible controls unless connected to real handlers or explicitly converted to safe final-release notices.

No visible Admin [E] action was found. The notification bell remains [D].

## 9. Visible [D]/[E] exception register

| Role | Control | Current class | Required interim treatment after approval |
|---|---|---|---|
| Customer | Complete verification on incident demo | [D] | Native-disable with persistent final-release text, or link to the real verification page. |
| Customer | Add incident photos | [D] | Native-disable and label final release; do not pretend upload success. |
| Customer | Add Google Maps link | [D] | Native-disable and label final release. |
| Customer | Submit incident report on demo index | [D] | Route to real `incidents/create.php` when eligible, or native-disable. |
| Customer | Yes, request replacement | [D] | Native-disable and label final release; do not fabricate a replacement. |
| Customer | No, request refund | [D] | Native-disable and label final release; do not fabricate a refund. |
| Owner | Header notification bell | [D] | Remove, route to a real notification view, or show a truthful final-release state. |
| Admin | Header notification bell/fixed 11-item toast | [D] | Remove fixed count; route to real queues or show a truthful informational state. |
| All roles | Error-producing visible actions | **No [E] observed** | Preserve this condition. Recheck after any UI centralization. |

## 10. Navigation audit

| Role | Current result | Exceptions/notes |
|---|---|---|
| Guest | PASS | Footer labels are semantically broader than their shared anchor destinations. |
| Customer | PASS | All major sidebar routes load. Incidents lands on a demo-only page despite real create/details routes existing separately. |
| Owner | PASS | All major sidebar routes load; absolute `/owner/...` paths assume the current `public` document root. |
| Driver | PASS | All major sidebar routes load. `messages.php` is the correct list entry; `chat.php` is a detail route. |
| Admin | PASS | All major sidebar routes load. Users dashboard filter/query labels need alignment with the current User CRUD page. |

Evidence baseline: the completed runtime QA exercised 54 major routes (Guest 4, Customer 13, Owner 9, Driver 15, Admin 13) with zero failures and no raw PHP/SQL error marker. This audit additionally traced the visible conditional buttons/forms to their current handlers without executing mutations.

## 11. Chat/messaging architecture audit

### Current database capability

The canonical schema has:

- `chat_rooms(id, booking_id, created_at)`;
- `chat_participants(room_id, user_id, joined_at)` with a unique room/user key;
- `chat_messages(room_id, sender_id, message_text, is_read, sent_at)`;
- foreign keys to bookings and users.

This is a sound generic participant foundation. It can hold more than two users in a booking room and can technically hold an explicitly added Admin because participants reference `users`. It does not currently record conversation type, support/escalation state, who added a participant, why Admin was added, or a support/dispute reference.

### Current role support

| Capability | Status | Evidence |
|---|---|---|
| Customer booking chat | **FUNCTIONAL** | Customer ownership/status is resolved server-side; room selection also requires Customer membership; send is POST/CSRF. |
| Driver booking chat | **FUNCTIONAL** | Driver must be assigned to the booking to create a room; read/send require `chat_participants` membership. |
| Owner booking chat | **FUNCTIONAL with design caveat** | Owner rooms are derived from vehicles owned by the session Owner; read/send require membership. Enrollment currently occurs automatically when Chat is opened. |
| Customer + Owner + assigned Driver in one room | **PARTIAL** | Schema supports three participants. Customer creation chooses either Driver or Owner using `COALESCE`; Driver creation adds Driver + Customer; Owner is added later only when the Owner opens Chat. Participant completeness is therefore timing-dependent. |
| Correct multi-party participant display | **PARTIAL** | Shared room query and Driver participant query use `LIMIT 1` for the “other participant,” so a three-person room displays only one arbitrary counterpart rather than all participants. |
| Owner ↔ Driver direct conversation outside a booking | **MISSING** | No UI/controller/model creates a direct Owner–Driver room. `booking_id` is nullable in schema, but no conversation type or direct-room authorization workflow exists. |
| Admin support conversation | **MISSING** | No Admin Chat navigation/page/controller and no support-request/enrollment action. |
| Admin silent access to all private chats | **NOT PRESENT — privacy-positive** | No Admin transcript query exists. Existing room lists are participant-scoped. |
| Explicit Admin escalation | **MISSING** | No escalation flag/table/action/audit trail or intentional Admin-participant workflow. |
| Unread state | **PARTIAL** | Owner marks non-owner messages read; Customer and Driver paths do not consistently mark messages read, despite unread counts being displayed. |
| Shared chat visual component | **MISSING** | Customer, Owner, and Driver implement separate markup/layouts; Admin has none. |

### Current participant formation

```text
Customer starts room
  -> server verifies Customer owns eligible booking
  -> adds Customer + assigned Driver, otherwise Customer + Owner

Driver starts room
  -> server verifies booking is assigned to Driver
  -> adds Driver + Customer

Owner opens Chat
  -> server derives every booking for Owner vehicles
  -> creates/enrols Owner in those rooms during GET
```

The intended stable result—Customer + Owner + assigned Driver—is possible in the table, but is not guaranteed until the Owner visits Chat.

### Chat authorization assessment

The main IDOR requirement is substantially satisfied:

- Customer does not trust `booking_id` alone: `ensureChatRoom()` joins the session Customer to the booking and validates eligible status.
- Customer room read/send requires both the session Customer's owned booking and a matching `chat_participants` row.
- Driver room creation validates `booking_id` against the authenticated Driver profile.
- Driver read/send validates `room_id` membership.
- Owner room selection and sending validate authenticated Owner membership; enrollment candidates are derived server-side from Owner-owned vehicles.
- Prepared statements are used for room/message access.

Remaining hardening/UX items for a later reviewed implementation:

- centralize authorization in one conversation service so all roles use the same membership rule;
- return a shared 403/404 chat empty state instead of Driver `die(...)` output;
- do not mutate Owner room membership on GET;
- render all participants and verify the room still belongs to the expected booking context;
- when Admin support is added, require an explicit server-side escalation/support record before adding Admin to `chat_participants`;
- never add an Admin-only “all conversations” transcript query.

### Admin privacy rule fit

Current status: **PARTIAL FOUNDATION, SAFE DEFAULT**.

The generic participant table supports the preferred privacy rule if Admin is intentionally added to a specific room. Current code does not give Admin blanket access, which should be preserved. It cannot yet prove why or by whom Admin was added, so final support/escalation participation needs an explicit auditable workflow before implementation.

### One shared chat UI proposal

Use one markup/component contract for Customer Chat, Owner Chat, Driver Messages, and future Admin Support Chat:

```text
+--------------------------+-------------------------------------------+
| Conversations            | Booking #123 · Toyota Aqua · CAB-1234   |
| Search/filter             | Customer · Owner · Assigned Driver       |
|                          |-------------------------------------------|
| Booking #123             | Customer: Pickup time confirmed.         |
| Toyota Aqua · 2 unread   |                          Owner: Thank you. |
|                          | Driver: I am approaching the location.    |
| Booking #118             |                                           |
| Suzuki Wagon R           |-------------------------------------------|
|                          | Message...                         [Send]  |
+--------------------------+-------------------------------------------+
```

Shared regions:

- conversation list;
- active booking/rental context;
- complete participant list;
- message history;
- composer and safe disabled state;
- consistent empty, loading, access-denied, closed-rental, and support-escalated states.

Role-specific differences should be limited to which rooms are returned, whether sending is permitted, and whether an explicit “Request support”/“Join support case” action is authorized.

## 12. AI-looking/decorative cleanup register

These are refinement candidates only; operational meaning must be preserved.

| Location | Finding | Recommendation after review |
|---|---|---|
| Driver dashboard | Waving-hand emoji in welcome heading | Remove emoji; retain personalized greeting. |
| Owner header | Bell emoji inside a nonfunctional button | Remove/replace with the shared real notification control. |
| Owner vehicle list | Vehicle-type emoji (`🚐`, `🚙`, `🚛`, `🏍️`) | Use the existing neutral vehicle icon vocabulary or plain type text. |
| Owner Chat empty state | Large decorative chat emoji | Replace with the shared neutral empty-state icon treatment. |
| Admin dashboard | “Do this next” SaaS-style callout | Convert to a normal operational queue summary or standard card. |
| Admin dashboard/header | Fixed notification count and hard-coded “All systems operational”/service-status claims | Remove unless calculated from real health/queue data. |
| Customer incident demo | Fixed settlement amounts, workflow progress, role/status pills | Remove from interim navigation or clearly contain as unavailable final-release preview; never present as live data. |
| All portals | Multiple logo marks (`L`, `LR`, text-only), spellings, role chips/pills | Select one wordmark/mark and show the role once in a consistent account context. |
| Admin/Owner/Customer surfaces | Many colored icon bubbles and repeated badges | Keep colors only for meaningful status/severity; use neutral icons elsewhere. |
| Admin tables/dashboard | High badge density | Keep approval/payment/incident status badges; change descriptive/non-status labels to plain text. |

## 13. Recommended implementation sequence after audit approval

No work in this sequence is implemented by this audit.

1. **Interim button safety:** resolve the eight [D] controls using removal, native disabled states with clear final-release text, or real existing destinations. Do not add backend systems.
2. **Truthful labels/data:** remove fixed Admin notification/health claims, fabricated incident settlement data, and chat claims for unsupported Owner/Admin direct conversations.
3. **Navigation alignment:** point Customer Incidents to the real safe database-backed state or a truthful unavailable state; align Admin approval/filter links; clarify Guest footer anchors.
4. **Shared visual tokens:** centralize logo, font stack, colors, spacing, radii, shadows, and responsive shell dimensions while retaining current appearance.
5. **Shared primitives:** consolidate buttons, controls, alerts, cards, tables, badges, empty states, and modals incrementally without rewriting page logic.
6. **Shared chat view only:** first unify chat markup/layout across the three existing roles without changing privacy/business rules.
7. **Final-project chat rules later:** only after approval, add stable three-party enrollment, direct Owner–Driver rules, and explicit audited Admin support escalation.
8. **Regression QA:** re-run authentication, the four assigned CRUD cycles, all visible navigation, CSRF/ownership checks, PHP lint, and raw-error scans.

## 14. Interim acceptance gates before presentation

- [ ] No visible [E] action.
- [ ] Every [D] action removed, natively disabled with clear final-release wording, or routed to a truthful informational state.
- [ ] Fixed/fabricated operational indicators are not presented as live data.
- [ ] All major Guest/Customer/Owner/Driver/Admin navigation remains error-free.
- [ ] Authentication and the four verified CRUDs remain unchanged and passing.
- [ ] Chat UI accurately describes only the participants/workflows currently supported.
- [ ] Admin retains no silent blanket access to private conversations.
- [ ] UI centralization preserves the approved branding/theme and does not introduce a new design system.

## 15. Audit stop statement

No PHP, controller, model, view, CSS, JavaScript, database schema, or Git state was modified. This document records findings and proposals only. Implementation must wait for audit review.
