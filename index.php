<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTB Login</title>
	
	
		 <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
	
	
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8A2BE2',
                        secondary: '#FF6B6B'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e91e63 0%, #5e35b1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            position: relative;
            overflow: hidden;
        }
        .bg-shape {
            position: absolute;
            z-index: 0;
        }
        .bg-shape-left {
            left: -0px;
            top: 50%;
            transform: translateY(-50%);
        }
        .bg-shape-right {
            right: -0px;
            top: 50%;
            transform: translateY(-50%);
        }
        .welcome-header {
            background: linear-gradient(135deg, #9c27b0 0%, #673ab7 100%);
        }
        .illustration-img {
            width: 100%;
            max-width: 18rem;
            max-height: 14rem;
            height: auto;
            object-fit: contain;
        }
        @media (max-width: 640px) {
            .illustration-img {
                max-width: 12rem;
                max-height: 12rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container bg-white shadow-xl rounded-lg w-full max-w-5xl mx-4 relative">
        <header class="flex justify-between items-center px-8 py-4 border-b">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white mr-3">
                   MTB
                </div>
                <h1 class="font-sans font-semibold text-gray-700">MTB Schools & Colleges</h1>
            </div>
        </header>

        <div class="flex min-h-[600px] relative">
            <div class="bg-shape bg-shape-left w-72 h-72 bg-blue-100 rounded-full opacity-70"></div>
            <div class="bg-shape bg-shape-right w-72 h-72 bg-blue-100 rounded-full opacity-70"></div>
            <div class="w-1/4 flex items-center justify-center relative z-10">
                <img src="images/left.png" class="illustration-img">
            </div>
            <div class="w-2/4 flex flex-col items-center justify-center relative z-10">
                <div class="w-full max-w-md login-form-container border border-gray-200 rounded-lg shadow-md">
                    <div class="welcome-header text-white text-center p-8 rounded-t-lg">
                        <h2 class="text-3xl font-bold mb-4">WELCOME</h2>
                        <p class="text-sm opacity-90">We're excited to have you join our platform.</p>
                    </div>
                    <div class="bg-white p-8 rounded-b-lg">
                        <div class="mb-4 relative">
                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <div class="w-10 h-10 flex items-center justify-center text-gray-400">
                                    <i class="ri-user-line"></i>
                                </div>
                                <input type="number" id="student_id" placeholder="Enter Student ID" class="w-full h-10 px-2 outline-none text-sm">
                            </div>
                        </div>
                        <div id="error-message" class="text-red-500 text-sm text-center mb-4"></div>
                        <button type="button" onclick="handleLogin()" class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition-all whitespace-nowrap">LOGIN</button>
                    </div>
                </div>
            </div>
            <div class="w-1/4 flex items-center justify-center relative z-10">
                <img src="images/right.png" class="illustration-img">
            </div>
        </div>

        <footer class="text-center py-4 text-gray-500 text-sm">
            <p>© 2025 All rights reserved.</p>
        </footer>
    </div>

    <script>
        function handleLogin() {
            const studentId = document.getElementById('student_id').value.trim();
            const errorDiv = document.getElementById('error-message');

            if (!studentId) {
                errorDiv.textContent = 'Please enter your Student ID';
                return;
            }

            const csrfToken = Math.random().toString(36).substring(2);

            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ student_id: studentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorDiv.textContent = data.message || 'An error occurred. Please try again.';
                }
            })
            .catch(error => {
                errorDiv.textContent = 'Network error. Please try again.';
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
