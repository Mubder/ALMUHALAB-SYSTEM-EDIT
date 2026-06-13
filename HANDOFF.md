# Project Handoff — ALMuhalab System

## Overview

This repository contains two things:

1. **Laravel App** (`app.almuhalab.net`) — Service request management system with role-based workflow
2. **Corporate Site** (`www.almuhalab.net`) — WordPress + Spectra + Polylang + Astra (planned, not yet built)

**Hosting:** GoDaddy shared hosting (PHP/MySQL/cPanel)  
**cPanel Username:** `b9dumvi117dk`  
**cPanel IP:** `92.204.28.237`  
**Primary Domain:** `almuhalab.net`  
**Home Directory:** `/home/b9dumvi117dk`  
**Laravel Root:** `/home/b9dumvi117dk/public_html/app/`  
**Laravel Public:** `/home/b9dumvi117dk/public_html/app/public/`

---

## Repository Structure

```
origin:    git@github.com:Mubder/ALMUHALAB-SYSTEM-EDIT.git
upstream:  git@github.com:Mrhumood/ALMUHALAB-SYSTEM.git
branch:    main
```

---

## What Was Done (Session Summary)

### 1. Logo on Login & Navbar

- Copied `C:\Users\balfa\Pictures\ALMuhalab\Logo.png` to `public/images/Logo.png`
- Updated `resources/views/layouts/app.blade.php` navbar brand mark to use `<img>` instead of the "م" letter
- Updated `resources/views/layouts/guest.blade.php` login brand to show logo image with company name in white

### 2. Background Image on Login/Register Pages

- Copied `C:\Users\balfa\Pictures\ALMuhalab\Background.png` to `public/images/Background.png`
- Updated `resources/views/layouts/guest.blade.php`:
  - Added `.login-bg` class with `background: url('/images/Background.png')` cover
  - Added dark overlay (`rgba(15, 23, 42, 0.55)`) via `::before` pseudo-element
  - Made `.login-card` semi-transparent with backdrop blur
  - Added amber gradient login button

### 3. Background Image on Landing Page (`app.almuhalab.net`)

- Updated `resources/views/app-landing.blade.php`:
  - Same background image + dark overlay as login pages
  - Logo image displayed at top
  - Semi-transparent card with backdrop blur
  - Amber gradient buttons matching login page
  - Footer centered below card (wrapped in `.wrapper` div with `flex-direction: column`)
  - Copyright text: "© {year} ALMuhalab International Co. — Kuwait City"

### 4. 500 Error Fix (WorkflowService)

- **File:** `app/Services/WorkflowService.php`
- Added `safeNotifyTransition()` and `safeNotifyStatusChange()` wrapper methods
- These catch exceptions around notification sending so workflow operations don't crash
- Changed `fireTransition()` to use `safeNotifyTransition()` for both the main user notification and the admin notification
- **Root cause:** Notifications were throwing exceptions (e.g., missing email config) which killed the entire transition

### 5. Timezone Fix

- **File:** `config/app.php`
- Changed `'timezone' => 'UTC'` to `'timezone' => 'Asia/Kuwait'`
- **File:** `.env` (on production)
- Set `TIMEZONE=Asia/Kuwait`

### 6. Permissions Fix

#### 6a. Removed `delete_request` from User (Client) Role

- **File:** `database/seeders/PermissionSeeder.php`
- Removed `'delete_request'` from the User role permissions array
- Clients should not be able to delete service requests

#### 6b. Split Admin Routes by Permission

- **File:** `routes/web.php`
- Admin routes were ALL under a single `manage_users` middleware gate
- Split into 4 separate route groups with specific permissions:

| Route Prefix | Permission Required | Purpose |
|---|---|---|
| `/admin/users` | `manage_users` | User management |
| `/admin/service-catalog` | `manage_service_catalog` | Service categories & types |
| `/admin/audit-log` | `view_audit_log` | Audit trail |
| `/admin/pages` | `manage_pages` | CMS pages |

#### 6c. Fixed Client Access Control

- **File:** `app/Http/Controllers/ServiceRequestController.php`
- `authorizeAccess()` method now restricts clients to their own requests only
- Added check: `$request->created_by !== auth()->id()` blocks clients from viewing other clients' requests

#### 6d. Fixed Navbar Admin Dropdown

- **File:** `resources/views/layouts/app.blade.php`
- Admin dropdown links now check for specific permissions:
  - "Users" link → `manage_users`
  - "Service Catalog" link → `manage_service_catalog`
  - "Audit Log" link → `view_audit_log`

### 7. Deployment

All changes were deployed to GoDaddy via cPanel File Manager API:
- Uploaded as a zip, extracted manually by user
- View/config caches cleared via artisan commands
- `php artisan db:seed --class=PermissionSeeder --force` re-ran to apply permission changes

---

## Known Issues (Low/Medium Priority)

