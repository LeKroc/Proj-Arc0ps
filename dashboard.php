<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopMe Dashboard</title>
    
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">

    <link rel="stylesheet" href="style-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<link rel="icon"
<body>
    ```

    <div class="dashboard-container">
        
        <main class="main-content">
            <header class="header">
                <div class="welcome-section">
                    <h1>Welcome back, Anish 👋</h1>
                    <p class="time">Time: <span id="time-display">--:--</span></p>
                </div>
            </header>

            <div class="stats-container">
                <div class="card">
                    <div class="icon-wrapper">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="card-info">
                        <span class="number">5</span>
                        <span class="label">Products</span>
                    </div>
                </div>

                <div class="card">
                    <div class="icon-wrapper">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <span class="number">5</span>
                        <span class="label">Users</span>
                    </div>
                </div>

                <div class="card">
                    <div class="icon-wrapper">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="card-info">
                        <span class="number">5</span>
                        <span class="label">Orders</span>
                    </div>
                </div>
            </div>
        </main>

        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i> ShopMe
            </div>

            <div class="user-profile">
                <div class="avatar"></div>
                <h3>Admin name</h3>
            </div>

            <nav class="menu">
                <a href="#" class="menu-item active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-clipboard-list"></i> Orders
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i> Account
                </a>
            </nav>

            <div class="footer">
                © ShopMe Corporation
            </div>
        </aside>

    </div>

</body>
</html>