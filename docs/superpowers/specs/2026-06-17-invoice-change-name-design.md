# Design: Invoice Change Name — V2

**Date:** 2026-06-17  
**Status:** Approved

---

## Overview

Port the v1 `invoiceChangeName` page to the V2 MVC framework.  
Managers can view, update status, edit record fields, and assign a handler.  
The creation form lives inside the Formatter (v2 formatter modal) and submits to a new API route.  
Agents list (dropdown for assigning handler) is pulled from `users` where `is_active=1` and `department_id` matches שירות לקוחות in the `departments` table.

---

## Database — `alon_db2`

New migration file: `v2/config/migration_invoice_change_name.php`

```sql
CREATE TABLE IF NOT EXISTS invoice_change_name (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  open_by_id         INT NOT NULL,
  open_by_name       VARCHAR(100) NOT NULL DEFAULT '',
  new_name           VARCHAR(100) NOT NULL,
  invoice_sap_number VARCHAR(20)  NOT NULL,
  invoice_note       VARCHAR(500) NOT NULL DEFAULT '',
  customer_phone     VARCHAR(30)  NOT NULL DEFAULT '',
  customer_mail      VARCHAR(150) NOT NULL DEFAULT '',
  customer_name      VARCHAR(100) NOT NULL DEFAULT '',
  status             VARCHAR(30)  NOT NULL DEFAULT 'פתוחה',
  care_by            VARCHAR(100) NOT NULL DEFAULT '',
  time_added         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  time_change_status DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  isActive           TINYINT      NOT NULL DEFAULT 1,
  INDEX idx_status (status),
  INDEX idx_invoice (invoice_sap_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Status values: `פתוחה`, `בהמתנה`, `טופלה + מייל`, `סגורה`, `תקלה בפרטים`

---

## File Structure

```
v2/
├─ config/
│  └─ migration_invoice_change_name.php
├─ src/
│  ├─ Controllers/
│  │  └─ InvoiceChangeNameController.php
│  └─ Models/
│     └─ InvoiceChangeNameModel.php
└─ views/
   └─ pages/
      └─ invoice-change-name/
         └─ index.php
```

Also modified:
- `v2/config/routes.php` — add routes
- `v2/src/Models/UserModel.php` — add `customerServiceUsers()` method
- `v2/views/components/formatter-modal.php` — add invoice change name form section

---

## Routes

```
GET  /invoice-change-name                    → InvoiceChangeNameController@index
GET  /api/invoice-change-name                → InvoiceChangeNameController@apiList
POST /api/invoice-change-name/create         → InvoiceChangeNameController@create
POST /api/invoice-change-name/{id}/status    → InvoiceChangeNameController@updateStatus
POST /api/invoice-change-name/{id}/edit      → InvoiceChangeNameController@editField
```

---

## Model — `InvoiceChangeNameModel`

Methods:
- `all(): array` — SELECT ordered: פתוחה → בהמתנה → טופלה/סגורה
- `byId(int $id): ?array`
- `create(array $data): int` — INSERT, returns lastInsertId
- `updateStatus(int $id, string $status, string $careBy): bool`
- `editField(int $id, string $field, string $value): bool` — whitelist: `new_name`, `invoice_note`, `invoice_sap_number`, `customer_name`, `customer_phone`, `customer_mail`
- `checkDuplicate(string $invoiceNum): bool` — returns true if active record exists for that invoice number

`UserModel::customerServiceUsers(): array` — query:
```sql
SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name
FROM users u
JOIN departments d ON d.id = u.department_id
WHERE u.is_active = 1
  AND (d.name_heb LIKE '%שירות%' OR d.name_heb LIKE '%מוקד%')
ORDER BY u.first_name ASC
```

---

## Controller — `InvoiceChangeNameController`

**`index()`** — `requirePermission('canUseInvoiceChangeName')`, loads `$users` (customerServiceUsers), passes to view.

**`apiList()`** — returns JSON `{rows: [...], users: [...]}` — rows grouped by status for JS rendering.

**`create()`** — `requireAuth()`, `verifyCsrf()`, validates:
- `invoice_sap_number`: numeric, 9 chars, no duplicate
- `new_name`: max 50 chars
- `customer_phone`: numeric
- `customer_mail`: not empty
Inserts record, sends mail via `Mailer` (same pattern as v1), returns JSON.

**`updateStatus(int $id)`** — `requirePermission('canUseInvoiceChangeName')`, `verifyCsrf()`, updates status + care_by. If status is `טופלה + מייל`, sends mail to opener.

**`editField(int $id)`** — `requirePermission('canUseInvoiceChangeName')`, `verifyCsrf()`, whitelist check on field name, updates single field.

---

## View — `views/pages/invoice-change-name/index.php`

Three sections rendered by JS after `GET /api/invoice-change-name`:

1. **פתוחות** — card/table rows, yellow badge. Each row:
   - Editable cells: `new_name`, `invoice_note` (click → inline prompt → POST editField)
   - Status dropdown → POST updateStatus
   - Handler dropdown (customerServiceUsers) → sent with status update
2. **בהמתנה** — orange badge, same structure
3. **טופלו / סגורות** — collapsible, green badge, read-only rows

Header: `רענן רשימה` button + `ייצוא לאקסל` button (client-side via SheetJS, same columns as v1).

Admin edit: clicking any editable cell opens a `prompt()` (identical to v1 `.new_name` pattern) and POSTs to `editField`. Available on open+pending rows only. Editable fields: `new_name`, `invoice_note`.

---

## Formatter Integration

In `formatter-modal.php` (v2), when a template has `invoice_change_name` action, inject the form HTML:

```html
<div id="change_name_invoice_form">
  <fieldset>
    <legend>תבנית {name}</legend>
    <label>מספר חשבונית סאפ</label>
    <input type="text" id="invoice_num_toChange" autocomplete="off">
    <label>שם חדש על-גבי החשבונית</label>
    <input type="text" id="name_to_change">
    <label>הערה (לא חשוף ללקוח) - לא חובה</label>
    <input type="text" id="note_change_name">
    <button id="change_name_invoice_btn">בקשה לשינוי שם</button>
  </fieldset>
</div>
```

JS validation (identical to v1): invoice 9 digits, new_name < 50 chars, phone numeric, mail not empty.  
Submit: `POST /api/invoice-change-name/create` with CSRF token.

---

## Mail

Uses existing `Core\Mailer` (v2). Two mails:
1. **On create** — to fixed recipients (`eyal@bug.co.il`, `alonv@bug.co.il`, `bat-el@bug.co.il`), CC opener
2. **On `טופלה + מייל` status** — to opener + optionally to selected handler

---

## Permissions

| Permission | Usage |
|---|---|
| `canUseInvoiceChangeName` | Already exists in PERM_LABELS — view, update status, edit open fields |

No new permission needed. Admin editing of all fields still gated behind `canUseInvoiceChangeName` since all users of this page are trusted staff.

---

## Error Handling

- Duplicate invoice number → JSON error, user sees `v2Toast`
- Invalid phone/mail/length → JSON error before DB write
- CSRF failure → 403
- DB failure → JSON `{error: true, msg: '...'}`
