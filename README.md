<div align="center">
  <img width=115 src="https://github.com/user-attachments/assets/4ad8438e-d63f-4d8b-b44b-7001be28b81f" />
  <h1>NetStress — PHP Edition</h1>
</div>

> Pure PHP with JSON file storage — just upload to your www folder and go!

**NetStress** is a powerful and modern stress testing platform:

* ✅ Pure PHP frontend (no build step needed)
* ✅ JSON file storage (no database required)
* ✅ Dual-layer architecture (L4 and L7 methods)
* ✅ Admin panel for user, plan, and method management
* ✅ Beautiful, dark UI with Tailwind CSS
* ✅ Just upload and run — no Node.js, no npm, no build!

---

## 🚀 Quick Start

1. Upload **all files** to your web server's `www` / `htdocs` / `public_html` folder
2. Make sure the `data/` folder is writable: `chmod 755 data/`
3. Open the site in your browser
4. Login with the default admin account:
   - **Username:** `admin`
   - **Password:** `password`

> ⚠️ **Important:** Change the default password after first login!

---

## Requirements

- PHP 7.4+ (with `json` and `session` extensions — enabled by default)
- Apache or Nginx web server
- Write permissions on the `data/` directory

---

## 📂 Project Structure

```
├── index.php          # Landing page
├── login.php          # Login page
├── register.php       # Registration page
├── dashboard.php      # Dashboard with stats
├── panel.php          # Attack panel
├── admin.php          # Admin panel (users, plans, methods)
├── profile.php        # User profile
├── api-docs.php       # API documentation
├── logout.php         # Logout handler
├── includes/          # Shared PHP includes
│   ├── config.php     # Configuration & helpers
│   ├── auth.php       # Authentication functions
│   ├── header.php     # HTML header
│   ├── sidebar.php    # Navigation sidebar
│   └── footer.php     # HTML footer
├── api/               # JSON API endpoints
│   ├── login.php
│   ├── register.php
│   ├── verify-token.php
│   ├── dashboard.php
│   ├── profile.php
│   ├── attack.php
│   ├── methods.php
│   ├── plans.php
│   └── users.php
├── data/              # JSON data storage
│   ├── .htaccess      # Blocks direct access
│   ├── users.json
│   ├── plans.json
│   ├── methods.json
│   └── attacks.json
└── assets/            # Static assets
    ├── css/style.css
    ├── js/app.js
    ├── js/admin.js
    └── imagens/
```

---

## ⚙️ Configuration

Edit `includes/config.php` to change:
- `TOKEN_SECRET` — Change this to a random string for security
- `SITE_NAME` — Your site name

---

## 🔒 Security Notes

- The `data/.htaccess` file prevents direct browser access to JSON files (Apache)
- For Nginx, add a location rule to deny access to the `data/` directory
- Always change `TOKEN_SECRET` in production
- Change the default admin password immediately

---

## 🧪 Legal Disclaimer

> This platform is designed strictly for **authorized stress testing** purposes and **educational research**. You must own the target or have explicit permission to test it. The author is **not responsible for misuse**.

---

## 📄 License

MIT License © 2025 — [NetStress Team](#)
