<?php
/**
 * Login Page - Access Code Authentication
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect('courses.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'داخوازیا خەلەت. هیڤیە دوبارە هەوڵ بدەڤە.';
    } else {
        $code = trim($_POST['access_code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($code)) {
            $error = 'هیڤیە کۆدێ خۆ یێ دەستپێگەهشتنێ بنڤیسە.';
        } elseif (empty($name)) {
            $error = 'هیڤیە ناڤێ خۆ بنڤیسە.';
        } else {
            $result = Auth::loginWithCode($code, $name, $email);
            
            if ($result['success']) {
                redirect('courses.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

Security::setSecurityHeaders();
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <meta name="description" content="Login to access the course. Enter your access code and name to continue.">
    <meta name="keywords" content="login, access code, course">

    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background-color: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: right;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            position: relative;
        }
       
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .modal-header h2 {
            color: var(--text-dark);
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid #ddd; /* Fallback or specific color */
            padding-bottom: 0.5rem;
        }

        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-body p {
            margin-bottom: 1rem;
            color: var(--text-dark);
            font-weight: bold;
        }


        .modal-body li {
            margin-bottom: 1rem;
            color: var(--text-dark);
            line-height: 1.6;
            text-align: justify;
        }

        

        .modal-footer {
            margin-top: 1.5rem;
            text-align: left;
            border-top: 1px solid #ddd;
            padding-top: 1rem;
        }
        
        .modal-footer .btn {
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
        }

        .browser-icons {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .browser-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .browser-icon svg {
            width: 20px;
            height: 20px;
        }

        .browser-icon img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        /* Animated lock icon */
        .lock-animation {
            text-align: center;
            /* margin: 1rem 0; */
        }

        .lock-animation img {
            width: 150px;
            height: 150px;
            object-fit: contain;
        }
         @media screen and (max-width: 600px) {
            .modal-content {
                padding: 1rem;
            }
            .modal-body ul {
                padding-left: 0rem !important;
                list-style-type: none;
            }
             .modal-body p {
                font-size: 14px;
                text-align: justify;
            }
             .modal-body li {
                
           font-size: 14px;
            text-align: justify;
        }
        
        .modal-header h2 {
           
            font-size: 1rem;
          
        }
           .modal-footer .btn {
            padding: 0.3rem 1rem;
            font-size: 0.8rem;
        }

            
        }

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <!-- <div class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18V5l12-2v13"></path>
                        <circle cx="6" cy="18" r="3"></circle>
                        <circle cx="18" cy="16" r="3"></circle>
                    </svg>
                    <span>کۆرسێ دەنگی</span>
                </div> -->
                <!-- <h1>بخێرهاتی</h1> -->
                <div class="lock-animation">
                    <img src="assets/img/lockunlock_conv-2.gif" alt="Unlock">
                </div>
                <!-- <p>کۆدێ دەستپێگەهشتنا خۆ بنڤیسە بۆ بەردەوامبوونێ</p> -->
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label class="form-label" for="access_code">کۆدێ دەستپێگەهشتنێ *</label>
                    <input type="text" 
                           class="form-control" 
                           id="access_code" 
                           name="access_code" 
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           autocomplete="off"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="name">ناڤێ خۆ *</label>
                    <input type="text" 
                           class="form-control" 
                           id="name" 
                           name="name" 
                           placeholder="ناڤێ خۆ یێ دروست بنڤیسە"
                           autocomplete="off"
                           required>
                </div>
                
                <!-- <div class="form-group">
                    <label class="form-label" for="email">ئیمەیڵ (ئارەزوومەندانە)</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="email@example.com"
                           autocomplete="off">
                </div> -->
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    چوونا ژوور
                </button>
            </form>
            
            <!-- <p style="text-align: center; margin-top: 30px; color: var(--gray-500); font-size: 0.9rem;">
                کۆدێ دەستپێگەهشتنێ نینە؟ پەیوەندیێ ب ڕێڤەبەری بکە.
            </p> -->
        </div>
    </div>
    
    <!-- Important Note Modal -->
    <div id="noticeModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>تێبینیەکا گرنگ</h2>
            </div>
            <div class="modal-body">
                <p>سلاڤ بەرێز ژبەری دەست ب ڤەکرنا کۆرسی بکەی ڤان خالان جێبەجێ بکە :</p>
                <ul>
                    <li>
                        هیڤیە کۆرسێ خۆ لسەر بەرنامی Google Chrome یان ژی بەرنامێ Brave یان ژی بەرنامێ Firefox یان ژی بەرنامێ Safari ڤەکە ئەگەر تە نەبن دشێی داونلۆد بکەی و لسەر ڤەکەی.
                        <div class="browser-icons">
                            <span class="browser-icon">
                                <img src="assets/img/Google_Chrome_icon_(February_2022).svg.png" alt="Chrome">
                                <!-- Chrome -->
                            </span>
                            <span class="browser-icon">
                                <img src="assets/img/brave-logo-png_seeklogo-302583.png" alt="Brave">
                                <!-- Brave -->
                            </span>
                            <span class="browser-icon">
                                <img src="assets/img/Firefox_logo,_2019.svg.png" alt="Firefox">
                                <!-- Firefox -->
                            </span>
                            <span class="browser-icon">
                                <img src="assets/img/Safari_browser_logo.svg.png" alt="Safari">
                                <!-- Safari -->
                            </span>
                        </div>
                    </li>
                    <li>خالا دووێ یا گرنگ کۆرس لسەر ئێک ئامیرە و جهـ ڤەدبیت واتە ئەو جهێ تۆ کورسێ خۆ لسەر ڤەدکەی ل وی جهی دشێی بەردەوامیێ بدەیە کۆرسێ خۆ.</li>
                    <li>خالا سیێ یا گرنگ ب هیچ رەنگەکێ براوسەرێ خۆ ئانکۆ ئەو جهێ تۆ کورسێ خۆ ڤەدکەی ئەوی براوسەری کلیر داتا Clear data نەکەی.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button id="closeModalBtn" class="btn btn-primary" type="button">تەمام</button>
            </div>
        </div>
    </div>
    
    <script>
                // Disable right-click
        document.addEventListener('contextmenu', e => e.preventDefault());
        
        // Disable keyboard shortcuts for DevTools
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) ||
                (e.ctrlKey && e.key === 'U')) {
                e.preventDefault();
            }
        });
        
        // Format access code input
        document.getElementById('access_code').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            let formatted = '';
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) formatted += '-';
                formatted += value[i];
            }
            e.target.value = formatted;
        });

        // Show modal on load
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('noticeModal').classList.add('active');
            }, 500);
        });

        // Close modal
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('noticeModal').classList.remove('active');
        });
    </script>
</body>
</html>
