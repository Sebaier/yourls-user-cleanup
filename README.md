# 🧹 YOURLS User Cleanup Plugin

A YOURLS plugin that automatically removes old ShortURLs from selected user accounts.
It deletes all short links older than a defined number of weeks, created by specific users — helping you keep your YOURLS database clean and efficient.

---

## 📋 Overview

The **YOURLS User Cleanup Plugin** enables administrators to automatically or manually remove outdated ShortURLs created through the **multi-user mode** (provided by [AuthMgrPlus](https://github.com/joshp23/YOURLS-AuthMgrPlus)).
It’s especially useful in setups where temporary or automatically generated links — such as API calls, campaign URLs, or testing links — accumulate over time and need to be cleaned up regularly.

---

## ⚠️ Compatibility Notice

> **This plugin currently requires [AuthMgrPlus](https://github.com/joshp23/YOURLS-AuthMgrPlus) to function properly.**
>
> YOURLS doesn’t natively store the username of the user who created a ShortURL.
> AuthMgrPlus extends the YOURLS database with an additional `user` column that records the creator of each link.
> Without this column, user-specific cleanup isn’t possible, as YOURLS cannot associate links with individual users.

---

## 🧩 Features

* ✅ Select which users’ links should be deleted
* 🕒 Choose how old links must be before deletion (e.g., 1, 2, 4, or 8 weeks)
* 🧠 Safety check: deletion only runs if at least one user is selected
* 🗄️ Works with **MariaDB** and **MySQL**
* 🧰 Fully integrated into the YOURLS admin interface

---

## ⚙️ Installation

1. Download or clone the repository:

   ```bash
   git clone https://github.com/Sebaier/yourls-user-cleanup.git
   ```
2. Copy the plugin folder into your YOURLS installation:

   ```
   /user/plugins/yourls-user-cleanup/
   ```
3. Activate the plugin via the YOURLS admin panel (**Admin → Plugins**).
4. Make sure **AuthMgrPlus** is active and that user data is being stored in the `yourls_url` table under the `user` column.

---

## 🧭 Usage

1. Open the **User Cleanup** page in the YOURLS admin area.
2. Select:

   * The age threshold for links (via dropdown)
   * One or more users (via checkboxes)
3. Click **“Show Preview”** to review which links will be deleted.
4. Click **“Delete x Links Now”** to confirm and execute the cleanup.
5. The plugin will automatically remove all entries whose `timestamp` is older than the selected age.

---

## 🧰 Requirements

| Component          | Minimum Version |
| ------------------ | --------------- |
| YOURLS             | ≥ 1.9           |
| PHP                | ≥ 7.4           |
| MariaDB/MySQL      | ✅ Supported     |
| AuthMgrPlus Plugin | **Required**    |

---

## 🚀 Planned Features

* 🕓 Automatic scheduled cleanup via cron job
* 🔄 Operation without AuthMgrPlus (e.g., using custom meta fields)
* 🌐 Multi-language support

---

## 📄 License

Licensed under the [MIT License](LICENSE).

---

### 💡 Developer Notes

This plugin is ideal for administrators maintaining large multi-user YOURLS environments. It ensures database performance by preventing link bloat and simplifies maintenance for automated systems or temporary users. Contributions and pull requests are welcome — especially for extending compatibility beyond AuthMgrPlus or adding internationalization support.
