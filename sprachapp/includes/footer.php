<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>SprachApp</h5>
                <p>Deine Lernplattform für Vokabeln und Sprachübungen.</p>
            </div>
            <div class="col-md-3">
                <h5>Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Startseite</a></li>
                    <li><a href="browse_units.php">Einheiten</a></li>
                    <li><a href="leaderboard.php">Bestenliste</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Kontakt</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-envelope"></i> kontakt@sprachapp.de</li>
                    <li><i class="fas fa-phone"></i> +49 123 456789</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <p>&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
<?php if (isset($additionalScripts)): ?>
    <?= $additionalScripts ?>
<?php endif; ?>
</body>
</html>