<?php
// =====================================================
// SOUND GROUP - Homepage
// index.php
// =====================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pdo       = getDB();
$pageTitle = 'Home';

// Latest music (ordered by latest year & popularity)
$musicLimit = (int)getSiteSetting('homepage_music_cnt', '8');
if ($musicLimit < 1) $musicLimit = 8;

$videoLimit = (int)getSiteSetting('homepage_video_cnt', '8');
if ($videoLimit < 1) $videoLimit = 8;

$latestMusic = $pdo->query("
    SELECT m.*, a.name AS artist_name
    FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    WHERE m.status = 'active'
    ORDER BY m.year DESC, m.play_count DESC
    LIMIT $musicLimit
")->fetchAll();

// Latest videos
$latestVideos = $pdo->query("
    SELECT v.*, a.name AS artist_name
    FROM videos v
    LEFT JOIN artists a ON v.artist_id = a.id
    WHERE v.status = 'active'
    ORDER BY v.year DESC, v.view_count DESC
    LIMIT $videoLimit
")->fetchAll();

// Trending media (by plays / views)
$trending = $pdo->query("
    SELECT m.id, m.title, m.cover_image, m.year, m.language, m.play_count AS play_count, a.name AS artist_name, 'music' AS type
    FROM music m LEFT JOIN artists a ON m.artist_id = a.id
    WHERE m.status='active'
    UNION ALL
    SELECT v.id, v.title, v.cover_image, v.year, v.language, v.view_count AS play_count, a.name AS artist_name, 'video' AS type
    FROM videos v LEFT JOIN artists a ON v.artist_id = a.id
    WHERE v.status='active'
    ORDER BY play_count DESC LIMIT 8
")->fetchAll();

// Albums (latest 4)
$albums = $pdo->query("
    SELECT al.*, a.name AS artist_name,
           COUNT(m.id) AS track_count
    FROM albums al
    LEFT JOIN artists a ON al.artist_id = a.id
    LEFT JOIN music m ON m.album_id = al.id
    GROUP BY al.id
    ORDER BY al.year DESC LIMIT 4
")->fetchAll();

// Artists (top 6)
$artists = $pdo->query("
    SELECT a.*,
           COUNT(m.id) AS song_count
    FROM artists a
    LEFT JOIN music m ON m.artist_id = a.id
    GROUP BY a.id
    ORDER BY song_count DESC LIMIT 6
")->fetchAll();

// Dynamically resolve exact IDs for featured slides so front matches inside 100%
$chaleyaId   = (int)($pdo->query("SELECT id FROM music WHERE title LIKE '%Chaleya%' LIMIT 1")->fetchColumn() ?: 10);
$antiHeroId  = (int)($pdo->query("SELECT id FROM music WHERE title LIKE '%Anti-Hero%' LIMIT 1")->fetchColumn() ?: 109);
$naatuVidId  = (int)($pdo->query("SELECT id FROM videos WHERE title LIKE '%Naatu%' LIMIT 1")->fetchColumn() ?: 1);
$pushpaVidId = (int)($pdo->query("SELECT id FROM videos WHERE title LIKE '%Pushpa 2%' LIMIT 1")->fetchColumn() ?: 9);

// Featured Slider Items (Mix of top music and videos with exact matched IDs)
$slides = [
    [
        'title' => 'Naatu Naatu',
        'subtitle' => 'Oscar Winning Sensation &bull; RRR OST',
        'desc' => 'Experience the global phenomenon beats by M.M. Keeravani with electrifying energetic dance visuals.',
        'tag' => '🔥 Blockbuster Video',
        'link' => SITE_URL . '/pages/video-detail.php?id=' . $naatuVidId,
        'image' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1600&auto=format&fit=crop&q=85',
        'badge' => 'OSCAR WINNER',
        'btn_text' => 'Watch Video Now'
    ],
    [
        'title' => 'Chaleya',
        'subtitle' => 'Arijit Singh &bull; Jawan Blockbuster',
        'desc' => 'The chart-topping romantic melody of the year featuring Shah Rukh Khan with soulful melodies.',
        'tag' => '🎵 Trending Song',
        'link' => SITE_URL . '/pages/music-detail.php?id=' . $chaleyaId,
        'image' => 'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=1600&auto=format&fit=crop&q=85',
        'badge' => 'TOP STREAM',
        'btn_text' => 'Listen Audio Track'
    ],
    [
        'title' => 'Anti-Hero',
        'subtitle' => 'Taylor Swift &bull; Midnights Album',
        'desc' => 'Billboard record-shattering pop anthem with captivating production and introspective lyrics.',
        'tag' => '🌟 Global Hit',
        'link' => SITE_URL . '/pages/music-detail.php?id=' . $antiHeroId,
        'image' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=1600&auto=format&fit=crop&q=85',
        'badge' => 'GLOBAL #1',
        'btn_text' => 'Stream Track'
    ],
    [
        'title' => 'Pushpa 2 - The Couple Song',
        'subtitle' => 'Allu Arjun & Rashmika Mandanna',
        'desc' => 'The most awaited cinematic visual celebration with groovy beats and colorful choreography.',
        'tag' => '🎬 New Release',
        'link' => SITE_URL . '/pages/video-detail.php?id=' . $pushpaVidId,
        'image' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600&auto=format&fit=crop&q=85',
        'badge' => 'NEW ARRIVAL',
        'btn_text' => 'Watch in HD'
    ]
];

include __DIR__ . '/includes/header.php';
?>

<!-- FEATURED CAROUSEL SLIDER -->
<style>
  .slider-section {
    position: relative;
    width: 100%;
    height: 82vh;
    min-height: 560px;
    overflow: hidden;
    background: #0a0b10;
  }
  .slider-container {
    position: relative;
    width: 100%;
    height: 100%;
  }
  .slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    visibility: hidden;
    display: flex;
    align-items: center;
    transition: opacity 0.7s ease, visibility 0.7s ease;
    z-index: 1;
  }
  .slide.active {
    opacity: 1;
    visibility: visible;
    z-index: 2;
  }
  .slide-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center 30%;
    transform: scale(1.06);
    transition: transform 6s cubic-bezier(0.2, 0.8, 0.2, 1);
    filter: brightness(0.6) saturate(1.2);
  }
  .slide.active .slide-bg {
    transform: scale(1);
  }
  .slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(10, 11, 16, 0.95) 0%, rgba(10, 11, 16, 0.65) 50%, rgba(10, 11, 16, 0.2) 100%),
                linear-gradient(0deg, rgba(10, 11, 16, 0.95) 0%, transparent 40%);
    z-index: 2;
  }
  .slide-content {
    position: relative;
    z-index: 3;
    max-width: 740px;
    padding: 0 4%;
  }
  .slide.active .slide-content {
    animation: slideInText 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  }
  @keyframes slideInText {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .slide-title {
    font-size: clamp(34px, 5.5vw, 62px);
    font-weight: 900;
    line-height: 1.15;
    margin: 12px 0;
    color: #fff;
    text-shadow: 0 4px 20px rgba(0,0,0,0.8);
  }
  .slide-subtitle {
    font-size: 17px;
    font-weight: 600;
    color: #ffb300;
    margin-bottom: 14px;
  }
  .slide-desc {
    font-size: 15px;
    color: #94a3b8;
    line-height: 1.7;
    margin-bottom: 28px;
    max-width: 600px;
  }
  .slide-badge-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .slide-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(255, 45, 85, 0.15);
    border: 1px solid rgba(255, 45, 85, 0.4);
    border-radius: 30px;
    font-size: 12px;
    color: #ff5e7e;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    backdrop-filter: blur(8px);
  }
  .slide-btns {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
  }
  .slider-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    background: rgba(21, 25, 38, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s;
    backdrop-filter: blur(8px);
  }
  .slider-btn:hover {
    background: #ff2d55;
    border-color: #ff2d55;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 0 20px rgba(255, 45, 85, 0.5);
  }
  .prev-btn { left: 30px; }
  .next-btn { right: 30px; }
  .slider-dots {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
  }
  .slider-dots .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    cursor: pointer;
    transition: all 0.3s ease;
  }
  .slider-dots .dot.active {
    background: #ff2d55;
    width: 32px;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(255, 45, 85, 0.5);
  }
  .slider-stats {
    position: absolute;
    right: 5%;
    bottom: 30px;
    z-index: 9;
    display: flex;
    gap: 16px;
  }
  @media (max-width: 992px) {
    .slider-stats, .prev-btn, .next-btn { display: none; }
  }