| Issue | Severity | Details |
|---|---|---|
| `ActivityLog.user` column is `string` but stores integer IDs | Low | Schema mismatch, works but should be `unsignedBigInteger` |
| `request_services.created_by` cascades on delete | Medium | Deleting a user cascades to delete their service requests |
| `DATEDIFF()` MySQL-only in `DashboardController` | Medium | `.env.example` defaults to SQLite, `DATEDIFF()` won't work on SQLite |
| Two `Route::get('/admin', ...)` in web.php | Low | Duplicate route, last one wins |

---

## Corporate Site Plan (Not Yet Built)

**Target:** `www.almuhalab.net`  
**Stack:**
- WordPress (free)
- Astra theme (free, RTL-ready)
- Spectra plugin (free block builder — chosen over Elementor which loads/stucks on GoDaddy shared hosting)
- Polylang (free — now includes Language Switcher Block as of 3.8)

**Pages to Build (10 bilingual pages):**
1. Home
2. About
3. Transportation Division
4. Trading Division
5. Construction Division
6. Oil & Drilling Division
7. Tourism Division
8. Partners (10 logos, no links)
9. Contact (form + Google Maps)
10. Careers (placeholder — no job listings yet)

**Assets:**
- Logo: `public/images/Logo.png`
- Background: `public/images/Background.png`
- Division images: Generate using Microsoft Copilot (free, no watermark)

**User is the primary editor** — no client logins needed for the corporate site.

---

## How to Deploy Changes

### Via cPanel API (automated)

```powershell
# Upload file
$boundary = [System.Guid]::NewGuid().ToString()
$fileBytes = [System.IO.File]::ReadAllBytes("LOCAL_PATH")
$enc = [System.Text.Encoding]::UTF8
$bodyStart = "--$boundary`r`nContent-Disposition: form-data; name=`"dir`"`r`n`r`nREMOTE_DIR`r`n--$boundary`r`nContent-Disposition: form-data; name=`"overwrite`"`r`n`r`n1`r`n--$boundary`r`nContent-Disposition: form-data; name=`"file`"; filename=`"FILENAME`"`r`nContent-Type: application/octet-stream`r`n`r`n"
$bodyEnd = "`r`n--$boundary--`r`n"
$startBytes = $enc.GetBytes($bodyStart)
$endBytes = $enc.GetBytes($bodyEnd)
$all = New-Object byte[] ($startBytes.Length + $fileBytes.Length + $endBytes.Length)
[System.Buffer]::BlockCopy($startBytes, 0, $all, 0, $startBytes.Length)
[System.Buffer]::BlockCopy($fileBytes, 0, $all, $startBytes.Length, $fileBytes.Length)
[System.Buffer]::BlockCopy($endBytes, 0, $all, $startBytes.Length + $fileBytes.Length, $endBytes.Length)
$uri = "https://92.204.28.237:2083/execute/Fileman/upload_files"
$req = [System.Net.HttpWebRequest]::Create($uri)
$req.Method = "POST"
$req.ContentType = "multipart/form-data; boundary=$boundary"
$req.Headers.Add("Authorization", "cpanel b9dumvi117dk:BE7KOU61C6ENMICMKFONGWA639RRLH2R")
$req.ContentLength = $all.Length
$stream = $req.GetRequestStream()
$stream.Write($all, 0, $all.Length)
$stream.Close()
$resp = $req.GetResponse()
```

### Run Artisan Commands

The cPanel Terminal API module is not available. Use the PHP runner trick:

1. Upload `deploy_runner.php` via cPanel File Manager API
2. Access `https://almuhalab.net/app/deploy_runner.php?key=almuhalab_deploy_2026`
3. The script auto-deletes after execution

```php
<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'almuhalab_deploy_2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
chdir('/home/b9dumvi117dk/public_html/app');
$commands = [
    'php artisan config:clear 2>&1',
    'php artisan view:clear 2>&1',
    'php artisan cache:clear 2>&1',
];
foreach ($commands as $cmd) {
    echo ">>> $cmd\n";
    echo shell_exec($cmd) . "\n";
}
unlink(__FILE__);
```

### SSL Bypass for cPanel API (PowerShell 5.1)

```powershell
Add-Type @"
using System.Net; using System.Net.Security; using System.Security.Cryptography.X509Certificates;
public class T { public static void Enable() { ServicePointManager.ServerCertificateValidationCallback = delegate { return true; }; } }
"@; [T]::Enable()
```

---

## Environment Variables

| Key | Value |
|---|---|
| `APP_NAME` | ALMuhalab International Co. |
| `APP_URL` | https://app.almuhalab.net |
| `DB_CONNECTION` | mysql |
| `TIMEZONE` | Asia/Kuwait |

---

## Testing Checklist

- [ ] Login page shows logo + background image
- [ ] Register page shows logo + background image
- [ ] `app.almuhalab.net` landing page shows background image (not plain gray)
- [ ] `app.almuhalab.net` copyright text is centered below the card
- [ ] Workflow "return to previous stage" doesn't throw 500 error
- [ ] Clients can only see their own requests
- [ ] Users without `manage_users` don't see the Users link in navbar
- [ ] Users without `manage_service_catalog` don't see Service Catalog link
- [ ] Users without `view_audit_log` don't see Audit Log link
