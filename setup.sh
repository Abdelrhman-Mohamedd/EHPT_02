#!/usr/bin/env bash
# ==============================================================================
# Lab 02 Deployment & Personalization Script
# Vulnerabilities: A02 Cryptographic Failures | A05 Security Misconfiguration
#                  A06 Vulnerable Components
# ==============================================================================
set -e

if [ "$EUID" -ne 0 ]; then
  echo "[-] Run as root: sudo ./setup.sh <STUDENT_ID> [--production]"; exit 1
fi

STUDENT_ID="${1:-abdelrhman_h_2026}"
IS_PRODUCTION=0
for arg in "$@"; do [ "$arg" == "--production" ] && IS_PRODUCTION=1; done

SALT_FILE="/etc/lab02.conf"
SECRET_SALT=$([ -f "$SALT_FILE" ] && cat "$SALT_FILE" || echo "${2:-EHPT02_SECRET_SALT_2026}")
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

[ "$SCRIPT_DIR" == "/srv/labs/lab02" ] && echo "[-] Do not run from /srv/labs/lab02" && exit 1

echo "==[🔒] Lab 02 — Personalizing for: ${STUDENT_ID} =="

# Flag derivation
FLAG_WEAKHASH=$(echo -n  "${STUDENT_ID}_WEAKHASH_${SECRET_SALT}"  | sha256sum | cut -c1-32)
FLAG_HARDCODED=$(echo -n "${STUDENT_ID}_HARDCODED_${SECRET_SALT}" | sha256sum | cut -c1-32)
FLAG_JWT=$(echo -n       "${STUDENT_ID}_JWT_${SECRET_SALT}"       | sha256sum | cut -c1-32)
FLAG_MISCONFIG=$(echo -n "${STUDENT_ID}_MISCONFIG_${SECRET_SALT}" | sha256sum | cut -c1-32)
FLAG_VULNCOMP=$(echo -n  "${STUDENT_ID}_VULNCOMP_${SECRET_SALT}"  | sha256sum | cut -c1-32)

echo "[+] Step 1: System user 'lab02'..."
id -u lab02 >/dev/null 2>&1 || useradd --system --no-create-home --shell=/usr/sbin/nologin lab02

echo "[+] Step 2: Directory structure..."
mkdir -p /srv/labs/lab02/{public,data,sessions}
mkdir -p /srv/labs/lab02/public/{assets/{css,js},api/v1,admin,backup,uploads}
mkdir -p /srv/labs/lab02/data/{db,secrets}

echo "[+] Step 3: Copying app files..."
cp -r "${SCRIPT_DIR}/public/"* /srv/labs/lab02/public/
cp -r "${SCRIPT_DIR}/data/"*   /srv/labs/lab02/data/ 2>/dev/null || true

echo "[+] Step 4: Injecting flags..."

# FLAG 1 (WEAKHASH/A02): MD5-hashed passwords in /backup/users.db.sql
MD5_ADMIN=$(echo -n "vaulttech2024" | md5sum | cut -c1-32)
cat > /srv/labs/lab02/public/backup/users.db.sql << EOF
-- VaultTech Internal User Database — Automated Nightly Backup
-- Date: 2024-09-01 03:00:01 UTC  |  CONFIDENTIAL

CREATE TABLE users (
    id INTEGER PRIMARY KEY, username TEXT NOT NULL,
    email TEXT NOT NULL, password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'employee', last_login TEXT
);

INSERT INTO users VALUES (1,'admin','admin@vaulttech.com','${MD5_ADMIN}','admin','2024-09-01');
INSERT INTO users VALUES (2,'j.smith','j.smith@vaulttech.com','5f4dcc3b5aa765d61d8327deb882cf99','employee','2024-08-30');
INSERT INTO users VALUES (3,'m.williams','m.williams@vaulttech.com','7c4a8d09ca3762af61e59520943dc26b','manager','2024-08-29');
INSERT INTO users VALUES (4,'b.chen','b.chen@vaulttech.com','e10adc3949ba59abbe56e057f20f883e','employee','2024-08-28');
-- Vault monitor record (system-generated)
INSERT INTO users VALUES (99,'_vault_monitor','monitor@vaulttech.com','FLAG{${FLAG_WEAKHASH}}','system','2024-09-01');
-- NOTE: Passwords stored as MD5 with no salt.
EOF

