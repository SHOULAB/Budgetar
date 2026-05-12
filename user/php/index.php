<?php
// index.php - Homepage
$_traw = json_decode(file_get_contents(__DIR__ . '/translate.json'), true) ?? [];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetar</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#14b8a6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="../../assets/image/logo.png">
    <script>
        // Redirect to login when running as an installed PWA
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            window.location.replace('login.php');
        }
    </script>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <img src="../../assets/image/logo.png" alt="Budgetar Logo" class="logo-img">
                <span class="logo-text">Budgetar</span>
            </div>
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-secondary" data-i18n="index.nav.login">Ieiet</a>
                <a href="register.php" class="btn btn-primary" data-i18n="index.nav.register">Reģistrēties</a>
            </div>
        </nav>

        <main class="hero">
            <div class="hero-content">
                <h1 class="hero-title">
                    <span data-i18n="index.hero.title">Pārskati savas finanses</span>
                    <span class="switching-text-wrapper">
                        <b class="word-placeholder gradient-text" data-i18n="index.hero.word1">vienkārši un efektīvi</b>
                        <span class="switching-text">
                            <b class="word gradient-text" data-i18n="index.hero.word1">vienkārši un efektīvi</b>
                            <b class="word gradient-text" data-i18n="index.hero.word2">gudri un pārskatāmi</b>
                            <b class="word gradient-text" data-i18n="index.hero.word3">ātri un droši</b>
                        </span>
                    </span>
                </h1>
                <p class="hero-description" data-i18n="index.hero.desc">
                    Budgetar palīdz tev sekot līdzi ienākumiem un izdevumiem, 
                    analizēt finanšu plūsmas un sasniegt savus finanšu mērķus.
                </p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-large btn-primary">
                        <span data-i18n="index.hero.btn.start">Sākt tagad</span>
                        <span class="arrow">→</span>
                    </a>
                    <a href="#features" class="btn btn-large btn-outline" data-i18n="index.hero.btn.more">
                        Uzzināt vairāk
                    </a>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="card-demo">
                    <div class="card-demo-header">
                        <div class="card-demo-title" data-i18n="index.demo.title">Šī mēneša pārskats</div>
                        <div class="card-demo-date">Decembris 2024</div>
                    </div>
                    <div class="card-demo-stats">
                        <div class="stat stat-income">
                            <div class="stat-label" data-i18n="reports.chart.label.income">Ienākumi</div>
                            <div class="stat-value">+€2,450</div>
                        </div>
                        <div class="stat stat-expense">
                            <div class="stat-label" data-i18n="reports.chart.label.expense">Izdevumi</div>
                            <div class="stat-value">-€1,680</div>
                        </div>
                        <div class="stat stat-balance">
                            <div class="stat-label" data-i18n="cal.stat.balance">Bilance</div>
                            <div class="stat-value">€770</div>
                        </div>
                    </div>
                    <div class="card-demo-chart">
                        <div class="chart-bar" style="height: 60%"></div>
                        <div class="chart-bar" style="height: 75%"></div>
                        <div class="chart-bar" style="height: 45%"></div>
                        <div class="chart-bar" style="height: 85%"></div>
                        <div class="chart-bar" style="height: 55%"></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ── Features Section ─────────────────────────────────────── -->
        <section class="features" id="features">
            <h2 class="section-title" data-i18n="index.features.title">Kāpēc izvēlēties Budgetar?</h2>
            <div class="features-grid">
                <div class="feature-row">
                    <div class="feature-row-icon feat-color-1"><i class="fa-solid fa-chart-pie"></i></div>
                    <div class="feature-row-text">
                        <h3 data-i18n="index.feat1.title">Detalizēti pārskati</h3>
                        <p data-i18n="index.feat1.desc">Vizuāli pārskati par ienākumiem un izdevumiem — pa kategorijām, mēnešiem un tendencēm.</p>
                    </div>
                </div>
                <div class="feature-row">
                    <div class="feature-row-icon feat-color-2"><i class="fa-solid fa-wallet"></i></div>
                    <div class="feature-row-text">
                        <h3 data-i18n="index.feat2.title">Budžetu pārvaldība</h3>
                        <p data-i18n="index.feat2.desc">Izveido budžetus katrai kategorijai un seko līdzi, cik daudz esi jau iztērējis.</p>
                    </div>
                </div>
                <div class="feature-row">
                    <div class="feature-row-icon feat-color-3"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="feature-row-text">
                        <h3 data-i18n="index.feat3.title">Kalendāra skats</h3>
                        <p data-i18n="index.feat3.desc">Redzi visas transakcijas kalendārā — vienkārši noskaidro, kur nauda aiziet katru dienu.</p>
                    </div>
                </div>
                <div class="feature-row">
                    <div class="feature-row-icon feat-color-4"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="feature-row-text">
                        <h3 data-i18n="index.feat4.title">Droši un privāti</h3>
                        <p data-i18n="index.feat4.desc">Tavi dati ir aizsargāti. Neviens cits nevar piekļūt taviem personīgajiem finanšu datiem.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About Section ────────────────────────────────────────── -->
        <section class="about-section">
            <h2 class="section-title" data-i18n="index.about.title">Kā tas darbojas?</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3 data-i18n="index.step1.title">Izveido kontu</h3>
                    <p data-i18n="index.step1.desc">Reģistrējies bez maksas 30 sekundēs. Nav nepieciešams kredītkarte vai abonements.</p>
                </div>
                <div class="step-connector"><i class="fa-solid fa-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3 data-i18n="index.step2.title">Pievieno transakcijas</h3>
                    <p data-i18n="index.step2.desc">Ievadi savus ienākumus un izdevumus pa kategorijām. Izveido savus personīgos budžetus.</p>
                </div>
                <div class="step-connector"><i class="fa-solid fa-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3 data-i18n="index.step3.title">Analizē un plāno</h3>
                    <p data-i18n="index.step3.desc">Redzi savas finanses skaidri — ar pārskatu diagrammām, kalendāru un budžeta izsekotāju.</p>
                </div>
            </div>
        </section>

        <!-- ── CTA Section ──────────────────────────────────────────── -->
        <section class="cta-section">
            <div class="cta-content">
                <h2 data-i18n="index.cta.title">Gatavs sākt kontrolēt savas finanses?</h2>
                <p data-i18n="index.cta.desc">Pievienojies Budgetar jau šodien — pilnīgi bez maksas.</p>
                <a href="register.php" class="btn btn-large btn-primary">
                    <span data-i18n="index.cta.btn">Izveidot kontu</span>
                    <span class="arrow">→</span>
                </a>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-content">
                <p class="footer-text">2025 Budgetar | Dagnis Janeks 4PT</p>
            </div>
        </footer>
    </div>
    
    <script>window._i18nData=<?php echo json_encode($_traw); ?>;window._i18nIsDefault=true;</script>
    <script src="../js/language.js"></script>
    <script src="../js/script.js"></script>
    <script src="../js/index.js"></script>
</body>
</html>