</style>

<section class="slider-section">
  <div class="slider-container" id="heroSlider">
    <?php foreach ($slides as $idx => $slide): ?>
    <div class="slide <?= $idx === 0 ? 'active' : '' ?>">
      <div class="slide-bg" style="background-image: url('<?= $slide['image'] ?>');"></div>
      <div class="slide-overlay"></div>
      <div class="container slide-content">
        <div class="slide-badge-wrap">
          <span class="slide-badge"><span class="pulse-dot"></span> <?= $slide['tag'] ?></span>
          <span class="badge-new"><?= $slide['badge'] ?></span>
        </div>
        <h1 class="slide-title"><?= clean($slide['title']) ?></h1>
        <p class="slide-subtitle"><i class="fas fa-sparkles"></i> <?= $slide['subtitle'] ?></p>
        <p class="slide-desc"><?= clean($slide['desc']) ?></p>
        <div class="slide-btns">
          <a href="<?= $slide['link'] ?>" class="btn btn-primary btn-lg"><i class="fas fa-play"></i> <?= $slide['btn_text'] ?></a>
          <a href="<?= SITE_URL ?>/pages/music.php" class="btn btn-outline btn-lg"><i class="fas fa-compass"></i> Explore All</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Slider Navigation Buttons -->
    <button class="slider-btn prev-btn" id="slidePrev" aria-label="Previous Slide"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-btn next-btn" id="slideNext" aria-label="Next Slide"><i class="fas fa-chevron-right"></i></button>

    <!-- Slider Pagination Dots -->
    <div class="slider-dots" id="sliderDots">
      <?php foreach ($slides as $idx => $s): ?>
      <span class="dot <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>"></span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- FLOATING STATS OVERLAY -->
  <div class="slider-stats">
    <?php
      $totalMusic  = $pdo->query("SELECT COUNT(*) FROM music  WHERE status='active'")->fetchColumn();
      $totalVideos = $pdo->query("SELECT COUNT(*) FROM videos WHERE status='active'")->fetchColumn();
      $totalArtists= $pdo->query("SELECT COUNT(*) FROM artists")->fetchColumn();
    ?>
    <div class="stat-card"><span class="stat-num"><?= number_format($totalMusic) ?>+</span><span class="stat-label">Tracks</span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($totalVideos) ?>+</span><span class="stat-label">Videos</span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($totalArtists) ?>+</span><span class="stat-label">Artists</span></div>
  </div>
