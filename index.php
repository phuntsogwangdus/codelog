<?php

// Created by Phuntsog Wangdus
// https://wangdus.com/


// https://it-ladakh.com/
// Infotech Solutions Ladakh

date_default_timezone_set('Asia/Kolkata');
// Initialize SQLite database
try {
    $pdo = new PDO('sqlite:database.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create messages table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL
    )");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}



// Handle update (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    header('Content-Type: application/json');

    $id = intval($_POST['id'] ?? 0);
    $name    = trim(base64_decode($_POST['name'] ?? ''));
    $subject = trim(base64_decode($_POST['subject'] ?? ''));
    $message = trim(base64_decode($_POST['message'] ?? ''));

    if ($id && $name && $subject && $message) {
        $stmt = $pdo->prepare("UPDATE messages SET name=?, subject=?, message=? WHERE id=?");
        $stmt->execute([$name, $subject, $message, $id]);

        echo json_encode([
            'status' => 'success',
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'subject' => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
            'message' => renderMessage($message)
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }

    exit; // 🔴 VERY IMPORTANT
}


// Handle form submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $now = date('Y-m-d H:i:s');

    // Decode Base64 encoded fields
    $name    = trim(base64_decode($_POST['name'] ?? ''));
    $subject = trim(base64_decode($_POST['subject'] ?? ''));
    $message = trim(base64_decode($_POST['message'] ?? ''));

    if (empty($name) || empty($subject) || empty($message)) {
        $error = 'All fields are required!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (name, subject, message, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $subject, $message, $now]);
            $success = 'Message logged successfully!';
        } catch (PDOException $e) {
            $error = 'Error saving message: ' . $e->getMessage();
        }
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $ids = $_POST['message_ids'] ?? [];
    if (!empty($ids)) {
        try {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $success = count($ids) . ' message(s) deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Error deleting messages: ' . $e->getMessage();
        }
    }
}





// Handle search
$search = $_GET['search'] ?? '';
$write_mode = isset($_GET['write']) ? true : false;

// Fetch messages
try {
    if ($search) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE name LIKE ? OR subject LIKE ? OR message LIKE ? ORDER BY created_at DESC");
        $searchTerm = "%$search%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
    }
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $messages = [];
    $error = 'Error fetching messages: ' . $e->getMessage();
}



function renderMessage($text)
{
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Handle explicit fenced blocks ```lang\ncode```
    $escaped = preg_replace_callback(
        '/```(\w+)?\n(.*?)```/s',
        function ($matches) {
            $lang = !empty($matches[1]) ? ' class="language-' . htmlspecialchars($matches[1]) . '"' : '';
            return '<pre><code' . $lang . '>' . $matches[2] . '</code></pre>';
        },
        $escaped
    );

    // Handle inline `code`
    $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);

    // Auto-detect: if message looks like code (has indentation + braces/semicolons)
    // and has no fences, wrap the whole thing
    $isCode = (
        substr_count($escaped, "\n") > 2 &&
        (
            preg_match('/^\s{2,}/m', $escaped) || // indented lines
            strpos($escaped, '{') !== false
        ) &&
        (
            strpos($escaped, ';') !== false ||
            strpos($escaped, '()') !== false ||
            strpos($escaped, '//') !== false ||
            strpos($escaped, 'function') !== false ||
            strpos($escaped, 'const ') !== false ||
            strpos($escaped, 'var ') !== false
        )
    );

    // Only auto-wrap if no pre block was already created
    if ($isCode && strpos($escaped, '<pre>') === false) {
        $escaped = '<pre><code>' . $escaped . '</code></pre>';
    } else {
        // Plain text — preserve line breaks
        $escaped = '<div style="white-space: pre-line; line-height: 1.4;">' . $escaped . '</div>';
    }

    return $escaped;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Logger</title>
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --radius: 12px;
    --gap: 16px;
    --soft: #f3f4f6;
    --border: #e5e7eb;
    --text: #111827;
}

body {
    font-family: 'Montserrat';
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    padding: 24px;
}

.container {
    max-width: 1360px;
    margin: auto;
}


.header {
    text-align: center;
    color: white;
    margin-bottom: 15px;
	font-family: 'Gentium Plus';
}

.header h1 {
    font-size: 2.3rem;
    margin-bottom: 6px;
	text-align: left;
	
}

