# 22 · Dashboard search: full spec (17 Nov 2026)

Files in this package for this feature:
- `22-DASHBOARD-SEARCH.md` (this spec)
- `22-search-index.xlsx` (the index, two sheets: Artist dashboard 98 rows, Studio dashboard 32 rows)
- `html/assets/account-state.js` (the mockup code, second block in the file)
- Gallery card "Dashboard search" on the Home row of `html/index.html`. Open any artist page and use the box at the top of the sidebar.

---

## 1. What it is and why

A search box at the top of the artist dashboard sidebar. An artist types what they want in their own words and jumps to the right page, tab or setting, or finds a client or booking. If nothing matches, they can ask us.

Why: the dashboard has about 35 pages and tabs and about 40 settings. Artists look for things like "day off", "price" or "waiver", which are not the names we use (Blocked dates, Deposits & rates, Consent form). Search closes that gap without re-arranging the menu.

Out of scope: changing the menu, search on public pages, search inside Inbox messages.

The studio dashboard has the same search (section 14). It is a separate index. A studio never sees artist pages and an artist never sees studio pages.

## 2. Where it appears

- Every artist dashboard page, at the top of the sidebar, under the logo and the "TATTOO ARTIST PLATFORM BY INKJIN" tag.
- Not on: onboarding steps (artist O1 to O7, studio SO1 to SO5), sign-in pages, client pages, the public studio page, full-page pop-ups.
- Phone and tablet: the box sits at the top of the menu that opens with the burger button. Results open as a full-width panel under the box.

## 3. The box

| Item | Rule |
| --- | --- |
| Placeholder | "Search or jump to…" |
| Left icon | Search (magnifier) |
| Right hint | None. The "/" shortcut works but is not shown in the box, so the placeholder fits |
| Shortcut | Press "/" anywhere on the page (when not typing in a field) to focus the box |
| Clear | The browser's clear (x) button in the box. Esc also closes the panel |
| Width | The sidebar width. The results panel is wider (440 px on desktop, full width on phone) and opens over the page |

## 4. What the artist sees

### 4.1 Empty box (focused, nothing typed)
- **Recent**: the last 4 results the artist opened (name and where it sits). Shown only if there are any.
- **Try**: 3 fixed examples: "Day off", "Deposit", "Consent form".

### 4.2 Typing
Results in three groups, best match first within each group. A group with no results is not shown.

| Group | Max rows | Contents |
| --- | --- | --- |
| Settings and pages | 5 | Pages, tabs, settings and actions from the index |
| Clients and bookings | 4 | The artist's own clients, bookings and requests |
| Help | 2 | Help articles from the index |

Each row: icon, name in bold, and the "where" line under it (e.g. "Schedule > Availability"). Actions show a "Do it" tag. They only open the page and highlight the button or setting. Search never performs the action.

The last row is always **"Ask us about '<typed text>'"** ("Opens Get Help with your words filled in").

### 4.3 No match
The panel says **Nothing found for "<text>"**, "Ask us and we will point you to it.", then the "Ask us" row.

### 4.4 Choosing a result
- Arrow up and down move. The first row is selected by default. Enter opens. Click opens. Esc closes.
- A result opens the page (and tab) and scrolls to the anchor. The target is outlined for about 2.6 seconds, then the outline fades.
- If the artist is already on that page, no reload. Scroll and highlight only.
- If the anchor is not found, the page opens at the top. No error.
- The box is cleared after choosing.

## 5. The index

One list, one file, kept in the code next to the routes. See `22-search-index.xlsx` for the full list (98 rows). Each row:

| Field | Meaning |
| --- | --- |
| Type | Page, Setting, Action, Help, or Live |
| Name | What the artist sees in bold |
| Where | The line under it, e.g. "Money > Deposits & rates" |
| Route | The page URL. The sheet has the mockup file name. The developer fills in the real route |
| Anchor ID | The element ID to scroll to and highlight. Empty for whole pages |
| Artist words | Space-separated words and phrases artists use. We edit these, no code change |
| Shown when | Condition. Empty means always |

**Shown when** values used in the sheet: Account active (hide when deactivated), Account deactivated (show only then), Artist works at a studio, Artist works at 2 studios, Studio not on Bookpay, Stripe not connected, Pro or trial. Entries whose condition is false for this artist are left out of results.

