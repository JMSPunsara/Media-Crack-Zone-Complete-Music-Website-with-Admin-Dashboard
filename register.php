<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include 'includes/header.php';
?>

<style>
/* Professional Register Page Styling */
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
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23ffffff" opacity="0.04"/><circle cx="75" cy="75" r="1" fill="%23ffffff" opacity="0.03"/><circle cx="50" cy="10" r="1" fill="%23ffffff" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
    animation: float 25s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateX(0px) translateY(0px); }
    33% { transform: translateX(5px) translateY(-8px); }
    66% { transform: translateX(-3px) translateY(5px); }
}

.register-card {
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

.register-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.register-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><circle cx="10" cy="10" r="1" fill="%231db954" opacity="0.1"/><circle cx="50" cy="30" r="1" fill="%231ed760" opacity="0.08"/><circle cx="30" cy="50" r="1" fill="%231db954" opacity="0.06"/></svg>') repeat;
    pointer-events: none;
    opacity: 0.3;
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
    animation: rotate 12s linear infinite;
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

.register-header h1 {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.register-header p {
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
    top: 1.25rem;
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

.form-help {
    position: absolute;
    bottom: -1.5rem;
    left: 0;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.5);
    font-weight: 400;
}

.password-strength {
    position: absolute;
    bottom: -2.5rem;
    left: 0;
    right: 0;
}

.strength-bar {
    width: 100%;
    height: 3px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.password-match {
    position: absolute;
    bottom: -1.5rem;
    left: 0;
    font-size: 0.75rem;
    font-weight: 500;
}

.btn-register {
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

.btn-register::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-register:hover::before {
    left: 100%;
}

.btn-register:hover {
    transform: translateY(-3px);
    box-shadow: 
        0 15px 35px rgba(29, 185, 84, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.2) inset;
}

.btn-register:active {
    transform: translateY(-1px);
}

.btn-register:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
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

.register-footer p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    font-weight: 500;
}

.login-link {
    color: #1db954;
    text-decoration: none;
    font-weight: 700;
    position: relative;
    transition: all 0.3s ease;
}

.login-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #1db954;
    transition: width 0.3s ease;
}

.login-link:hover {
    color: #1ed760;
}

.login-link:hover::after {
    width: 100%;
}

.alert {
    background: rgba(40, 167, 69, 0.15);
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 16px;
    color: #d4edda;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    text-align: center;
}

.alert.alert-danger {
    background: rgba(220, 53, 69, 0.15);
    border-color: rgba(220, 53, 69, 0.3);
    color: #f8d7da;
}

.success-icon {
    font-size: 3rem;
    color: #28a745;
    animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.btn-success-action {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-success-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    color: white;
}

/* Form validation states */
.form-control-custom.valid {
    border-color: #28a745;
    background: rgba(40, 167, 69, 0.1);
}

.form-control-custom.invalid {
    border-color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .register-card {
        padding: 2rem 1.5rem;
        border-radius: 20px;
        margin: 1rem;
    }
    
    .register-header h1 {
        font-size: 2rem;
    }
    
    .music-logo {
        width: 60px;
        height: 60px;
    }
    
    .music-logo i {
        font-size: 1.5rem;
    }
    
    .row .col-md-6 {
        margin-bottom: 1rem;
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
    
    .btn-register {
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }
}

/* Loading state */
.btn-register.loading .btn-text {
    opacity: 0;
}

.btn-register.loading .btn-loader {
    opacity: 1;
}
</style>

<script>
// Enhanced form handling and validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    const inputs = document.querySelectorAll('.form-control-custom');
    const submitBtn = document.querySelector('.btn-register');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    // Password strength indicator
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatch = document.getElementById('passwordMatch');
    
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
            validateField(this);
        });
    });
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strength);
        checkPasswordMatch();
    });
    
    // Password confirmation checker
    confirmPasswordInput.addEventListener('input', function() {
        checkPasswordMatch();
    });
    
    function validateField(input) {
        const value = input.value.trim();
        let isValid = true;
        
        switch(input.type) {
            case 'email':
                isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                break;
            case 'text':
                if (input.name === 'username') {
                    isValid = value.length >= 3;
                } else {
                    isValid = value.length > 0;
                }
                break;
            case 'password':
                isValid = value.length >= 6;
                break;
        }
        
        input.classList.toggle('valid', isValid && value.length > 0);
        input.classList.toggle('invalid', !isValid && value.length > 0);
        
        return isValid;
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        return Math.min(strength, 100);
    }
    
    function updatePasswordStrength(strength) {
        strengthFill.style.width = strength + '%';
        
        if (strength === 0) {
            strengthFill.style.background = 'transparent';
            strengthText.textContent = 'Enter password';
            strengthText.style.color = 'rgba(255, 255, 255, 0.6)';
        } else if (strength < 50) {
            strengthFill.style.background = 'linear-gradient(90deg, #dc3545, #fd7e14)';
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#dc3545';
        } else if (strength < 75) {
            strengthFill.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
            strengthText.textContent = 'Fair password';
            strengthText.style.color = '#ffc107';
        } else {
            strengthFill.style.background = 'linear-gradient(90deg, #28a745, #20c997)';
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#28a745';
        }
    }
    
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length === 0) {
            passwordMatch.textContent = '';
            return;
        }
        
        if (password === confirmPassword) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.style.color = '#28a745';
            confirmPasswordInput.classList.add('valid');
            confirmPasswordInput.classList.remove('invalid');
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.style.color = '#dc3545';
            confirmPasswordInput.classList.add('invalid');
            confirmPasswordInput.classList.remove('valid');
        }
    }
    
    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        // Validate all fields
        let allValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                allValid = false;
            }
        });
        
        // Check password match
        if (passwordInput.value !== confirmPasswordInput.value) {
            allValid = false;
        }
        
        if (allValid) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Re-enable after 5 seconds (fallback)
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }, 5000);
        } else {
            e.preventDefault();
        }
    });
    
    // Auto-save form data to localStorage
    inputs.forEach(input => {
        // Load saved values (except passwords)
        if (input.type !== 'password') {
            const savedValue = localStorage.getItem(`register_${input.name}`);
            if (savedValue && !input.value) {
                input.value = savedValue;
                const label = input.nextElementSibling;
                label.style.transform = 'translateY(-1.5rem) scale(0.85)';
                label.style.color = '#1db954';
            }
        }
        
        // Save on change (except passwords)
        input.addEventListener('input', function() {
            if (this.type !== 'password') {
                localStorage.setItem(`register_${this.name}`, this.value);
            }
        });
    });
    
    // Clear localStorage on successful submit
    form.addEventListener('submit', function() {
        if (this.checkValidity()) {
            inputs.forEach(input => {
                if (input.type !== 'password') {
                    localStorage.removeItem(`register_${input.name}`);
                }
            });
        }
    });
});
</script>

