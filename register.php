<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Crash Hockey</title>
    
    <link rel="icon" type="image/png" href="https://images.crashmedia.ca/images/2026/01/18/logo.png">
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { 
            margin: 0; 
            height: 100vh; 
            display: flex; 
            background: #06080b; 
            font-family: 'Inter', sans-serif; 
            overflow: hidden; 
        }

        /* LEFT SIDE: HERO / BRANDING */
        .split-left {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(255, 77, 0, 0.1), rgba(6, 8, 11, 0.9)), url('https://images.unsplash.com/photo-1515703403366-26c9d3cf48b6?q=80&w=2574&auto=format&fit=crop'); 
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            padding: 40px;
            color: #fff;
        }
        
        .split-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .brand-content h1 {
            font-size: 3rem;
            font-weight: 900;
            margin: 10px 0;
            letter-spacing: -1px;
        }
        
        .brand-content p {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* RIGHT SIDE: REGISTRATION FORM */
        .split-right {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #06080b;
            padding: 40px;
            position: relative;
            overflow-y: auto; /* Allow scrolling for taller form */
        }

        .login-card {
            width: 100%;
            max-width: 420px; /* Slightly wider for 2-column inputs */
        }

        .input-box {
            background: #0d1116;
            border: 1px solid #1e293b;
            border-radius: 6px;
            padding: 10px 15px; /* Slightly tighter padding */
            margin-bottom: 12px;
            transition: 0.2s;
        }
        
        .input-box:focus-within {
            border-color: var(--neon);
            box-shadow: 0 0 0 2px rgba(255, 77, 0, 0.1);
        }

        .input-box label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 5px;
        }

        .input-box input, .input-box select {
            width: 100%;
            background: transparent;
            border: none;
            color: #fff;
            outline: none;
            font-size: 14px;
        }
        
        /* Dark mode date picker adjustment */
        .input-box input[type="date"] {
            color-scheme: dark;
        }

        /* Grid for First/Last Name */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* MOBILE RESPONSIVENESS */
        @media (max-width: 900px) {
            .split-left { display: none; }
            .split-right { flex: 1; padding: 20px; display:block; } /* Allow natural flow */
            .login-card { margin-top: 40px; }
        }
    </style>
</head>
<body>

    <div class="split-left">
        <div class="brand-content">
            <img src="https://images.crashmedia.ca/images/2026/01/18/logo.png" alt="Logo" style="height: 80px; margin-bottom: 20px;">
            <h1>JOIN THE <span style="color: var(--neon);">SQUAD</span></h1>
            <p>Start tracking your development, accessing video reviews, and managing your training schedule today.</p>
        </div>
        
        <div style="position: absolute; bottom: 30px; z-index: 2; font-size: 12px; color: rgba(255,255,255,0.4);">
            &copy; <?php echo date('Y'); ?> Crash Hockey Performance.
        </div>
    </div>

    <div class="split-right">
        <div class="login-card">
            
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 24px; color: #fff; margin-bottom: 5px;">Create Account</h2>
                <p style="color: #64748b; font-size: 14px; margin: 0;">Fill in your details to get started.</p>
            </div>

            <?php if(isset($_GET['error']) && $_GET['error'] == 'email_taken'): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-exclamation"></i> Email address is already in use.
                </div>
            <?php endif; ?>

            <form action="process_register.php" method="POST">
                
                <div class="form-row">
                    <div class="input-box">
                        <label>First Name</label>
                        <input type="text" name="first_name" required placeholder="John">
                    </div>
                    <div class="input-box">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required placeholder="Doe">
                    </div>
                </div>

                <div class="input-box">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com">
                </div>

                <div class="form-row">
                    <div class="input-box">
                        <label>Position</label>
                        <select name="position" required>
                            <option value="Forward">Forward</option>
                            <option value="Defense">Defense</option>
                            <option value="Goalie">Goalie</option>
                        </select>
                    </div>
                    <div class="input-box">
                        <label>Date of Birth</label>
                        <input type="date" name="birth_date" required>
                    </div>
                </div>

                <div class="input-box">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a strong password" minlength="6">
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 14px; border: none; cursor: pointer; border-radius: 6px; font-weight: 700; letter-spacing: 0.5px; margin-top: 10px;">CREATE ACCOUNT</button>
            
            </form>

            <div style="margin-top: 25px; text-align: center; font-size: 13px; color: #64748b;">
                Already have an account? <a href="login.php" style="color: #fff; text-decoration: none; font-weight: 700;">Sign In</a>
            </div>

        </div>
    </div>

</body>
</html>