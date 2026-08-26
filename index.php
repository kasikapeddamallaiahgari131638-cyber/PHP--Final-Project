<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Home';

// Featured cases (first 3 published)
$stmt = $pdo->query("SELECT id, title, description, difficulty, crime_type, estimated_time
                      FROM cases WHERE status='published' ORDER BY id ASC LIMIT 3");
$featuredCases = $stmt->fetchAll();

// Quick stats for the "how it works" strip
$totalCases = $pdo->query("SELECT COUNT(*) c FROM cases WHERE status='published'")->fetch()['c'];
$totalUsers = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];

include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="pd-hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-7 pd-reveal">
        <span class="pd-tape">CASE FILE #001 &middot; UNSOLVED</span>
        <h1 class="mt-3 mb-3">Become the <span class="accent">Detective.</span><br>Solve the Case.</h1>
        <p class="lead">Step into fictional crime scenes, gather evidence, interrogate witnesses, interrogate suspects,
          and use pure logical deduction to name the culprit. Every clue matters. Every choice affects your score.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="<?= !empty($_SESSION['user_id']) ? 'cases/index.php' : 'register.php' ?>" class="btn btn-pd-solid btn-lg px-4">
            <i class="fa-solid fa-magnifying-glass-plus"></i> Start Investigation
          </a>
          <a href="login.php" class="btn btn-pd btn-lg px-4"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
          <a href="register.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-id-badge"></i> Register</a>
        </div>
        <div class="d-flex gap-4 mt-5">
          <div><div class="pd-stat"><?= (int)$totalCases ?></div><div class="text-muted small">Active Cases</div></div>
          <div><div class="pd-stat"><?= (int)$totalUsers ?></div><div class="text-muted small">Detectives Registered</div></div>
          <div><div class="pd-stat">100%</div><div class="text-muted small">Logic-Based</div></div>
        </div>
      </div>
      <div class="col-lg-5 pd-reveal">
        <div class="pd-glass p-4">
          <img src="assets/images/scene_default.svg" class="img-fluid rounded mb-3" alt="Crime scene">
          <p class="small text-muted mb-0"><i class="fa-solid fa-quote-left"></i> "The room told a story. Every detective's job is to read it correctly." </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURED CASES -->
<section id="cases" class="container py-5">
  <div class="text-center mb-5 pd-reveal">
    <div class="pd-eyebrow">Case Library</div>
    <h2 class="mt-2">Featured Investigations</h2>
    <p class="text-muted">A growing set of fictional mysteries, each with its own suspects, evidence, and solution.</p>
  </div>
  <div class="row g-4">
    <?php foreach ($featuredCases as $c): ?>
      <div class="col-md-4 pd-reveal">
        <div class="pd-card h-100 p-4">
          <span class="pd-badge diff-<?= htmlspecialchars($c['difficulty']) ?>"><?= htmlspecialchars($c['difficulty']) ?></span>
          <span class="pd-badge ms-1"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($c['crime_type']) ?></span>
          <h4 class="mt-3"><?= htmlspecialchars($c['title']) ?></h4>
          <p class="text-muted small"><?= htmlspecialchars($c['description']) ?></p>
          <div class="d-flex justify-content-between align-items-center mt-4">
            <span class="small text-muted"><i class="fa-regular fa-clock"></i> ~<?= (int)$c['estimated_time'] ?> min</span>
            <a href="<?= !empty($_SESSION['user_id']) ? 'cases/details.php?id=' . $c['id'] : 'login.php' ?>" class="btn btn-pd btn-sm">Investigate <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- FEATURES -->
<section id="features" class="container py-5">
  <div class="text-center mb-5 pd-reveal">
    <div class="pd-eyebrow">Game Features</div>
    <h2 class="mt-2">Everything a Detective Needs</h2>
  </div>
  <div class="row g-4">
    <?php
    $features = [
      ['fa-magnifying-glass', 'Crime Scene Investigation', 'Search interactive crime scenes for hidden evidence.'],
      ['fa-box-archive', 'Evidence Locker', 'Track, examine, and connect every item you find.'],
      ['fa-comments', 'Witness Interviews', 'Interrogate witnesses and catch contradictions.'],
      ['fa-user-secret', 'Suspect Profiles', 'Compare motives, alibis, and suspicion levels.'],
      ['fa-diagram-project', 'Deduction Board', 'Connect evidence, clues, and suspects logically.'],
      ['fa-gavel', 'Final Verdict', 'Submit your case and see if justice is served.'],
      ['fa-chart-line', 'Scoring System', 'Earn points for accuracy, thoroughness, and speed.'],
      ['fa-ranking-star', 'Leaderboard', 'Compete with other detectives for the top rank.'],
    ];
    foreach ($features as $f): ?>
      <div class="col-md-3 col-6 pd-reveal">
        <div class="pd-card h-100 p-4 text-center">
          <i class="fa-solid <?= $f[0] ?> fa-2x mb-3" style="color:var(--pd-accent)"></i>
          <h6><?= $f[1] ?></h6>
          <p class="small text-muted mb-0"><?= $f[2] ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="container py-5">
  <div class="text-center mb-5 pd-reveal">
    <div class="pd-eyebrow">How It Works</div>
    <h2 class="mt-2">Your Investigation Workflow</h2>
  </div>
  <div class="row g-3 text-center">
    <?php
    $steps = ['Register','Select a Case','Search the Scene','Collect Evidence','Interview Witnesses','Analyze Suspects','Deduce','Submit Verdict'];
    foreach ($steps as $i => $s): ?>
      <div class="col">
        <div class="pd-panel p-3 h-100 pd-reveal">
          <div class="pd-stat" style="font-size:1.4rem"><?= $i+1 ?></div>
          <div class="small mt-1"><?= $s ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container pb-5">
  <div class="pd-glass p-5 text-center pd-reveal">
    <h3>Ready to crack your first case?</h3>
    <p class="text-muted">Registration takes less than a minute. Your detective badge is waiting.</p>
    <a href="register.php" class="btn btn-pd-solid btn-lg mt-2"><i class="fa-solid fa-id-badge"></i> Register Now</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
