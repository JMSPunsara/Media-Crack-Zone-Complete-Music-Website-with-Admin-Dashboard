<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password, is_admin, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['full_name'] = $user['full_name'];
            
            redirect(SITE_URL);
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Login';
include 'includes/header.php';
?>

<style>
/* Professional Login Page Styling */
.min-vh-100 {
    background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
    position: relative;
    overflow: hidden;
}

.min-vh-100::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="%23ffffff" opacity="0.05"/><circle cx="80" cy="40" r="1" fill="%23ffffff" opacity="0.03"/><circle cx="40" cy="80" r="1" fill="%23ffffff" opacity="0.04"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateX(0px) translateY(0px); }
    50% { transform: translateX(10px) translateY(-10px); }
}

.login-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
    position: relative;
    overflow: hidden;
}

.login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.logo-container {
    position: relative;
}

.music-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 
        0 20px 40px rgba(29, 185, 84, 0.3),
        0 0 0 8px rgba(29, 185, 84, 0.1);
    animation: pulse 3s ease-in-out infinite;
    position: relative;
}

.music-logo::before {
    content: '';
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border-radius: 50%;
    z-index: -1;
    opacity: 0.3;
    animation: rotate 10s linear infinite;
}

.music-logo i {
    font-size: 2rem;
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.login-header h1 {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.login-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    font-weight: 400;
}

.input-group-custom {
    position: relative;
    margin-bottom: 1.5rem;
}

.form-control-custom {
    width: 100%;
    padding: 1.25rem 1rem 1.25rem 3.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.form-control-custom::placeholder {
    color: rgba(255, 255, 255, 0.4);
    font-weight: 400;
}

.form-control-custom:focus {
    outline: none;
    border-color: #1db954;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 
        0 0 0 4px rgba(29, 185, 84, 0.2),
        0 8px 25px rgba(29, 185, 84, 0.15);
    transform: translateY(-2px);
}

.form-control-custom:focus + .form-label-custom {
    color: #1db954;
    transform: translateY(-1.5rem) scale(0.85);
}

.input-icon {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.1rem;
    z-index: 2;
    transition: color 0.3s ease;
}

.form-control-custom:focus ~ .input-icon {
    color: #1db954;
}

.form-label-custom {
    position: absolute;
    top: 1.25rem;
    left: 3.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
    font-weight: 500;
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: left center;
}

.password-toggle {
    position: absolute;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    font-size: 1.1rem;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.password-toggle:hover {
    color: #1db954;
    background: rgba(255, 255, 255, 0.05);
}

.btn-login {
    width: 100%;
    padding: 1.25rem 2rem;
    background: linear-gradient(135deg, #1db954, #1ed760);
    border: none;
    border-radius: 16px;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 
        0 8px 25px rgba(29, 185, 84, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.btn-login::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-login:hover::before {
    left: 100%;
}

.btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 
        0 15px 35px rgba(29, 185, 84, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.2) inset;
}

.btn-login:active {
    transform: translateY(-1px);
}

.btn-text {
    position: relative;
    z-index: 2;
}

.btn-loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.login-footer p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    font-weight: 500;
}

.register-link {
    color: #1db954;
    text-decoration: none;
    font-weight: 700;
    position: relative;
    transition: all 0.3s ease;
}

.register-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #1db954;
    transition: width 0.3s ease;
}

.register-link:hover {
    color: #1ed760;
}

.register-link:hover::after {
    width: 100%;
}

.alert {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    border-radius: 12px;
    color: #f8d7da;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
}

.alert i {
    color: #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
    .login-card {
        padding: 2rem 1.5rem;
        border-radius: 20px;
        margin: 1rem;
    }
    
    .login-header h1 {
        font-size: 2rem;
    }
    
    .music-logo {
        width: 60px;
        height: 60px;
    }
    
    .music-logo i {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .form-control-custom {
        padding: 1rem 0.75rem 1rem 3rem;
    }
    
    .input-icon {
        left: 1rem;
    }
    
    .form-label-custom {
        left: 3rem;
    }
    
    .btn-login {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }
}

/* Loading state */
.btn-login.loading .btn-text {
    opacity: 0;
}

.btn-login.loading .btn-loader {
    opacity: 1;
}
</style>

<script>
// Password toggle functionality
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Enhanced form handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.login-form');
    const inputs = document.querySelectorAll('.form-control-custom');
    const submitBtn = document.querySelector('.btn-login');
    
    // Handle input focus/blur for floating labels
    inputs.forEach(input => {
        const label = input.nextElementSibling;
        
        // Check if input has value on page load
        if (input.value.trim() !== '') {
            label.style.transform = 'translateY(-1.5rem) scale(0.85)';
            label.style.color = '#1db954';
        }
        
        input.addEventListener('focus', function() {
            label.style.transform = 'translateY(-1.5rem) scale(0.85)';
            label.style.color = '#1db954';
        });
        
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                label.style.transform = 'translateY(0) scale(1)';
                label.style.color = 'rgba(255, 255, 255, 0.6)';
            }
        });
        
        // Real-time validation
        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.style.borderColor = 'rgba(40, 167, 69, 0.5)';
            } else {
                this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
            }
        });
    });
    
    // Form submission with loading state
    form.addEventListener('submit', function() {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        // Re-enable after 3 seconds (fallback)
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }, 3000);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
            form.submit();
        }
    });
});
</script>

<div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-lg-5 col-md-7 col-sm-9">
        <div class="login-card">
            <div class="login-header text-center mb-5">
                <div class="logo-container mb-4">
                    <div class="music-logo">
                        <i class="fas fa-music"></i>
                    </div>
                </div>
                <h1 class="fw-bold mb-2">Welcome Back</h1>
                <p class="text-muted fs-6">Sign in to your account to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="input-group-custom mb-4">
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="text" class="form-control-custom" id="username" name="username" 
                           placeholder="Username or Email Address"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    <label for="username" class="form-label-custom">Username or Email</label>
                </div>
                
                <div class="input-group-custom mb-4">
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" class="form-control-custom" id="password" name="password" 
                           placeholder="Enter your password" required>
                    <label for="password" class="form-label-custom">Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn-login w-100 mb-4">
                    <span class="btn-text">Sign In</span>
                    <div class="btn-loader">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
            
            <div class="login-footer text-center">
                <p class="mb-0">New to our platform? 
                    <a href="register.php" class="register-link">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
