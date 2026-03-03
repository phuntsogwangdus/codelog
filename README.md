# 🧠✨ CodeLog – Smart Code & Message Logger

> A lightweight, elegant PHP-based message and code logging system with automatic syntax highlighting and live editing.

---

## 🚀 Overview

**CodeLog** is a simple yet powerful logging application that allows you to:

- Store messages and code snippets
- Automatically detect and highlight code
- Search through entries instantly
- Edit posts live without page reload
- Manage entries efficiently with bulk delete

Built with simplicity and usability in mind, CodeLog works perfectly as a:

- 🧑‍💻 Developer snippet vault  
- 📝 Personal knowledge logger  
- 📚 Mini documentation tool  
- 🛠 Quick debugging notebook  

---

## 🎯 Features

### 📝 Message & Code Logging
- Store name, subject, and message
- Automatic timestamping
- Base64 encoding support for safe input handling

---

### 🧠 Smart Code Detection
- Detects:
  - Indentation
  - Braces `{ }`
  - Semicolons
  - Functions
  - JavaScript / PHP patterns
- Automatically wraps detected code in `<pre><code>`
- Syntax highlighting via Highlight.js
- Supports fenced code blocks:

````markdown
```php
echo "Hello World";

---

### 🎨 Syntax Highlighting
Powered by:

- Highlight.js (GitHub Dark theme)
- Automatic highlighting inside modals
- Safe HTML rendering

---

### 🔍 Search Functionality
- Search by:
  - Name
  - Subject
  - Message content
- Real-time filtering from database

---

### ✏️ Live Inline Editing
- Edit posts directly inside modal
- AJAX-based update (no full page reload required logic)
- Instant database sync
- Cancel or save changes on the spot

---

### 🗑 Bulk Delete
- Select multiple entries
- "Select All" option
- Confirmation before deletion

---

### 🪟 Clean Modal UI
- Click message → opens full view modal
- Escape key closes modal
- Click outside to close
- Scroll lock while open

---

## 🏗 Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (PDO) |
| Database | SQLite |
| Frontend | HTML5 |
| Styling | Inline styles + minimal classes |
| Syntax Highlighting | Highlight.js |
| Encoding | Base64 (for safe submission handling) |
| AJAX | Fetch API |

---

## 🗄 Database Structure

SQLite table: `messages`

```sql
CREATE TABLE messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL
);
```

---

## 🔐 Security Considerations

- Uses `htmlspecialchars()` for output escaping
- PDO prepared statements prevent SQL injection
- Base64 encoding protects special characters during submission
- Controlled modal rendering prevents raw HTML injection

---

## ⚙️ Installation

1. Clone repository:

```bash
git clone https://github.com/phuntsogwangdus/codelog.git
```

2. Place inside your PHP server directory.

3. Ensure PHP has SQLite enabled.

4. Open in browser:

```
http://localhost/codelog
```

Database will auto-create on first run.

---

## 📂 Project Structure

```
codelog/
│
├── index.php
├── database.db (auto-generated)
└── README.md
```

---

## 💡 Future Improvements (Ideas)

- Dark/Light theme toggle
- Markdown support
- Tag system
- Export to JSON
- Version history tracking
- Authentication layer
- REST API mode

---

## 👨‍💻 Author
Phuntsog Wangdus
Built for developers who like clean tools without heavy frameworks.

---

## 📜 License

MIT License – Free to use, modify, and distribute.

---

## ⭐ Why CodeLog?

Because sometimes you just need:

- A simple tool  
- No frameworks  
- No bloat  
- No unnecessary complexity  

Just pure functionality.

---

> “Write it. Log it. Improve it.” 🚀
```

---