<div style="text-align: center; margin-top: 50px;">
  <h1 style="color: #2c3e50; font-size: 3em; border-bottom: 2px solid #6366f1; padding-bottom: 10px;">VaultTech Financial Services Portal</h1>
  <h2 style="color: #7f8c8d; font-weight: 300;">Ethical Hacking Lab 02 &mdash; Student Manual</h2>
</div>

<br><br>

<div style="background-color: #ecf0f1; padding: 20px; border-left: 5px solid #e74c3c; border-radius: 5px;">
  <h3 style="margin-top: 0; color: #c0392b;">🚨 Rules of Engagement</h3>
  <ul>
    <li><strong>Target Scope:</strong> You may only attack the VaultTech Portal at <code>http://&lt;VM_IP&gt;:80</code>.</li>
    <li><strong>Out of Scope:</strong> Do NOT attempt SSH brute-force, OS-level privilege escalation, or attacking the host. All vulnerabilities are within the web application.</li>
    <li><strong>Integrity:</strong> Flags are cryptographically bound to your Student ID. Submitting another student's flag results in an automatic zero.</li>
  </ul>
</div>

---

## 💻 Prerequisites & Attacker Machine

This lab is a **"Black-Box" network appliance**. You are not provided with a command line or terminal access to the target server.

To successfully complete this lab, you must attack the target VM from a separate **Attacker Machine** connected to the same virtual network. We strongly recommend using **Kali Linux** or **Parrot OS**.

You will need the following tools installed on your attacker machine:
- A web browser (Firefox / Chrome)
- A directory enumeration tool (<code>gobuster</code>, <code>dirb</code>, or <code>ffuf</code>)
- A password hash cracker (<code>hashcat</code> or <code>john the ripper</code>)
- A wordlist (e.g., <code>rockyou.txt</code> and a standard directory wordlist)
- An HTTP proxy / request interceptor (<code>Burp Suite Community Edition</code>)
- Terminal utilities like <code>curl</code> and <code>base64</code>

---

## 🏢 Scenario

You have been hired as a Penetration Tester by **VaultTech Financial Services** to assess the security of their internal employee portal ahead of a regulatory compliance audit.

The development team insists the portal is "encrypted and secure," but your lead suspects critical cryptographic failures, exposed configuration assets, and reliance on outdated, vulnerable components.

Your mission: find, exploit, and document **five** security weaknesses across three OWASP Top 10 (2021) categories.

---

## 🎯 Lab Objectives

<table style="width: 100%; border-collapse: collapse;">
  <thead>
    <tr style="background-color: #34495e; color: white;">
      <th style="padding: 10px; border: 1px solid #bdc3c7;">#</th>
      <th style="padding: 10px; border: 1px solid #bdc3c7;">Vulnerability</th>
      <th style="padding: 10px; border: 1px solid #bdc3c7;">OWASP Category</th>
      <th style="padding: 10px; border: 1px solid #bdc3c7;">Flag Format</th>
      <th style="padding: 10px; border: 1px solid #bdc3c7;">Points</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">1</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><strong>Weak Password Hashing (MD5)</strong></td>
      <td style="padding:10px;border:1px solid #bdc3c7;">A02 — Cryptographic Failures</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><code>FLAG{...}</code></td>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">20</td>
    </tr>
    <tr style="background-color:#f9f9f9;">
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">2</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><strong>Hardcoded API Secret in JavaScript Source</strong></td>
      <td style="padding:10px;border:1px solid #bdc3c7;">A02 — Cryptographic Failures</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><code>FLAG{...}</code></td>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">20</td>
    </tr>
    <tr>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">3</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><strong>JWT 'none' Algorithm Bypass</strong></td>
      <td style="padding:10px;border:1px solid #bdc3c7;">A02 — Cryptographic Failures</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><code>FLAG{...}</code></td>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">20</td>
    </tr>
    <tr style="background-color:#f9f9f9;">
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">4</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><strong>Security Misconfiguration (Default Admin Credentials)</strong></td>
      <td style="padding:10px;border:1px solid #bdc3c7;">A05 — Security Misconfiguration</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><code>FLAG{...}</code></td>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">20</td>
    </tr>
    <tr>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">5</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><strong>XXE via Vulnerable XML Component</strong></td>
      <td style="padding:10px;border:1px solid #bdc3c7;">A06 — Vulnerable Components</td>
      <td style="padding:10px;border:1px solid #bdc3c7;"><code>FLAG{...}</code></td>
      <td style="padding:10px;border:1px solid #bdc3c7;text-align:center;">20</td>
    </tr>
  </tbody>
