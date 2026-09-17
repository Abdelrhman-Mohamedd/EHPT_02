# Lab 02: VaultTech Financial Services Portal (Ethical Hacking Lab)

This repository contains the complete source code, isolation configs, setup scripts, and instructor verification tools for **Lab 02**.

## Lab Theme: Cryptographic Failures, Security Misconfiguration & Vulnerable Components

Lab 02 targets **OWASP Top 10** categories:
- **A02:2021 – Cryptographic Failures** (weak hashing, plaintext secrets, JWT none algorithm)
- **A05:2021 – Security Misconfiguration** (debug endpoints, directory listing, default credentials, exposed admin panel)
- **A06:2021 – Vulnerable and Outdated Components** (simulated outdated library with known CVE-style exploit)

## Appliance Hardening & Anti-Tampering Model

To ensure students **never have direct file system access to lab files, source code, or flags**, Lab 02 is deployed as a **Hardened Black-Box Virtual Appliance**:

1. **Instructor-Prepared Template**: The instructor runs `prepare_template_vm.sh` once. This locks down the VM, hides the instructor account (`cyberlabs`) from the login screen, and moves all sensitive repo files to `/opt/lab02-setup/` (`root:root 700`), completely invisible to students.
2. **Automated GUI First-Boot Setup**: The student receives the VM and logs into a low-privileged `student` account. A GNOME autostart `zenity` popup appears automatically to ask for their Student ID, provisions the lab using restricted `sudo`, and then the wizard self-deletes.
3. **Anti-Tamper Permissions (`root:lab02`)**: All files under `/srv/labs/lab02` are owned by `root:lab02` with strict `750`/`640` permissions. The web application process `lab02` has read-only access to source code and cannot rewrite files. Flags like `/etc/lab_jwt_flag` are secured as `root:lab02 640` to prevent direct shell access.
4. **Cryptographic Flag Binding**: Flags are derived dynamically from `sha256(STUDENT_ID + VULN_TYPE + SECRET_SALT)`. The salt is stored in `/etc/lab02.conf` (`root:root 600`).
5. **Network-Only Black-Box Access**: Students interact with the web app exclusively over HTTP (`http://<VM_IP>:80`).

## Quick Start (Instructor Setup)

```bash
# 1. Clone repository
git clone https://github.com/YourOrg/EHPT_02.git
cd EHPT_02

# 2. Prepare the Template VM (Run Once)
sudo ./prepare_template_vm.sh

# 3. Export VM and distribute to students
```

See [APPLIANCE_HARDENING_GUIDE.md](APPLIANCE_HARDENING_GUIDE.md) and [ROCKY_SETUP_GUIDE.md](ROCKY_SETUP_GUIDE.md) for complete details.
