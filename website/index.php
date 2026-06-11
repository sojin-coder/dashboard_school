<!DOCTYPE html>
<html lang="km">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>E-Education | សាលារៀនឌីជីថលទំនើប</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Khmer OS', 'Segoe UI', 'Poppins', system-ui, 'អក្សរខ្មែរ', sans-serif;
      background: linear-gradient(145deg, #fff5f9 0%, #ffe6f0 100%);
      color: #1e1a2f;
      line-height: 1.5;
      scroll-behavior: smooth;
    }

    /* រចនាបថពណ៌ចម្បង: pink, deeppink, blue */
    :root {
      --soft-pink: #ffb6c1;
      --deep-pink: #ff1493;
      --vibrant-blue: #1e88e5;
      --pure-blue: #0d47a1;
      --pink-glow: rgba(255, 20, 147, 0.2);
      --blue-glow: rgba(30, 136, 229, 0.2);
    }

    /* scrollbar custom */
    ::-webkit-scrollbar {
      width: 10px;
    }
    ::-webkit-scrollbar-track {
      background: #ffe0ec;
    }
    ::-webkit-scrollbar-thumb {
      background: var(--deep-pink);
      border-radius: 20px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #c5116b;
    }

    /* Reusable elements */
    .container {
      max-width: 1300px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* Header / Navbar */
    .navbar {
      background: rgba(255, 248, 250, 0.96);
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 2px solid var(--soft-pink);
    }
    .nav-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 24px;
      flex-wrap: wrap;
    }
    .logo h1 {
      font-size: 1.9rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--deep-pink), var(--vibrant-blue));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      letter-spacing: -0.5px;
    }
    .logo span {
      font-size: 1rem;
      color: var(--pure-blue);
      font-weight: 500;
    }
    .nav-links {
      display: flex;
      gap: 2rem;
      list-style: none;
    }
    .nav-links a {
      text-decoration: none;
      font-weight: 600;
      color: #2c2c3a;
      transition: 0.3s;
      font-size: 1.1rem;
      padding: 8px 0;
      border-bottom: 2px solid transparent;
    }
    .nav-links a:hover {
      color: var(--deep-pink);
      border-bottom-color: var(--deep-pink);
    }

    /* Hero Section */
    .hero {
      padding: 80px 0 70px;
      background: radial-gradient(circle at 10% 30%, rgba(255,182,193,0.2), transparent 70%);
    }
    .hero-grid {
      display: flex;
      align-items: center;
      gap: 40px;
      flex-wrap: wrap;
    }
    .hero-content {
      flex: 1;
    }
    .hero-badge {
      background: var(--deep-pink);
      display: inline-block;
      padding: 6px 18px;
      border-radius: 40px;
      color: white;
      font-weight: 600;
      font-size: 0.85rem;
      letter-spacing: 1px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(255,20,147,0.3);
    }
    .hero-content h1 {
      font-size: 3.4rem;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 20px;
    }
    .gradient-text {
      background: linear-gradient(120deg, var(--deep-pink), var(--vibrant-blue));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }
    .hero-content p {
      font-size: 1.2rem;
      color: #2d2f3e;
      margin-bottom: 30px;
      max-width: 550px;
    }
    .btn-group {
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
    }
    .btn-pink {
      background: var(--deep-pink);
      color: white;
      border: none;
      padding: 12px 30px;
      font-weight: 700;
      border-radius: 60px;
      cursor: pointer;
      transition: 0.2s;
      box-shadow: 0 8px 18px rgba(255,20,147,0.3);
      font-size: 1rem;
    }
    .btn-pink:hover {
      background: #e6007a;
      transform: scale(1.02);
    }
    .btn-outline {
      background: transparent;
      border: 2px solid var(--vibrant-blue);
      padding: 10px 28px;
      border-radius: 60px;
      font-weight: 700;
      color: var(--pure-blue);
      transition: 0.2s;
    }
    .btn-outline:hover {
      background: var(--vibrant-blue);
      color: white;
      border-color: var(--vibrant-blue);
    }
    .hero-image {
      flex: 1;
      text-align: center;
    }
    .hero-image img {
      max-width: 100%;
      width: 380px;
      filter: drop-shadow(0 20px 30px rgba(0,0,0,0.1));
      background: rgba(255,255,240,0.3);
      border-radius: 48px;
    }

    /* Features Section */
    .features {
      padding: 80px 0;
      background: rgba(30, 136, 229, 0.03);
    }
    .section-title {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 50px;
    }
    .section-title span {
      background: linear-gradient(135deg, var(--deep-pink), var(--vibrant-blue));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }
    .cards {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      justify-content: center;
    }
    .card {
      background: white;
      border-radius: 40px;
      padding: 32px 24px;
      flex: 1 1 280px;
      box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      text-align: center;
      border-bottom: 6px solid var(--soft-pink);
    }
    .card:hover {
      transform: translateY(-8px);
      border-bottom-color: var(--deep-pink);
      box-shadow: 0 28px 40px -12px var(--pink-glow);
    }
    .card-icon {
      font-size: 2.8rem;
      margin-bottom: 20px;
    }
    .card h3 {
      font-size: 1.6rem;
      margin-bottom: 12px;
      color: var(--pure-blue);
    }
    .card p {
      color: #4a4a5a;
    }

    /* About / អំពីសាលា */
    .about {
      padding: 80px 0;
    }
    .about-wrapper {
      display: flex;
      gap: 50px;
      align-items: center;
      flex-wrap: wrap;
    }
    .about-text {
      flex: 1.2;
    }
    .about-text h2 {
      font-size: 2.3rem;
      margin-bottom: 20px;
      border-left: 8px solid var(--deep-pink);
      padding-left: 22px;
    }
    .about-text p {
      margin-bottom: 18px;
      font-size: 1.05rem;
      color: #2a2a38;
    }
    .stats {
      display: flex;
      gap: 35px;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    .stat-item {
      background: rgba(255, 20, 147, 0.08);
      padding: 12px 22px;
      border-radius: 60px;
      backdrop-filter: blur(2px);
    }
    .stat-number {
      font-weight: 800;
      font-size: 1.9rem;
      color: var(--deep-pink);
    }
    .about-img {
      flex: 0.9;
      background: radial-gradient(ellipse at center, #ffd9e8, #ffeef4);
      border-radius: 60px;
      padding: 20px;
      text-align: center;
    }
    .about-img img {
      width: 100%;
      max-width: 320px;
      border-radius: 48px;
    }

    /* Programs section */
    .programs {
      background: linear-gradient(110deg, #fef2f8 0%, #e9f2fe 100%);
      padding: 80px 0;
    }
    .program-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 28px;
      justify-content: center;
    }
    .program-card {
      background: rgba(255,255,250,0.9);
      backdrop-filter: blur(4px);
      border-radius: 32px;
      padding: 24px;
      width: 280px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0,0,0,0.05);
      transition: 0.2s;
      border: 1px solid white;
    }
    .program-card:hover {
      transform: scale(1.02);
      border: 1px solid var(--deep-pink);
    }
    .program-icon {
      font-size: 2.2rem;
      background: var(--soft-pink);
      width: 70px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 30px;
      margin: 0 auto 18px;
      color: var(--deep-pink);
    }
    .program-card h4 {
      font-size: 1.4rem;
      margin-bottom: 10px;
      color: #0d3b66;
    }

    /* Testimonials */
    .testimonials {
      padding: 80px 0;
    }
    .testi-grid {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .testi-item {
      background: white;
      border-radius: 48px;
      padding: 32px 28px;
      width: 320px;
      box-shadow: 0 18px 32px -12px rgba(0,0,0,0.08);
      border-top: 5px solid var(--vibrant-blue);
    }
    .testi-text {
      font-style: italic;
      font-size: 1rem;
      margin-bottom: 20px;
      color: #1f2a44;
    }
    .student-name {
      font-weight: 800;
      color: var(--deep-pink);
    }

    /* Footer & Contact */
    .footer {
      background: #0d1b2a;
      color: #f0eef7;
      padding: 50px 0 30px;
      border-top: 8px solid var(--deep-pink);
    }
    .footer-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 2rem;
    }
    .footer-col h3 {
      color: var(--soft-pink);
      margin-bottom: 20px;
      font-size: 1.4rem;
    }
    .footer-col p, .footer-col a {
      color: #ddd9f0;
      text-decoration: none;
      line-height: 1.8;
    }
    .footer-col a:hover {
      color: var(--deep-pink);
    }
    .social-icons {
      display: flex;
      gap: 16px;
      margin-top: 16px;
    }
    .social-icons span {
      font-size: 1.8rem;
      cursor: default;
      background: #2c3e5c;
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: 0.2s;
    }
    .copyright {
      text-align: center;
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #2c3e5c;
      font-size: 0.85rem;
    }

    /* Responsive */
    @media (max-width: 800px) {
      .nav-container {
        flex-direction: column;
        gap: 16px;
      }
      .hero-content h1 {
        font-size: 2.3rem;
      }
      .section-title {
        font-size: 2rem;
      }
    }
    @media (max-width: 550px) {
      .btn-group {
        justify-content: center;
      }
      .container {
        padding: 0 18px;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation Bar -->
  <nav class="navbar">
    <div class="nav-container container">
      <div class="logo">
        <h1>E-Education </h1>
      </div>
      <ul class="nav-links">
        <li><a href="#home">ទំព័រដើម</a></li>
        <li><a href="#about">អំពីសាលា</a></li>
        <li><a href="#programs">កម្មវិធីសិក្សា</a></li>
        <li><a href="#contact">ទំនាក់ទំនង</a></li>
      </ul>
    </div>
  </nav>

  <main>
    <!-- Hero Section with ID for home -->
    <section id="home" class="hero">
      <div class="container hero-grid">
        <div class="hero-content">
          <div class="hero-badge">សាលារៀនឌីជីថលសម័យថ្មី</div>
          <h1>អនាគតនៃការសិក្សា <br><span class="gradient-text">ចាប់ផ្ដើមនៅទីនេះ</span></h1>
          <p>សាលាវិទ្យាល័យ E-Education ផ្ដល់ជូននូវបរិយាកាសសិក្សាប្រកបដោយភាពច្នៃប្រឌិត បច្ចេកវិទ្យាទំនើប និងការយកចិត្តទុកដាក់ខាងសីលធម៌។</p>
          <div class="btn-group">
            <button class="btn-pink" onclick="alert('សូមស្វាគមន៍ការចុះឈ្មោះចូលរៀននៅ E-Education! 📚')">ចុះឈ្មោះឥឡូវ</button>
            <button class="btn-outline" onclick="document.getElementById('about').scrollIntoView({behavior:'smooth'})">ស្វែងយល់បន្ថែម</button>
          </div>
        </div>
        <div class="hero-image">
          <img src="https://placehold.co/500x500/ffe0f0/ff1493?text=E-Edu+&font=montserrat" alt="E-Education Illustration" style="background: radial-gradient(circle, #ffe0f0, #ffc0dd); border-radius: 50%; object-fit: cover;">
        </div>
      </div>
    </section>

    <!-- Features / Why choose us -->
    <section class="features">
      <div class="container">
        <div class="section-title">ហេតុអ្វី<span> E-Education?</span></div>
        <div class="cards">
          <div class="card">
            <div class="card-icon">💻</div>
            <h3>បច្ចេកវិទ្យាទំនើប</h3>
            <p>ថ្នាក់រៀនឆ្លាតវៃ មន្ទីរពិសោធន៍កុំព្យូទ័រ និង e-Learning platform</p>
          </div>
          <div class="card">
            <div class="card-icon">🎓</div>
            <h3>គ្រូបង្រៀនជំនាញ</h3>
            <p>សាស្ត្រាចារ្យដែលមានបទពិសោធន៍ និងស្រឡាញ់សិស្ស</p>
          </div>
          <div class="card">
            <div class="card-icon">🌍</div>
            <h3>កម្មវិធីសកល</h3>
            <p>ឱកាសប្ដូរវប្បធម៌ និងកម្មសិក្សាអន្តរជាតិ</p>
          </div>
          <div class="card">
            <div class="card-icon">🏅</div>
            <h3>សកម្មភាពក្រៅកម្មវិធី</h3>
            <p>កីឡា សិល្បៈ ក្លឹបស្រាវជ្រាវ អភិវឌ្ឍន៍ភាពជាអ្នកដឹកនាំ</p>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
      <div class="container about-wrapper">
        <div class="about-text">
          <h2>អំពីវិទ្យាល័យ <span style="color: var(--deep-pink);">E-Education</span></h2>
          <p>បង្កើតឡើងក្នុងឆ្នាំ 2015, E-Education ជាសាលារៀនឯកជនឈានមុខគេដែលបញ្ចូលគ្នារវាងកម្មវិធីសិក្សាជាតិ និងជំនាញសតវត្សរ៍ទី 21។ យើងជឿថាការអប់រំគឺជាអំណាចផ្លាស់ប្តូរជីវិត ហើយយើងប្ដេជ្ញាចិត្តផ្ដល់នូវបរិស្ថានសុវត្ថិភាព ចម្រុះពណ៌ និងរួមបញ្ចូលទាំងក្ដីស្រលាញ់។</p>
          <p>ជាមួយនឹងពណ៌ផ្កាឈូក និងពណ៌ខៀវដែលតំណាងឲ្យភាពច្នៃប្រឌិត និងសន្តិភាព សាលាយើងជំរុញសិស្សគ្រប់រូបឱ្យក្លាយជាអ្នកដឹកនាំ និងអ្នកច្នៃប្រឌិតនាពេលអនាគត។</p>
          <div class="stats">
            <div class="stat-item"><span class="stat-number">1200+</span><br>សិស្សសកម្ម</div>
            <div class="stat-item"><span class="stat-number">98%</span><br>អត្រាជាប់ប្រឡង</div>
            <div class="stat-item"><span class="stat-number">35+</span><br>ក្លឹបសិស្ស</div>
          </div>
        </div>
        <div class="about-img">
          <img src="https://placehold.co/400x400/f9cedf/ff1493?text=School+Campus&font=poppins" alt="campus illustration" style="border-radius: 48px;">
        </div>
      </div>
    </section>

    <!-- Programs Section -->
    <section id="programs" class="programs">
      <div class="container">
        <div class="section-title">កម្មវិធីសិក្សា<span> ពិសេស</span></div>
        <div class="program-grid">
          <div class="program-card">
            <div class="program-icon">📐</div>
            <h4>វិទ្យាសាស្ត្រ និងគណិត</h4>
            <p>STEM Education, មន្ទីរពិសោធទំនើប និងការប្រកួតអូឡាំព្យាដ</p>
          </div>
          <div class="program-card">
            <div class="program-icon">💻</div>
            <h4>ព័ត៌មានវិទ្យា</h4>
            <p>កម្មវិធីសរសេរ Code, AI, Robotics & Digital Creator</p>
          </div>
          <div class="program-card">
            <div class="program-icon">🎨</div>
            <h4>សិល្បៈ និងរចនា</h4>
            <p>គំនូរ តន្ត្រី សិល្បៈសម្ដែង និងការរចនាក្រាហ្វិក</p>
          </div>
          <div class="program-card">
            <div class="program-icon">🌎</div>
            <h4>ភាសាអន្តរជាតិ</h4>
            <p>English, Chinese, French Program with native teachers</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
      <div class="container">
        <div class="section-title">សិស្ស<span> និយាយអំពីយើង</span></div>
        <div class="testi-grid">
          <div class="testi-item">
            <div class="testi-text">“ខ្ញុំពិតជាចូលចិត្តបរិយាកាសសាលា E-Education ណាស់! គ្រូបង្រៀនយកចិត្តទុកដាក់ ហើយក៏មានឱកាសចូលរួមក្នុងការប្រកួតបច្ចេកវិទ្យាអន្តរជាតិ។”</div>
            <div class="student-name">— ស្រីពេជ្រ, ថ្នាក់ទី១២</div>
          </div>
          <div class="testi-item">
            <div class="testi-text">“ខ្ញុំចូលរៀនផ្នែករ៉ូបូតនៅទីនេះ ហើយទទួលបានជំនាញជាក់ស្ដែង។ សូមអរគុណ E-Education ដែលធ្វើឲ្យសុបិនខ្ញុំក្លាយជាការពិត។”</div>
            <div class="student-name">— វិច្ឆិកា, និស្សិតឆ្នើម</div>
          </div>
          <div class="testi-item">
            <div class="testi-text">“មិនដែលគិតថាសាលារៀនអាចសប្បាយ និងមានគុណភាពដូចនេះទេ។ ស្រលាញ់ពណ៌ផ្កាឈូក និងខៀវនៅទូទាំងសាលា! ❤️”</div>
            <div class="student-name">—  សុខា, ថ្នាក់ទី១០</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact & Footer -->
    <footer id="contact" class="footer">
      <div class="container footer-grid">
        <div class="footer-col">
          <h3>E-Education High School</h3>
          <p>អាស័យដ្ឋាន: ផ្លូវជាតិលេខ៥, ភ្នំពេញ, កម្ពុជា</p>
          <p>ទូរស័ព្ទ: +855 23 999 888</p>
          <p>អ៊ីមែល: info@e-education.edu.kh</p>
        </div>
        <div class="footer-col">
          <h3>ម៉ោងបើកការដ្ឋាន</h3>
          <p>ច័ន្ទ – សុក្រ: 7:30 AM – 5:00 PM<br>សៅរ៍: 8:00 AM – 12:00 PM<br>អាទិត្យ: បិទ</p>
        </div>
        <div class="footer-col">
          <h3>តាមដានព័ត៌មាន</h3>
          <div class="social-icons">
            <span>📘</span>
            <span>📷</span>
            <span>▶️</span>
            <span>🐦</span>
          </div>
          <p style="margin-top: 16px;">#EEducation #SchoolOfFuture</p>
        </div>
      </div>
      <div class="copyright container">
        <p>© 2025 E-Education High School - សាលារៀនដែលបំផុសគំនិតអនាគត | Designed with 💗 & 💙</p>
      </div>
    </footer>
  </main>

  <!-- Simple smooth script -->
  <script>
    // smooth scroll for all internal anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if(targetId === "#") return;
        const targetElement = document.querySelector(targetId);
        if(targetElement) {
          targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
    // small interactive for contact / demo
    console.log("E-Education website loaded — pink, deeppink & blue vibes!");
  </script>
</body>
</html>