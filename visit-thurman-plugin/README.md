# Visit Thurman Plugin

Shortcodes included in this plugin:

- `[vt_next_events]`
- `[vt_upcoming_events]`
- `[vt_user_profile]`
- `[vt_user_dashboard]`
- `[vt_claim_listing]`
- `[vt_bookmark_button]`
- `[vt_share_buttons]`

---

## All shortcodes accept the following attributes:

- `limit` – number of posts to show (default: 12)  
- `columns` – grid columns (default: 3)  
- `orderby` – field to order by (default: `date`)  
- `order` – `ASC` or `DESC` (default: `DESC`)  
- `category` – filter by category slug  
- `tag` – filter by tag slug  
- `meta_key` – custom field for ordering  
- `search` – search keyword  
- `ajax` – enable ajax filters (`true` or `false`)

---

## Example:

```php
[vt_events limit="5" orderby="meta_value" meta_key="_vt_start_date" order="ASC"]