**Anchor IDs.** Each setting needs a stable element ID on its page, for example `id="blocked-dates"`. The index points to the ID, never to heading text, so renaming a heading cannot break search. The IDs in the sheet are suggestions.

**Keeping it correct.** A release check fails the build if a page, tab or setting in the app has no row in the index. Each month, read the search log (section 8). "Nothing found" words become new artist words or new help articles.

## 6. Matching (for the entries in the index)

Runs on the device, no wait.

1. Lower-case the typed text. Keep only letters, numbers, spaces and the euro sign. Collapse spaces.
2. For each entry, score it against the typed text:
   - Name equals the text: 100.
   - Name starts with the text: 60.
   - Name contains the text: 40.
   - For each typed word: in the name +12; at the start of an artist word +9; inside an artist word (word longer than 3 letters) +5; in the "where" line +2.
   - If any typed word matches nothing, the entry scores 0 and is left out.
3. Sort by score, highest first. Take the top 5.

Examples that must work:

| Typed | First result |
| --- | --- |
| holiday, vacation, day off, time off | Blocked dates |
| price, prices, rates | Deposits & rates |
| deposit | Deposit (Money > Deposits & rates) |
| waiver, release | Consent form |
| paid, stripe, bank | Payouts |
| turn off, pause, close account | Deactivate account |
| books closed, stop taking bookings | Booking status |
| refund | Help: How do I refund a client? |

## 7. Clients, bookings and requests (live search)

- Searched on the server for this artist only. Never another artist's clients, never the studio's.
- Starts at 2 typed characters, with a short delay (about 250 ms after the last key).
- Fields searched: client name, client email, booking number (INK-000041-PL), request number (INK-000018-CR), design name.
- Row: name or number in bold, with a line like "Booking · Sep 30, 2026" or "Client". Opens the Clients, Bookings or Requests page and highlights the row.
- Studio artists: only the artist's own clients and bookings, as on the rest of the dashboard. With no split, nothing from the studio.
- Deactivated account: still works.
- Limit 4 rows. If more exist, the last row can say "See all in Clients" (optional, not in the mockup).

## 8. "Ask us" and logging

**Ask us.** Opens the Get Help chat with the typed text already in the message box. If the chat cannot be pre-filled, open it empty. In the mockup the chat is a stub, so the text is not shown.

**Log.** One event per search, written when the artist opens a result, presses Enter, or stops typing for 2 seconds:

| Field | Value |
| --- | --- |
| artist_id | the artist |
| text | what was typed |
| outcome | `opened`, `nothing_found`, `asked_us` |
| result_name, result_type | when `opened` |
| group | settings, people or help |
| at | timestamp |

