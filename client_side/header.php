<!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent fixed-top" style="backdrop-filter: blur(0px); opacity: 1;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
                Sunrise
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#hotels">Hotels</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking_history.php">Membership</a>
                    </li>
                </ul>
                <form class="d-flex me-3">
                    <input class="form-control me-2" type="search" placeholder="Search here..." aria-label="Search">
                </form>
                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <span class="me-3" >Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                    <a href="login.php?logout=true" class="btn btn-outline-light rounded-pill">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-dark rounded-pill">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
