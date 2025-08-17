<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
</head>
<body>
    <div class="error-page">
        <div class="error-background">
            <img src="https://images.unsplash.com/photo-1590674899484-d5640e854abe?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Background" class="bg-image">
            <div class="error-overlay"></div>
        </div>
        
        <div class="error-container">
            <div class="error-card glass-card">
                <div class="error-icon">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                
                <h1 class="error-title">404</h1>
                <h2 class="error-subtitle">Lost your way?</h2>
                <p class="error-message">The parking spot you're looking for doesn't exist or has been moved.</p>
                
                <div class="error-actions">
                    <a href="index.html" class="btn btn-primary">Go Home</a>
                    <a href="search.php" class="btn btn-ghost">Find Parking</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add animation to the error page
        document.addEventListener('DOMContentLoaded', function() {
            const errorCard = document.querySelector('.error-card');
            const errorIcon = document.querySelector('.error-icon svg');
            
            // Animate card entrance
            errorCard.style.opacity = '0';
            errorCard.style.transform = 'translateY(50px) scale(0.9)';
            errorCard.style.transition = 'all 0.8s ease';
            
            setTimeout(() => {
                errorCard.style.opacity = '1';
                errorCard.style.transform = 'translateY(0) scale(1)';
            }, 100);
            
            // Animate icon
            errorIcon.style.animation = 'bounce 2s infinite';
        });
        
        // Add bounce animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                60% {
                    transform: translateY(-5px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>