.header p {
    opacity: 0.9;
}

        
        .mode-toggle {
            display: flex;
            justify-content: right;
            gap: 15px;
            margin-bottom: 7px;
        }

        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
		
.form-group {
    margin-bottom: 18px;
}

label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
    color: #374151;
}

input[type="text"],
textarea {
    width: 100%;
    padding: 14px 14px;
    font-size: 15px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--soft);
    outline: none;
}

input:focus,
textarea:focus {
    border-color: #4f46e5;
    background: white;
}

textarea {
    min-height: 160px;
    resize: vertical;
}
		
		


        

        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
        }
        
        .delete-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #333;
        }
        
		
		
		
		
.message-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message-item {
    background: #f9fafb;
    border-radius: 5px;
    padding: 5px;
    display: flex;
    gap: 12px;
    border-left: 2px solid #4f464f;
    transition: 0.2s;
	box-shadow: rgba(0, 0, 0, 0.15) 1.95px 1.95px 2.6px;
}

.message-item:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}


.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
}



.message-subject {
    font-weight: 600;
    margin-bottom: 4px;
}



        
        .message-checkbox {
            margin-top: 5px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .message-content {
            flex: 1;
            min-width: 0;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-name {
			color: #333;
			font-family: Ruthie;
			font-size: 23px;
			border-bottom: 1px solid #a7a7a7;
			padding-bottom: 3px;
        }
        
        .message-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .message-subject {
            font-weight: 600;
            color: #333;
            margin-bottom: -10px;
            font-size: 1.05em;
			letter-spacing: 0.6px;
			font-family: trirong;
        }
        
        .message-text {
			margin:1em;
            color: #49549a;
            line-height: 1.5;
            white-space: pre-wrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        /* Modal/Theatre Mode */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            position: relative;
            background: white;
            margin: 2% auto;
            padding: 0;
            width: 90%;
            max-width: 1280px;
            max-height: 90vh;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: slideIn 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 10px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .close {
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            line-height: 1;
        }
        
        .close:hover {
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

		
        
        .modal-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .modal-name {
            font-size: 1.2em;
            font-weight: 700;
            color: #667eea;
        }
        
        .modal-date {
            color: #666;
            font-size: 0.95em;
        }
        
        .modal-subject {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin: 16px 0 10px;
        }
        
        .modal-text {
            color: #555;
            line-height: 1.8;
            white-space: pre-wrap;
            font-size: 1.05em;
			padding:10px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .message-count {
            text-align: left;
            color: #555;
            margin-bottom: 20px;
            font-size: 1.1em;
			
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .mode-toggle {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
                max-height: 95vh;
            }
			
			 .card {
            padding: 15px;
			}
        }
		
	pre {
    background: #0d1117;
    border-radius: 8px;
    padding: 16px;
    overflow-x: auto;
    margin: 10px 0;
    margin: 0 -28px 0 -28px;
}

pre code {
    font-family: 'Fira Code', 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.6;
}

code:not(pre code) {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    color: #d63384;
}	

.dlog_edit button{
	transition:transform 0.3s ease-in-out;
}

.dlog_edit button:hover{
    transform: scale(1.1);
}

    </style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Gentium+Plus&family=Montserrat:wght@600&family=Oswald:wght@200;300;400&family=Roboto:ital,wght@0,100;0,300;0,400;1,100&family=Trirong&family=Ruthie&family=Alumni+Sans:wght@200;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Message Logger</h1>
            <p style="display:none;">Log and track your messages with ease</p>
        </div>
        
        <div class="mode-toggle">
            <a href="?" class="btn <?= !$write_mode ? 'btn-primary' : 'btn-secondary' ?>">
                👁️ View Messages 
            </a>
            <a href="?write=1" class="btn <?= $write_mode ? 'btn-primary' : 'btn-secondary' ?>">
                ✍️ Create Message
            </a>
        </div>
        
        <?php if ($write_mode): ?>
            <div class="card">
                <h2 style="margin-bottom: 20px; color: #333;">Create New Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required placeholder="Enter your name">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required placeholder="Enter message subject">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required placeholder="Enter your message here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        📤 Submit Message
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <h2 style="margin-bottom: 20px; color: #333;">View Messages</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="search-box">
                    <form method="GET" action="">
                        <input type="hidden" name="view" value="1">
                        <input type="text" name="search" placeholder="🔍 Search by name, subject, or message..." value="<?= htmlspecialchars($search) ?>">
                    </form>
                </div>
                
                <?php if (count($messages) > 0): ?>
                    <form method="POST" action="?view=1" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        
                        <div class="delete-controls">
                            <label class="checkbox-label">
                                <input type="checkbox" id="selectAll">
                                <span>Select All</span>
                            </label>
                            <button type="submit" class="btn btn-danger" id="deleteBtn" disabled>
                                🗑️ Delete Selected
                            </button>
                        </div>
                        
                        <div class="message-count">
                            📊 Total Messages: <strong><?= count($messages) ?></strong>
                        </div>
                        
                        <div class="message-list">
                            <?php foreach ($messages as $msg): ?>
                                <div id="message-item-<?= $msg['id']?>" class="message-item" onclick="openModal(<?= intval($msg['id']) ?>)">
                                    <input type="checkbox" 
                                           class="message-checkbox" 
                                           name="message_ids[]" 
                                           value="<?= $msg['id'] ?>"
                                           onclick="event.stopPropagation(); updateDeleteButton();">
                                    <div class="message-content">
                                        <div class="message-header">
                                            <span class="message-name"><?= htmlspecialchars($msg['name']) ?></span>
                                            <span class="message-date">
                                                🕐 <?= date('M d, Y - g:i A', strtotime($msg['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="message-subject">
                                            📌 <?= htmlspecialchars($msg['subject']) ?>
                                        </div>
                                        <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                                    </div>
                                </div>
                                
                                <!-- Modal for this message -->
                                <div id="modal-<?= $msg['id'] ?>" class="modal">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2>Message Details</h2>
                                            <span class="close" onclick="closeModal(<?= intval($msg['id']) ?>)">&times;</span>
                                        </div>
                                        <div class="modal-body">
                                            <div class="modal-info">
                                                <div class="modal-name"><?= htmlspecialchars($msg['name']) ?></div>
                                                <div class="modal-date">
                                                    🕐 <?= date('M d, Y - g:i A', strtotime($msg['created_at'])) ?>
                                                </div>
												
                                            </div>
											<button 
												type="button"
												onclick="event.stopPropagation(); enableEdit(<?= $msg['id'] ?>);"
												style="float:right;padding:8px 14px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;">
												✏️ Edit
											</button>
                                            <div class="modal-subject">
                                                📌 <?= htmlspecialchars($msg['subject']) ?>
                                            </div>
                                            
											
											
											<div id="view-area-<?= $msg['id'] ?>">
											
											<div class="modal-text"><?= renderMessage($msg['message']) ?></div>

											
											</div>

						<div id="edit-area-<?= $msg['id'] ?>" class="dlog_edit" style="display:none;margin-top:15px;">
							<input type="text" id="edit-name-<?= $msg['id'] ?>" 
								value="<?= htmlspecialchars($msg['name']) ?>" 
								style="width:100%;margin-bottom:10px;padding:8px;border:1px solid #ccc;border-radius:4px;">

							<input type="text" id="edit-subject-<?= $msg['id'] ?>" 
								value="<?= htmlspecialchars($msg['subject']) ?>" 
								style="width:100%;margin-bottom:10px;padding:8px;border:1px solid #ccc;border-radius:4px;">

							<textarea id="edit-message-<?= $msg['id'] ?>" 
								style="width:100%;height:150px;margin-bottom:10px;padding:8px;border:1px solid #ccc;border-radius:4px;"><?= htmlspecialchars($msg['message']) ?></textarea>
<div id="update-msg-<?= $msg['id'] ?>" 
     style="display:none;margin-bottom:10px;padding:8px;background:#d4edda;color:#155724;border-radius:4px;">
</div>
							<button onclick="saveEdit(<?= $msg['id'] ?>)" 
								style="padding:8px 14px;background:#28a745;color:#fff;border:none;border-radius:4px;cursor:pointer;">
								💾 Save
							</button>

							<button onclick="cancelEdit(<?= $msg['id'] ?>)" 
								style="padding:8px 14px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;margin-left:8px;">
								❌ Cancel
							</button>
						</div>
											
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3>No Messages Found</h3>
                        <p><?= $search ? 'Try adjusting your search terms' : 'Start by creating your first message!' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <center><code> Created by Phuntsog Wangdus 🙂</code></center>
    <script>
        // Select all functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.message-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButton();
            });
        }
        
        // Update delete button state
        function updateDeleteButton() {
            const checkboxes = document.querySelectorAll('.message-checkbox:checked');
            const deleteBtn = document.getElementById('deleteBtn');
            if (deleteBtn) {
                deleteBtn.disabled = checkboxes.length === 0;
            }
        }
        
        // Confirm deletion
        const deleteForm = document.getElementById('deleteForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                const checkboxes = document.querySelectorAll('.message-checkbox:checked');
                if (checkboxes.length === 0) {
                    e.preventDefault();
                    return;
                }
                
                const count = checkboxes.length;
                if (!confirm(`Are you sure you want to delete ${count} message(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                }
            });
        }
        
        // Modal functions
function openModal(id) {
    const modal = document.getElementById('modal-' + id);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Highlight any code blocks inside this modal
        modal.querySelectorAll('pre code').forEach(el => {
            if (!el.classList.contains('hljs')) { // avoid re-highlighting
                hljs.highlightElement(el);
            }
        });
    }
}
        
        function closeModal(id) {
            const modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Close modal when clicking outside
/*  */
/*     const modals = document.querySelectorAll('.modal');

    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
}); */
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });


const createForm = document.querySelector('form input[name="action"][value="add"]')?.closest('form');

if (createForm) {
    createForm.addEventListener('submit', function(e) {

        const fields = ['name', 'subject', 'message'];

        fields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.value = btoa(unescape(encodeURIComponent(field.value)));
            }
        });

    });
}
    </script>



<script>
    // Configure highlight.js
    hljs.configure({
        ignoreUnescapedHTML: true
    });

    // Run on page load for all pre>code blocks
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('pre code .message-text').forEach(el => {
            hljs.highlightElement(el);
        });
    });
	
	
	
	
	
	
	// UPDATE Code
	
	function enableEdit(id) {
    document.getElementById('view-area-' + id).style.display = 'none';
    document.getElementById('edit-area-' + id).style.display = 'block';
}

function cancelEdit(id) {
    document.getElementById('edit-area-' + id).style.display = 'none';
    document.getElementById('view-area-' + id).style.display = 'block';
}

function saveEdit(id) {

    const name = document.getElementById('edit-name-' + id).value;
    const subject = document.getElementById('edit-subject-' + id).value;
    const message = document.getElementById('edit-message-' + id).value;

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('name', btoa(unescape(encodeURIComponent(name))));
    formData.append('subject', btoa(unescape(encodeURIComponent(subject))));
    formData.append('message', btoa(unescape(encodeURIComponent(message))));

    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === 'success') {

            // ✅ Update modal content instantly
            document.querySelector('#modal-' + id + ' .modal-name').innerHTML = data.name;
            document.querySelector('#modal-' + id + ' .modal-subject').innerHTML = "📌 " + data.subject;
            document.querySelector('#modal-' + id + ' .modal-text').innerHTML = data.message;

            // ✅ Show success message
            const msgBox = document.getElementById('update-msg-' + id);
            msgBox.innerHTML = "✅ Changes successfully updated!";
            msgBox.style.display = "block";

            // Hide edit area
            const editArea = document.getElementById('edit-area-' + id);
				editArea.style.opacity = "0.5";

				setTimeout(() => {
					cancelEdit(id);
					editArea.style.opacity = "1";
				}, 300);

            // Re-run syntax highlight if needed
            document.querySelectorAll('#modal-' + id + ' pre code').forEach(el => {
                hljs.highlightElement(el);
            });

// ✅ Update background list instantly
const listItem = document.getElementById('message-item-' + id);

if (listItem) {
    listItem.querySelector('.message-name').innerHTML = data.name;
    listItem.querySelector('.message-subject').innerHTML = "📌 " + data.subject;

    // For preview list, show plain text (not full renderMessage formatting)
    listItem.querySelector('.message-text').innerText =
        document.getElementById('edit-message-' + id).value;
}

        } else {
            alert('Update failed.');
        }
    });
}
	
	
	
	
</script>


</body>
</html>
