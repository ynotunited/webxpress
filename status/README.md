# Webxpress Status

A dependency-free public status page. One HTML file, one JSON data file, nothing
else — no framework, no build step, no CDN, no analytics. It has to keep working,
so it depends on as little as possible.

## Why it is not in the main app

If the status page were served from the same server as the site, it would go
down when the site goes down — which is precisely when people visit it. It is
therefore a separate **Cloudflare Pages** deployment on its own domain, built
from this `status/` directory:

- Different infrastructure entirely (Cloudflare's edge, not your web server), so
  it stays up when the origin does not.
- Cloudflare Pages has a free plan and pulls directly from this repository, so
  the single source of truth is a folder you already commit to.
- Even a bad deploy here can be rolled back by re-pushing the previous commit.

The page reads one file — `status/data/status.json` — and nothing else. Only the
`status:publish` command writes that file, so there is exactly one way state
gets onto the page.

## Deploying it

1. Push this repository to GitHub.
2. Cloudflare Pages → Create project → connect the repo.
3. Build command: *none*. Output directory: `status`.
4. Add a custom domain (`status.webxpress...` or whatever the parent domain is).
5. First deploy, then go to `status/data/status.json` → *Preview* → the JSON —
   confirm the `name`, `homepage` and initial `overall` value are what you want.

Every update is a git push. There is no "publish button" — the command below
writes the file, you commit and push, Pages redeploys in seconds.

## Monitoring that feeds it

The page tells the public what you know. Something has to tell *you*. A free
external monitor watches from independent infrastructure — the rule is the same
as the page: the thing that watches must not live on the thing it watches.

Free options, in order of what fits the setup you already have:

| Service | Free tier | Notes |
|---|---|---|
| **UptimeRobot** | 50 monitors, 5-minute checks | Simplest; the standard pairing with a Cloudflare Pages status page. Supports HTTP, port, and keyword checks. |
| **Better Stack** | ~10 monitors + 1 status page | Also hosts a status page itself if you ever want the managed variant. |
| **Uptime Kuma** | Unlimited, but self-hosted | Needs a separate box on a different provider — only worth it once you already have one. |

Recommended: **UptimeRobot** for the checks now. Add monitors for `https://webxpresstech.com.ng`, the admin login URL, and the transactional API. When a monitor fires, open an incident (below) — the monitor does not write `status.json`; people do, because a machine that auto-publishes "down" has the same failure mode as the page it describes and no judgement about what a blip means.

## The workflow this repo implements

### Scheduled maintenance (S2)

1. Decide the window: title, exact `starts`, `ends`. Say "no service impact
   expected" if that is true; do not say it if you are not sure.
2. Announce it, ideally the day before at minimum — *before* the window:
   ```sh
   php artisan status:publish maintenance \
       --title="Server maintenance" \
       --starts="2026-10-10 21:00" \
       --ends="2026-10-11 03:00" \
       --message="Updating host software. Brief HTTP blips possible; no data loss."
   ```
3. Commit and push (`status/data`), which publishes the notice. Tell the
   relevant clients directly if the window affects them — the status page is
   the record, not the reminder.
4. Reschedule if needed (same command with `--id=`), or cancel:
   ```sh
   php artisan status:publish maintenance --id=mnt-2026-10-10-server-maintenance --cancel
   ```
5. After the window, announce completion by issuing a `monitor` update or by
   resolving/updating status — the maintenance record stays in the file as
   history.

### Incidents (S3)

The rule that gives the page its credibility: **a real incident gets announced
early, updated honestly, and resolved with a post-mortem when it matters. The
worst thing you can do is wait until you are sure about the cause before saying
anything.** A status page that goes dark during an outage is worse than none.

Severity and what it means:

| Severity | Meaning | Page says |
|---|---|---|
| `critical` | Checkout/payments/portal unavailable. Business-stopping. | **Service disruption** |
| `major` | A core site feature degraded or unavailable. | **Degraded performance** |
| `minor` | Cosmetic, slow-loading, or affects few users. | **Degraded performance** |

Lifecycle:

1. **Open** the moment you know the service is not right — do not wait for root
   cause:
   ```sh
   php artisan status:publish incident \
       --title="Checkout is returning errors" \
       --severity=major \
       --state=investigating \
       --message="Payment pages are erroring. We are investigating."
   ```
   The page's *overall* status flips automatically from the severity.

2. **Update** as you learn things. Each update is one honest sentence:
   ```sh
   php artisan status:publish incident --id=inc-2026-10-07-checkout-is-returning-8xk2 \
       --state=identified \
       --message="Root cause: provider webhook timeout increased. Fixing."
   php artisan status:publish incident --id=inc-2026-10-07-checkout-is-returning-8xk2 \
       --state=monitoring \
       --message="Fix deployed and verified against new transactions."
   ```
   The timeline on the page grows in order, so what you publish is the story.

3. **Resolve**:
   ```sh
   php artisan status:publish incident --id=inc-2026-10-07-checkout-is-returning-8xk2 \
       --state=resolved \
       --message="All payments processing normally again. Impact: ~40 minutes."
   ```

4. **Post-mortem**: if the incident was major or involved payments, write the
   five lines that make it worth anything — what happened, why it happened, what
   stopped it being worse, what changes prevent recurrence, who was affected —
   file it in the repo `docs/` next to the deploy history. The page shows the
   resolution; the post-mortem is for the next time.

5. **Commit and push after every step.** Nothing is public until `status/data`
   is pushed. Fold this into your deploy habit: the status update is a commit,
   and the incident is not communicated until it is.

### The one thing the page can't do

This whole arrangement — separate host, single JSON file, command-driven
updates — exists so the *page* survives. It does not make anyone perform the
steps. A monitoring alert that nobody converts into an incident leaves the page
green and quiet while real customers stare at an outage, which is the failure
mode to guard against. The `status:publish monitor` command prints the reminder
for exactly this reason when a monitor flips.

## Files

```
status/
├── index.html          the page (self-contained, no external requests)
├── data/status.json    the entire public state — the only mutable file
└── README.md           you are here
```

## Secrecy

`status/data/status.json` is public by design, but keep it free of credentials,
hostnames behind auth, and incident detail that outlives its usefulness. Say
what broke and how long — not where your servers are.