Rules: for clients and bookings results, do not store the typed text (it can be a client's name or email). Store only `outcome` and `group`. Keep the log for 12 months.

**Monthly review.** List the top "nothing_found" and "asked_us" texts. For each, add artist words to an entry, add a help article, or note a missing feature.

## 9. Recent

- The last 4 results the artist opened. Stored per artist (server or device storage, developer's choice). Deduplicated by name, newest first.
- Not stored for clients and bookings results.

## 10. States and edge cases

| Case | Behavior |
| --- | --- |
| Account deactivated | Search works. "New payment link" and any entry marked "Account active" is left out. "Reactivate account" is shown |
| Artist has no studio | "Studio" page still shown. Entries marked "Artist works at a studio" are left out |
| Stripe not connected | "Finish payouts banner" entry shown. Payouts still shown |
| Typed text only spaces | Treated as empty |
| Very long text | Cut at 100 characters |
| Typed "/" while in a field | Types the character, does not move focus |
| Screen reader | Box has the label "Search the dashboard". Panel has role listbox. The selected row is announced |
| Slow or failed live search | Settings and pages still show. The clients group is left out. No error shown |

## 11. Developer tasks

1. Create the index file from `22-search-index.xlsx` (one entry per row). Replace "Mockup file" with real routes.
2. Add the element ID from "Anchor ID" to each setting on its page.
3. Build the box, panel, keyboard handling and highlight (mockup code is a reference, about 150 lines).
4. Build the live search endpoint for clients, bookings and requests (this artist only).
5. Pre-fill Get Help with the typed text.
6. Add the search log and a simple monthly report.
7. Add the release check: every route and tab has an index row, every anchor ID exists on its page.
8. Add the "Shown when" conditions to the result filter.

## 12. Acceptance tests

1. Typing each example in section 6 returns the expected first result.
2. Choosing "Blocked dates" opens Schedule > Availability and outlines the Blocked dates card.
3. On the same page, choosing a result scrolls and highlights without a reload.
4. Typing "zzzz" shows "Nothing found" and only the "Ask us" row. Choosing it opens Get Help with "zzzz" in the message.
5. With the box focused and empty, Recent and Try show. After opening a result, it appears in Recent next time.
6. "/" focuses the box. Esc closes the panel. Arrow keys and Enter work.
7. A deactivated artist does not see "New payment link" in results and does see "Reactivate account".
8. Searching a client name returns only this artist's clients.
9. Search events are logged with the right outcome and no client text.
10. Remove an index row for a real page: the release check fails.

## 13. Mockup limits

- The index in the mockup is a fixed list in the script, with the same entries as the sheet (the sheet is the full, reviewed list).
- Clients and bookings use only the 4 sample clients and 4 sample bookings, so "anna" finds nothing.
- Help rows are examples. "Ask us" clicks the Get Help button but the chat stub does not show the text.
- Recent uses the browser's local storage. Logging is kept in session storage only.
- Pop-up pages have no sidebar and no search box.
- Anchors in the mockup are matched by heading text. The product uses element IDs.

## 14. Studio dashboard

The same box, panel, keyboard, matching, logging and Ask us as sections 3 to 10. Only the index, the live search and the visibility rules differ.

**Where.** Top of the studio sidebar, under the logo and the "STUDIO DASHBOARD BY INKJIN" tag, on every studio dashboard page (S01 to S08f). Not on studio onboarding (SO1 to SO5) or the public studio page.

**Index.** Sheet "Studio dashboard" in `22-search-index.xlsx` (32 rows): Home, Artists (with Join requests, Invites, Guest artists), Calendar, Bookings (with Requests, Payment links), Money (Earnings, Payouts), Studio Page (About, Artists on my page, Appearance, Portfolio), Client Flow (Consent form, Intake form), Account (Profile, Password, Studio details, Regional, Plan, Notifications). Actions: Invite an artist, New booking. Setting: Revenue split and artist pay.

**Studio words.** Examples that must work:

| Typed | First result |
| --- | --- |
| add artist, team, staff | Invite an artist |
| split, percent, studio share | Revenue split and artist pay |
| walk in, qr code, payment link | New booking |
| any available artist, assign | Requests |
| guest, visiting | Guest artists |
| stripe, bank, payout | Payouts |
| waiver, release | Consent form |

**Live search (this studio only).**

| Searches | Fields | Opens |
| --- | --- | --- |
| Artists | name, username | Artists (row highlighted). Guest artists open Guest artists |
| Bookings | client name, booking number, design, artist | Bookings |
| Requests | client name, request number | Requests |

**No-split rule (important).** For an artist who rents a workstation or is a guest artist on direct payment (no split):
- The artist is found by name and opens the Artists list. That is all.
- Their clients, bookings and requests are never searchable. Searching a name that only appears in their bookings finds nothing.
- Their bookings never count anywhere in results.

**Other rules.**
- A studio sees only its own artists, bookings and requests. Never another studio's.
- With the Plan switched off, the "Plan" entry is left out.
- "Invite an artist", "New booking" and "Revenue split" only open the page and highlight. Search does not do the action.

**Acceptance tests (studio).**
1. "split" returns Revenue split and artist pay first.
2. "invite" opens the Invites tab. "add artist" returns the Invite an artist action.
3. A name from a no-split artist's booking returns nothing. The artist's own name returns the Artists list.
4. A studio login never returns an artist dashboard entry. An artist login never returns a studio entry.
5. Recent for the studio is separate from Recent for an artist.

**Mockup note.** In the mockup the studio rows for artists and bookings are the sample ones (Julian Ink, Nikos Pappas, Maria Kosta, three guest artists, five bookings and requests). The product uses the live search instead.
