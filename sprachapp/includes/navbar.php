<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-language"></i> SprachApp
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home"></i> Startseite
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'browse_units.php' ? 'active' : '' ?>" href="browse_units.php">
                        <i class="fas fa-book"></i> Einheiten
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : '' ?>" href="leaderboard.php">
                        <i class="fas fa-trophy"></i> Bestenliste
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>" href="admin.php">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['username'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                       data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= h($_SESSION['username']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="my_units.php"><i class="fas fa-folder"></i> Meine Einheiten</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>" href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Anmelden
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>" href="register.php">
                        <i class="fas fa-user-plus"></i> Registrieren
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>