<div class="row justify-content-center align-items-center min-vh-100">
    <div class="col-lg-6 col-md-8 col-sm-10">
        <div class="register-card">
            <div class="register-header text-center mb-5">
                <div class="logo-container mb-4">
                    <div class="music-logo">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
                <h1 class="fw-bold mb-2">Join Our Platform</h1>
                <p class="text-muted fs-6">Create your account and discover amazing music</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <div class="success-icon mb-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5 class="mb-2">Welcome Aboard!</h5>
                    <p class="mb-3"><?php echo $success; ?></p>
                    <a href="login.php" class="btn-success-action">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In Now
                    </a>
                </div>
            <?php else: ?>
            
            <form method="POST" action="" class="register-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group-custom mb-4">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" class="form-control-custom" id="full_name" name="full_name" 
                                   placeholder="Enter your full name"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                            <label for="full_name" class="form-label-custom">Full Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-custom mb-4">
                            <div class="input-icon">
                                <i class="fas fa-at"></i>
                            </div>
                            <input type="text" class="form-control-custom" id="username" name="username" 
                                   placeholder="Choose a username"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            <label for="username" class="form-label-custom">Username</label>
                            <div class="form-help">Min. 3 characters</div>
                        </div>
                    </div>
                </div>
                
                <div class="input-group-custom mb-4">
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <input type="email" class="form-control-custom" id="email" name="email" 
                           placeholder="your.email@example.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <label for="email" class="form-label-custom">Email Address</label>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group-custom mb-4">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="form-control-custom" id="password" name="password" 
                                   placeholder="Create a strong password" required>
                            <label for="password" class="form-label-custom">Password</label>
                            <div class="form-help">Min. 6 characters</div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-text" id="strengthText">Enter password</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-custom mb-4">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="form-control-custom" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                            <label for="confirm_password" class="form-label-custom">Confirm Password</label>
                            <div class="password-match" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-register w-100 mb-4" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </span>
                    <div class="btn-loader">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
            
            <?php endif; ?>
            
            <div class="register-footer text-center">
                <p class="mb-0">Already have an account? 
                    <a href="login.php" class="login-link">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
