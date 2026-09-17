# Lab 02 Assessment & Anti-Cheating Grading Rubric
## VaultTech Financial Services Portal — Penetration Test

**Target URL:** `http://vaulttech.local:80`  
**Build Isolation:** Per-student VM with cryptographic flag binding  
**Total Points:** 100 | **OWASP Coverage:** A02 · A05 · A06

---

## Anti-Cheating & Submission Rules

1. **Cryptographic Flag Verification** — Every submitted flag is verified against `generate_student_flags.py`. Mismatched flags = **0/100 + plagiarism referral**.
2. **Uncropped Screenshots** — Must show the full browser URL bar **and** the portal footer with the student's assigned ID.
3. **Plagiarism Check** — Report narratives and `.buildinfo` machine hashes are cross-checked across all submissions.

---

## Grading Breakdown

| Category | Description | Points |
| :--- | :--- | :---: |
| **1. Executive Summary & Methodology** | Scope, tools used (gobuster, hashcat, curl, jwt.io), structured approach | 15 pts |
| **2. Vulnerability Findings & PoCs** | Technical exploitation, reproduction steps, screenshot + valid flag | 50 pts |
| **3. Root Cause Analysis** | OWASP-mapped code/config root cause explanation | 15 pts |
| **4. Remediation & Hardening** | Specific code fixes and configuration hardening | 20 pts |
| **Total** | | **100 pts** |

---

## Detailed Evaluation Criteria

### 1. Executive Summary & Methodology (15 pts)
- **15–13:** Clear exec summary, systematic recon documented (gobuster, page source review, Burp Suite), OWASP categories addressed.
- **12–8:** Acceptable summary; basic methodology.
- **7–0:** Missing summary or incomplete enumeration narrative.

---

### 2. Vulnerability Findings & Proof-of-Concept (50 pts / 10 pts each)

#### A. Account Portal — Weak MD5 Password Hashing (10 pts)
- **OWASP:** A02:2021 — Cryptographic Failures
- **Target:** `/backup/users.db.sql` → `account.php` (login form)
- **Criteria:**
  - Finds `/backup/` via gobuster; directory listing enabled (Apache misconfiguration).
  - Downloads `users.db.sql`; identifies `password_hash` column stored as unsalted MD5.
  - Cracks `admin` MD5 hash using `hashcat -m 0 hash.txt rockyou.txt` or `john`.
  - Logs into `account.php` as `admin` using the recovered plaintext password.
  - Admin panel displays `FLAG{WEAKHASH}` — submitted flag matches `generate_student_flags.py`.

#### B. JavaScript Bundle — Hardcoded API Secret (10 pts)
- **OWASP:** A02:2021 — Cryptographic Failures (Hardcoded Credentials)
- **Target:** `/assets/js/portal.js` → `/internal_api.php`
- **Criteria:**
  - Views page source (Ctrl+U) or opens browser DevTools → Sources.
  - Opens `assets/js/portal.js`; finds `INTERNAL_API_TOKEN` constant with hardcoded bearer token.
  - Calls the protected endpoint with the token:
    ```bash
    curl -H "Authorization: Bearer <token>" http://vaulttech.local:80/internal_api.php
    # OR: http://vaulttech.local:80/internal_api.php?token=<token>
    ```
  - JSON response contains `FLAG{HARDCODED}`.

#### C. JWT Portal — 'none' Algorithm Authentication Bypass (10 pts)
- **OWASP:** A02:2021 — Cryptographic Failures
- **Target:** `jwt_portal.php`
- **Criteria:**
  - Obtains a valid HS256 JWT by logging in with employee credentials.
  - Decodes the token (jwt.io or manual base64url decode).
  - Modifies header to `{"alg":"none","typ":"JWT"}` and payload to `"role":"admin"`.
  - Re-encodes without a signature: `<header>.<payload>.` (trailing dot, empty sig).
  - Server accepts forged token and returns `FLAG{JWT}`.

