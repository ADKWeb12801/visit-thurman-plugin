# Visit Thurman Listings & Community Hub

This plugin registers several custom post types (Events, Businesses, Accommodations, TCA Members) and provides shortcodes for displaying them in Breakdance.

## Shortcodes
- `[vt_events]`
- `[vt_businesses]`
- `[vt_accommodations]`
- `[vt_tca_members]`
- `[vt_next_events]`
- `[vt_upcoming_events]`
- `[vt_user_profile]`
- `[vt_user_dashboard]`
- `[vt_claim_listing]`
- `[vt_bookmark_button]`

All listing shortcodes accept the following attributes:

```
limit    - number of posts to show (default 12)
columns  - grid columns (default 3)
orderby  - field to order by (default 'date')
order    - ASC or DESC (default DESC)
category - filter by category slug
```

Example:

```
[vt_events limit="5" orderby="meta_value" meta_key="_vt_start_date" order="ASC"]
```

## Claim Listings
Use `[vt_claim_listing post_id="123"]` inside a single listing to display a button allowing logged-in users to request ownership.

User dashboards show existing claim requests via `[vt_user_dashboard]`.