</table>

---

## 🛠️ Methodology & Hints

<div style="display: flex; flex-wrap: wrap; gap: 15px;">

<div style="flex:1;min-width:300px;background:#fdfefe;border:1px solid #dcdde1;padding:15px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
  <h4 style="color:#2980b9;margin-top:0;">🔍 1. Reconnaissance</h4>
  <p>Browse every page and use <code>gobuster</code> / <code>ffuf</code> to discover hidden directories and files.
  Pay attention to the browser's network tab — what JavaScript files are loaded?
  Check <code>/backup/</code>, <code>/admin/</code>, and the JS bundle in <code>/assets/js/</code>.</p>
</div>

<div style="flex:1;min-width:300px;background:#fdfefe;border:1px solid #dcdde1;padding:15px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
  <h4 style="color:#27ae60;margin-top:0;">🔑 2. Cryptographic Analysis</h4>
  <p>Once you find the database backup, examine the <code>password_hash</code> column.
  What algorithm is being used? Is there a salt? Use <code>hashcat -m 0</code>
  or <code>john --format=raw-md5</code> against <code>rockyou.txt</code> to crack them.</p>
</div>

<div style="flex:1;min-width:300px;background:#fdfefe;border:1px solid #dcdde1;padding:15px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
  <h4 style="color:#8e44ad;margin-top:0;">📄 3. Source Code Review</h4>
  <p>Right-click → View Page Source on any portal page. Note what JavaScript files
  are referenced. Open <code>assets/js/portal.js</code> — read it carefully.
  Developers sometimes commit secrets to client-facing bundles.
  How can you use what you find?</p>
</div>

<div style="flex:1;min-width:300px;background:#fdfefe;border:1px solid #dcdde1;padding:15px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
  <h4 style="color:#f39c12;margin-top:0;">🔐 4. JWT Attacks</h4>
  <p>The <code>/jwt_portal.php</code> page issues tokens signed with HS256.
  Decode the token at <a href="https://jwt.io" target="_blank">jwt.io</a>.
  What happens if you change <code>"alg"</code> to <code>"none"</code> and
  strip the signature? Does the server still accept it?</p>
</div>

<div style="flex:1;min-width:300px;background:#fdfefe;border:1px solid #dcdde1;padding:15px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
  <h4 style="color:#e67e22;margin-top:0;">⚙️ 5. Misconfiguration & Vulnerable Components</h4>
  <p>Use <code>gobuster</code> to find unlisted directories (try <code>/admin/</code>).
  When you find a login form, try default credentials before anything else.
  For the XML API (<code>/api/v1/parse.php</code>), try injecting an
  external XML entity referencing <code>file:///etc/passwd</code> first,
  then target the flag file.</p>
</div>

</div>

---

## 📝 Reporting Requirements

Your final submission must be a professional penetration test report. For **each** vulnerability include:

1. **Vulnerability Type & OWASP ID** — e.g., "A02:2021 — Cryptographic Failures: Unsalted MD5 Hashing"
2. **Exploit Payload / Steps** — exact URL, headers, tool commands used
3. **Step-by-Step Reproduction** — clear enough for a developer to reproduce
4. **Proof of Concept Screenshot** — showing the exploit and visible `FLAG{...}` with your Student ID in the footer
5. **Remediation Recommendation** — specific fix with PHP code snippet where applicable

<br>

<div style="text-align:center;padding:20px;background-color:#2c3e50;color:white;border-radius:5px;">
  <h3 style="margin:0;">Good luck, and hack responsibly!</h3>
</div>
