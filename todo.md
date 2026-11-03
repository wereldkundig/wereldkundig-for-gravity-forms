# Todo

## Resubscribes and confirmation (platform options we don't send)

`POST /v1/subscribe` accepts two options the add-on doesn't send yet (see the platform's `Wereldkundig\API\SubscribeController`):

- `upsert` (default `false`): an email that is already a member of the platform site is **not** updated; the platform answers `409 Member Exists` and nothing is saved, not even the new themes. The entry gets the feed error `… is already a list member. Set upsert to true … (code: member-exists)`. Sending `upsert: true` would add the new themes (and update the names) instead.
- `confirmed`: without it the platform sends its own confirmation request (double opt-in). `true` skips that.

Both are product decisions: decide, then send them from `Client::subscribe()` (possibly as add-on or feed settings).