</section>

<!-- LATEST MUSIC (5 listings) -->
<section class="content-section" id="latest-music">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-music"></i> Latest Music</h2>
      <a href="<?= SITE_URL ?>/pages/music.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="media-grid">
      <?php foreach ($latestMusic as $song): ?>
      <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/music-detail.php?id=<?= $song['id'] ?>'">
        <div class="card-thumb">
          <img src="<?= resolveMediaUrl($song['cover_image'], 'cover') ?>" alt="<?= clean($song['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
          <div class="card-overlay"><div class="play-circle"><i class="fas fa-play"></i></div></div>
          <?php if (isMediaNew($song)): ?><span class="badge-new">NEW</span><?php endif; ?>
          <span class="badge-type music">🎵 Music</span>
        </div>
        <div class="card-body">
          <h4><?= clean($song['title']) ?></h4>
          <p class="card-sub"><?= clean($song['artist_name'] ?? 'Unknown') ?></p>
          <div class="card-footer">
            <span class="card-lang"><?= clean($song['language']) ?></span>
            <span class="card-year"><?= $song['year'] ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- LATEST VIDEOS (5 listings) -->
<section class="content-section bg-dark2" id="latest-videos">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-video"></i> Latest Videos</h2>
      <a href="<?= SITE_URL ?>/pages/videos.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="media-grid">
      <?php foreach ($latestVideos as $vid): ?>
      <div class="media-card" onclick="location.href='<?= SITE_URL ?>/pages/video-detail.php?id=<?= $vid['id'] ?>'">
        <div class="card-thumb">
          <img src="<?= resolveMediaUrl($vid['cover_image'], 'cover') ?>" alt="<?= clean($vid['title']) ?>">
          <div class="card-overlay"><div class="play-circle"><i class="fas fa-play"></i></div></div>
          <?php if (isMediaNew($vid)): ?><span class="badge-new">NEW</span><?php endif; ?>
          <span class="badge-type video">🎬 Video</span>
        </div>
        <div class="card-body">
          <h4><?= clean($vid['title']) ?></h4>
          <p class="card-sub"><?= clean($vid['artist_name'] ?? 'Unknown') ?></p>
          <div class="card-footer">
            <span class="card-lang"><?= clean($vid['language']) ?></span>
            <span class="card-year"><?= $vid['year'] ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- BROWSE CATEGORIES -->
