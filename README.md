## 🧹 Static Cache Cleaner for Statamic

This command (`static-cache:clean`) removes **orphaned static cache files** that remain on disk after Redis has forgotten about them — a common edge case in high-traffic Statamic sites using [full static caching](https://statamic.dev/static-caching#file-driver).

### 🧨 Why this is needed

Statamic’s `FileCacher` stores all cached URLs for a site in a single array, saved to Redis under a single key (`statamic:static-cache:urls:<domain>`). When multiple requests (e.g. page renders, cache warmers, or invalidations) happen concurrently, they may:

- **Read the current cache index**
- **Modify it (add/remove one URL)**
- **Write the entire array back to Redis**

Because this process is **not atomic**, changes from one process can silently overwrite others. This race condition causes certain pages to **disappear from Redis**, even though their static `.html` files remain on disk.

### 🔒 What about Statamic’s built-in locks?

Statamic’s static cache middleware *does* apply a **per-URL file lock** (via Symfony's `LockFactory`) during a web request. This prevents the **same URL** from being rendered by multiple requests at the same time.

However, it does **not** protect the shared Redis key that holds the list of all cached URLs. This Redis write is global and unguarded, which means:

- Concurrent requests to different URLs (`/about`, `/products`) are **not locked against each other**.
- Console commands (e.g. `static:warm`, `invalidateUrl`, or queue jobs) **bypass middleware entirely**, skipping locks.
- Any of these can cause unintentional overwrites of Redis state.

> 🔁 Statamic has **mutex guard rails per page**, but not for the **global Redis index**.

### ❗ The risk

- Redis says a page is **not cached**.
- But the static `.html` file still exists.
- The web server continues serving the stale page, **bypassing Statamic completely**.

This issue is most common during:

- Bulk imports that trigger repeated invalidations (e.g. `EntrySaved` events)
- Cache warming across many URLs in parallel
- Sites using query string variations (`ignore_query_strings => false`)

### ✅ What this command does

This command:

1. Uses Statamic’s public APIs (`getUrls()`, `getFilePath()`, etc.) to collect all known cache entries from Redis.
2. Walks the static cache directories to find all existing `.html` files.
3. Deletes any file that **is no longer referenced in Redis**.
4. Optionally removes empty parent directories — with safeguards to **never delete above the static cache root**.

### 💡 Benefits

- Keeps your static cache clean and trustworthy
- Ensures Redis remains the **source of truth**
- Fixes stale page issues without requiring upstream changes
- Safe to run on production — includes `--dry-run` mode