#### D. Admin Panel — Default Credentials (10 pts)
- **OWASP:** A05:2021 — Security Misconfiguration
- **Target:** `/admin/` (discovered via directory enumeration)
- **Discovery:** `gobuster dir -u http://<IP>:80 -w common.txt` reveals `/admin/`
- **Criteria:**
  - Discovers the admin login form at `/admin/index.php`.
  - Tries default credentials: **`admin` / `admin`** — login succeeds immediately.
  - Documents additional misconfigurations visible on the page:
    - Verbose error messages disclosing valid usernames
    - Session ID exposed in URL (`session.use_trans_sid=On`)
    - Missing security headers (CSP, X-Frame-Options, X-Content-Type-Options)
    - Server version disclosed in HTTP response headers
  - Admin dashboard reveals `FLAG{MISCONFIG}`.

#### E. Legacy XML API — XXE Injection (10 pts)
- **OWASP:** A06:2021 — Vulnerable and Outdated Components
- **Target:** `POST /api/v1/parse.php` (XML API endpoint)
- **Criteria:**
  - Discovers endpoint via `api_status.php` and/or `/api/` directory listing.
  - Sends an XXE payload via cURL:
    ```bash
    curl -X POST http://vaulttech.local:80/api/v1/parse.php \
         -H "Content-Type: application/xml" \
         -d '<?xml version="1.0"?>
    <!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/lab_vulncomp_flag">]>
    <request><data>&xxe;</data></request>'
    ```
  - Flag contents appear in `<parsed>` element of the XML response.
  - Submits `FLAG{VULNCOMP}`.
  - **Bonus credit:** Student notes the endpoint uses `LIBXML_NOENT` which enables entity processing (disabled by default in PHP 8+) and identifies this as a known class of vulnerability in outdated/misconfigured libxml2 integrations.

---

### 3. Root Cause Analysis (15 pts)
- **15–13:** All 5 vulnerabilities correctly mapped to OWASP and explained with code-level root cause:
  - MD5: `md5()` has no salt → rainbow table / dictionary attack trivial; fix: `password_hash($p, PASSWORD_BCRYPT, ['cost'=>12])`.
  - Hardcoded token: developer committed secret to client-side JS bundle; fix: secrets manager + short-lived OAuth tokens.
  - JWT none: `verify_jwt()` branches on `if ($alg === 'none')` without restricting allowed algorithms; fix: explicit allowlist `['HS256']`.
  - Default creds: admin password never rotated post-deployment; fix: force password change on first login.
  - XXE: `libxml_disable_entity_loader(false)` + `LIBXML_NOENT` re-enables external entity processing disabled by PHP 8; fix: never call `libxml_disable_entity_loader(false)`.
- **12–8:** Most root causes correct; minor gaps.
- **7–0:** Shallow or incorrect technical details.

---

### 4. Remediation & Secure Coding Recommendations (20 pts)
- **20–17:** Specific, actionable fixes with code:
  - **MD5→bcrypt:** `password_hash($p, PASSWORD_BCRYPT, ['cost'=>12])` + `password_verify($p, $hash)`
  - **Hardcoded secret:** Store in environment variable (`$_SERVER['INTERNAL_API_TOKEN']`); rotate regularly.
  - **JWT none:** `if (!in_array($header['alg'], ['HS256'], true)) return null;`
  - **Default creds:** Enforce password change on first login; add account lockout after N failures.
  - **XXE:** Remove `libxml_disable_entity_loader(false)`; parse with `LIBXML_NONET`; validate input schema.
  - **Directory listing:** Add `Options -Indexes` to Apache config for `/backup/` and `/admin/`.
  - **Security headers:** `Header always set X-Frame-Options "DENY"`, `Content-Security-Policy`, `X-Content-Type-Options: nosniff`.
- **16–10:** Generic recommendations without specific code.
- **9–0:** Minimal or missing remediation.