# FLAG 2 (HARDCODED/A02): API bearer token hardcoded in assets/js/portal.js
HARDCODED_TOKEN="vtk-internal-$(echo -n "${STUDENT_ID}_token_${SECRET_SALT}" | sha256sum | cut -c1-16)"
cat > /srv/labs/lab02/public/assets/js/portal.js << EOF
/**
 * VaultTech Portal — Frontend Bundle v2.4.1 (minification disabled)
 */

// [TODO] FIXME Dev#4421: Remove hardcoded token before next release!
// Temporarily hardcoded during 2024-07-12 deployment emergency.
const INTERNAL_API_TOKEN = "${HARDCODED_TOKEN}";

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.card').forEach(function(c, i) {
        c.style.opacity = '0'; c.style.transform = 'translateY(16px)';
        setTimeout(function() {
            c.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            c.style.opacity = '1'; c.style.transform = 'translateY(0)';
        }, i * 80);
    });
});
EOF
echo "${HARDCODED_TOKEN}"          > /srv/labs/lab02/data/secrets/.api_token
echo "FLAG{${FLAG_HARDCODED}}"     > /srv/labs/lab02/data/secrets/.hardcoded_flag

# FLAG 3 (JWT/A02): JWT 'none' bypass — flag stored in /etc (lab02 process only)
echo "FLAG{${FLAG_JWT}}"           > /etc/lab_jwt_flag
chown root:lab02 /etc/lab_jwt_flag; chmod 640 /etc/lab_jwt_flag

# FLAG 4 (MISCONFIG/A05): Default creds admin:admin on /admin/
echo "FLAG{${FLAG_MISCONFIG}}"     > /srv/labs/lab02/data/secrets/.admin_flag

# FLAG 5 (VULNCOMP/A06): XXE via legacy XML API
echo "FLAG{${FLAG_VULNCOMP}}"      > /etc/lab_vulncomp_flag
chown root:lab02 /etc/lab_vulncomp_flag; chmod 640 /etc/lab_vulncomp_flag

echo "[+] Step 5: Build metadata..."
BUILD_TIME=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
MACHINE_HASH="N/A"
[ -f /etc/machine-id ] && MACHINE_HASH=$(sha256sum /etc/machine-id | cut -c1-32)

printf "STUDENT_ID=%s\nBUILD_TIME=%s\n" "$STUDENT_ID" "$BUILD_TIME" > /srv/labs/lab02/data/.studentinfo
cat << EOF > /srv/labs/lab02/.buildinfo
STUDENT_ID=${STUDENT_ID}
BUILD_TIMESTAMP=${BUILD_TIME}
MACHINE_HASH=${MACHINE_HASH}
FLAG_WEAKHASH=FLAG{${FLAG_WEAKHASH}}
FLAG_HARDCODED=FLAG{${FLAG_HARDCODED}}
FLAG_JWT=FLAG{${FLAG_JWT}}
FLAG_MISCONFIG=FLAG{${FLAG_MISCONFIG}}
FLAG_VULNCOMP=FLAG{${FLAG_VULNCOMP}}
EOF

echo "[+] Step 6: Permissions..."
id -u apache >/dev/null 2>&1 && usermod -aG lab02 apache
id -u www-data >/dev/null 2>&1 && usermod -aG lab02 www-data
chown -R root:lab02 /srv/labs/lab02
chmod 750 /srv/labs/lab02 /srv/labs/lab02/public /srv/labs/lab02/data /srv/labs/lab02/sessions
chmod 700 /srv/labs/lab02/.buildinfo
find /srv/labs/lab02/public -type d -exec chmod 750 {} \;
find /srv/labs/lab02/public -type f -exec chmod 640 {} \;
find /srv/labs/lab02/data   -type f -exec chmod 640 {} \;
chmod 750 /srv/labs/lab02/data/secrets
# /backup/ files world-readable (intentional misconfiguration — dir listing)
chown -R root:lab02 /srv/labs/lab02/public/backup
find /srv/labs/lab02/public/backup -type f -exec chmod 644 {} \;
# uploads writable by lab02 process
chown -R lab02:lab02 /srv/labs/lab02/public/uploads; chmod 770 /srv/labs/lab02/public/uploads

