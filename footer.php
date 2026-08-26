<footer class="pd-footer">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-md-4">
        <h5 class="pd-brand"><i class="fa-solid fa-magnifying-glass"></i> PHANTOM <span>DETECTIVE</span></h5>
        <p class="text-muted small mt-3">A web-based crime investigation game where logic meets mystery. Collect evidence, interrogate witnesses, and deduce the truth.</p>
      </div>
      <div class="col-md-4">
        <h6 class="text-uppercase pd-footer-heading">Game</h6>
        <ul class="list-unstyled small">
          <li><a href="index.php#cases">Case Library</a></li>
          <li><a href="leaderboard.php">Leaderboard</a></li>
          <li><a href="register.php">Become a Detective</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="text-uppercase pd-footer-heading">Project</h6>
        <ul class="list-unstyled small">
          <li>BTech Final Year Project</li>
          <li>PHP 8 &middot; MySQL &middot; Bootstrap 5</li>
          <li><a href="admin/login.php">Admin Panel</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-center small text-muted mb-0">&copy; <?= date('Y') ?> Phantom Detective &mdash; Crime Investigation Game. All cases are fictional.</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $root ?>assets/js/main.js"></script>
</body>
</html>