<section class="content-section" id="categories">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-layer-group"></i> Browse Categories</h2>
      <a href="<?= SITE_URL ?>/pages/music.php" class="view-all">Explore All Catalog <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="categories-grid">
      <?php
        $cats = [
          [
            'icon'  => 'fa-calendar-days',
            'label' => 'By Year',
            'desc'  => '1990 - 2024 Eras',
            'img'   => 'https://images.unsplash.com/photo-1539375665275-f9de415ef9ac?w=600&auto=format&fit=crop&q=80',
            'href'  => 'music.php?sort=year'
          ],
          [
            'icon'  => 'fa-microphone-lines',
            'label' => 'By Artist',
            'desc'  => 'Top Vocalists & Bands',
            'img'   => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600&auto=format&fit=crop&q=80',
            'href'  => 'artists.php'
          ],
          [
            'icon'  => 'fa-compact-disc',
            'label' => 'By Album',
            'desc'  => 'Full Soundtracks',
            'img'   => 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&auto=format&fit=crop&q=80',
            'href'  => 'albums.php'
          ],
          [
            'icon'  => 'fa-guitar',
            'label' => 'By Genre',
            'desc'  => 'Pop, Rock, Sufi & More',
            'img'   => 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600&auto=format&fit=crop&q=80',
            'href'  => 'music.php?sort=genre'
          ],
          [
            'icon'  => 'fa-globe',
            'label' => 'By Language',
            'desc'  => 'Regional, Hindi & English',
            'img'   => 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?w=600&auto=format&fit=crop&q=80',
            'href'  => 'music.php?sort=lang'
          ],
          [
            'icon'  => 'fa-fire-flame-curved',
            'label' => 'Trending Hits',
            'desc'  => 'Top Streamed Now',
            'img'   => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=600&auto=format&fit=crop&q=80',
            'href'  => 'music.php?sort=popular'
          ]
        ];
        foreach ($cats as $c):
      ?>
        <a href="<?= SITE_URL ?>/pages/<?= $c['href'] ?>" class="cat-card">
          <img src="<?= $c['img'] ?>" alt="<?= clean($c['label']) ?>" class="cat-card-bg" loading="lazy">
          <div class="cat-card-overlay"></div>
          <div class="cat-card-content">
            <span class="cat-card-badge"><i class="fas <?= $c['icon'] ?>"></i></span>
            <h4><?= clean($c['label']) ?></h4>
            <p><?= clean($c['desc']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Quick Genre & Language Pill Strip -->
    <div class="genre-quick-bar">
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Bollywood" class="genre-pill"><i class="fas fa-film"></i> Bollywood</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Pop" class="genre-pill"><i class="fas fa-wand-magic-sparkles"></i> Pop Hits</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Sufi" class="genre-pill"><i class="fas fa-moon"></i> Sufi &amp; Qawwali</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Rock" class="genre-pill"><i class="fas fa-bolt"></i> Rock</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Acoustic" class="genre-pill"><i class="fas fa-guitar"></i> Acoustic</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Classical" class="genre-pill"><i class="fas fa-music"></i> Classical</a>
      <a href="<?= SITE_URL ?>/pages/music.php?genre=Electronic" class="genre-pill"><i class="fas fa-headphones"></i> EDM / Dance</a>
      <a href="<?= SITE_URL ?>/pages/music.php?lang=Hindi" class="genre-pill"><i class="fas fa-language"></i> Hindi</a>
      <a href="<?= SITE_URL ?>/pages/music.php?lang=English" class="genre-pill"><i class="fas fa-globe"></i> English</a>
      <a href="<?= SITE_URL ?>/pages/music.php?lang=Telugu" class="genre-pill"><i class="fas fa-om"></i> Telugu</a>
      <a href="<?= SITE_URL ?>/pages/music.php?lang=Tamil" class="genre-pill"><i class="fas fa-sun"></i> Tamil</a>
      <a href="<?= SITE_URL ?>/pages/music.php?lang=Punjabi" class="genre-pill"><i class="fas fa-drum"></i> Punjabi</a>
    </div>
  </div>
</section>

<!-- TRENDING -->
<section class="content-section bg-dark2" id="trending">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-fire"></i> Trending Now</h2>
    </div>
    <div class="trending-list">
      <?php foreach ($trending as $i => $item): ?>
      <a href="<?= SITE_URL ?>/pages/<?= $item['type'] === 'music' ? 'music' : 'video' ?>-detail.php?id=<?= $item['id'] ?>" class="trending-item">
        <span class="t-num"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
        <div class="t-thumb">
          <img src="<?= resolveMediaUrl($item['cover_image'], 'cover') ?>" alt="<?= clean($item['title']) ?>" onerror="this.onerror=null; this.src='<?= SITE_URL ?>/assets/images/covers/cover_pop.jpg';">
        </div>
        <div class="t-info">
          <h4><?= clean($item['title']) ?></h4>
          <p><?= clean($item['artist_name'] ?? '') ?> &bull; <?= clean($item['language']) ?> &bull; <?= $item['year'] ?></p>
        </div>
        <div class="t-meta">
          <span class="t-plays"><?php
            $count = $item['play_count'] ?? 0;
            echo $count >= 1000000 ? round($count/1000000,1).'M' : ($count >= 1000 ? round($count/1000,1).'K' : $count);
          ?> plays</span>
          <span class="t-type <?= $item['type'] ?>"><?= $item['type'] === 'music' ? '🎵' : '🎬' ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ALBUMS -->
<section class="content-section" id="albums-sec">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-compact-disc"></i> Popular Albums</h2>
      <a href="<?= SITE_URL ?>/pages/albums.php" class="view-all">All Albums <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="albums-grid">
      <?php foreach ($albums as $alb): ?>
      <a href="<?= SITE_URL ?>/pages/album-detail.php?id=<?= $alb['id'] ?>" class="album-card">
        <div class="album-thumb">
          <img src="<?= resolveMediaUrl($alb['cover_image'], 'cover') ?>" alt="<?= clean($alb['title']) ?>">
          <div class="album-overlay"><i class="fas fa-play"></i></div>
        </div>
        <div class="album-body">
          <h4><?= clean($alb['title']) ?></h4>
          <p><?= clean($alb['artist_name'] ?? 'Various Artists') ?></p>
          <div class="album-meta">
            <span><?= $alb['year'] ?></span>
            <span class="track-count"><?= $alb['track_count'] ?> Tracks</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ARTISTS -->
<section class="content-section bg-dark2" id="artists-sec">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title"><span class="accent-bar"></span><i class="fas fa-microphone"></i> Top Artists</h2>
      <a href="<?= SITE_URL ?>/pages/artists.php" class="view-all">All Artists <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="artists-grid">
      <?php foreach ($artists as $art): ?>
      <a href="<?= SITE_URL ?>/pages/artist-detail.php?id=<?= $art['id'] ?>" class="artist-card">
        <div class="artist-avatar">
          <img src="<?= resolveMediaUrl($art['image'], 'artist') ?>" alt="<?= clean($art['name']) ?>">
        </div>
        <h4><?= clean($art['name']) ?></h4>
        <p><?= clean($art['language']) ?> &bull; <?= $art['song_count'] ?> Songs</p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