echo "[+] Step 7: SELinux (port 80)..."
if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce)" != "Disabled" ]; then
    setsebool -P httpd_can_network_connect 1 2>/dev/null || true
    command -v semanage >/dev/null 2>&1 && {
        semanage port -m -t http_port_t -p tcp 80 2>/dev/null || \
        semanage port -a -t http_port_t -p tcp 80 2>/dev/null || true
        semanage fcontext -a -t httpd_sys_rw_content_t "/srv/labs/lab02(/.*)?" 2>/dev/null || true
    }
    command -v restorecon >/dev/null 2>&1 && restorecon -R /srv/labs/lab02 2>/dev/null || true
fi

echo "[+] Step 8: Hostname & banner..."
HOSTNAME_TARGET="lab02-${STUDENT_ID//_/-}"
command -v hostnamectl >/dev/null 2>&1 && hostnamectl set-hostname "${HOSTNAME_TARGET}" 2>/dev/null || echo "${HOSTNAME_TARGET}" > /etc/hostname
cat << EOF > /etc/motd
==============================================================================
  VaultTech Ethical Hacking Black-Box Appliance (Lab 02)
  Student ID  : ${STUDENT_ID}    |    Build: ${BUILD_TIME}
  Portal URL  : http://<VM_IP>:80  or  http://vaulttech.local:80
  [!] All testing must be performed over the network only.
==============================================================================
EOF
cp /etc/motd /etc/issue

echo "[+] Step 9: PHP-FPM pool..."
FPM_CONF_COPIED=0
for FPM_DIR in "/etc/php-fpm.d" "/etc/php/8.3/fpm/pool.d" "/etc/php/8.2/fpm/pool.d"; do
    if [ -d "$FPM_DIR" ]; then
        cp "${SCRIPT_DIR}/config/lab02-php-fpm.conf" "${FPM_DIR}/lab02.conf"
        FPM_CONF_COPIED=1; break
    fi
done
[ "$FPM_CONF_COPIED" -eq 0 ] && { mkdir -p /etc/php-fpm.d; cp "${SCRIPT_DIR}/config/lab02-php-fpm.conf" /etc/php-fpm.d/lab02.conf; }

echo "[+] Step 10: Apache VirtualHost..."
[ -d "/etc/httpd/conf.d" ]            && cp "${SCRIPT_DIR}/config/lab02-apache.conf" /etc/httpd/conf.d/lab02.conf
[ -d "/etc/apache2/sites-available" ] && { cp "${SCRIPT_DIR}/config/lab02-apache.conf" /etc/apache2/sites-available/lab02.conf; a2ensite lab02.conf || true; }

echo "[+] Step 11: /etc/hosts..."
grep -q "vaulttech.local" /etc/hosts || echo "127.0.0.1 vaulttech.local" >> /etc/hosts

echo "[+] Step 12: Restarting services..."
systemctl restart php-fpm || systemctl restart php8.3-fpm || systemctl restart php8.2-fpm || true
systemctl restart httpd   || systemctl restart apache2    || true

if [ "$IS_PRODUCTION" -eq 1 ]; then
    echo "[+] Step 13: Production purge..."
    rm -f "${SCRIPT_DIR}/setup.sh" "${SCRIPT_DIR}/generate_student_flags.py"
    rm -rf "${SCRIPT_DIR}/.git"
fi

echo "==[🎉] Lab 02 ready for ${STUDENT_ID} — http://vaulttech.local:80 =="
