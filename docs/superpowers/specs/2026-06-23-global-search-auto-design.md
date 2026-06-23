# Global Search — Auto-search + UX Improvements

**Date:** 2026-06-23  
**File:** `views/layouts/main.php` (Global Search section, lines ~1030–1230)

---

## Goal

Improve the Global Search modal (Ctrl+K) so that typing automatically searches contacts and stores, results show phone/email, and keyboard navigation works within results.

---

## Behavior

### Auto-search (contacts + stores)
- Trigger: user types more than 2 characters in `#gs-input`
- Searches contacts (`/api/contacts?q=`) and stores (local `ALL_BUG`/`ALL_MODAN` pool or `/api/stores?q=`) simultaneously
- Results display immediately, no debounce
- Active scope pill does NOT change — user's scope selection is preserved
- Enter key and scope pill clicks continue to work as today (search in selected scope)

### Mixed results display
- Single result list inside `#gs-results`
- Section header "אנשי קשר" followed by contact rows
- Section header "חנויות" followed by store rows
- Contact row: avatar initial + full name + phone (if exists) + email (if exists)
- Store row: store number + name + city + phone (unchanged from today)
- If one category has no results, its section is hidden

### Keyboard navigation
- Arrow Down / Arrow Up move a highlight cursor through all result rows (`.gs-row`)
- Enter on a highlighted row activates it (same action as click)
- The modal stays open after activating a result (contact view opens on top; store view opens on top)
- Esc closes the modal as today

### Scope pills — no change for calls/pbx
- Clicking "קריאות שירות" or "שיחות מרכזיה" scope pill fires search in that scope with current query (existing behavior)
- These scopes do NOT auto-search on input

---

## Implementation scope

All changes are **JS-only inside `views/layouts/main.php`**, in the existing Global Search `<script>` block.

### Changes needed

1. **Add input listener for auto-search**  
   In the `_gsInput.addEventListener('input', ...)` handler (currently calls `gsEmpty()`), add: if `value.trim().length > 2`, call new `gsAutoSearch()`.

2. **New `gsAutoSearch()` function**  
   - Parallel fetch: contacts + stores
   - Renders combined result list with section headers
   - Does not touch `_gsScope`

3. **Update `gsRenderContacts()`**  
   Add phone and email fields to each row HTML.

4. **Add keyboard navigation**  
   - Track `_gsHighlightIdx` (integer, -1 = none)
   - In `_gsInput` keydown: ArrowDown → `gsNavResult(1)`, ArrowUp → `gsNavResult(-1)`, Enter → if `_gsHighlightIdx >= 0` activate highlighted row, else existing `gsSearch()`
   - `gsNavResult(dir)` updates highlight class on `.gs-row` elements
   - Remove highlight on input change

5. **Keep modal open after result click**  
   Contact rows: call `gsOpenContactView()` directly (already keeps modal closed — need to keep gs-modal open).  
   Store rows: open store view without calling `gsClose()`.

---

## What does NOT change

- `gsSearch()` logic for all scopes
- calls/pbx scope behavior
- Topbar search input sync
- Ctrl+K shortcut
- Esc behavior
- pbx-search component
- Wiz modal
