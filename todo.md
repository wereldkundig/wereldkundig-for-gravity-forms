# Todo

## Remember a failed fetch of the subscribe fields

When the fields cache (`wk_gf_api_fields`) is missing or belongs to other credentials, `Client::fields_setup()` calls `GET /v1/subscribe/fields`. A failed call is only remembered for the rest of the request, so while the platform is down every new request tries again, each with a 10 second timeout. Form submissions (`Submission` → `Client::theme_exists()`) are the ones that still fetch, so a down platform slows down every subscribe: in the background for feeds, but during the request for forms without a feed. The hourly background refresh keeps the cache filled, so this mainly matters when nothing was ever cached for the current credentials.

- Remember a failed fetch for a few minutes, e.g. in a transient keyed by `cache_key()`.
- During that time `fields_setup()` returns empty without calling the API; saving the Wereldkundig settings clears it and fetches again.
- Log the failure once, not on every request.

The form editor already never calls the API, see "Themes and subscriber fields cache" in [README.md](README.md).

## Resubscribes and confirmation (platform options we don't send)

`POST /v1/subscribe` accepts two options the add-on doesn't send yet (see the platform's `Wereldkundig\API\SubscribeController`):

- `upsert` (default `false`): an email that is already a member of the platform site is **not** updated; the platform answers `409 Member Exists` and nothing is saved, not even the new themes. The entry gets the feed error `… is already a list member. Set upsert to true … (code: member-exists)`. Sending `upsert: true` would add the new themes (and update the names) instead.
- `confirmed`: without it the platform sends its own confirmation request (double opt-in). `true` skips that.

Both are product decisions: decide, then send them from `Client::subscribe()` (possibly as add-on or feed settings).
