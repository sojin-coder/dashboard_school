<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>E-Education សាកលវិទ្យាល័យ | អនាគតនៃការសិក្សា</title>
   <style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --soft-pink:#ffb6c1;
    --deep-pink:#ff1493;
    --vibrant-blue:#1e88e5;
    --navy-blue:#0d47a1;
    --light-bg:#fff4f8;
    --white:#ffffff;
    --dark:#1a1a2e;

    --shadow-sm:0 10px 30px rgba(0,0,0,0.06);
    --shadow-md:0 20px 40px rgba(0,0,0,0.12);

    --radius:28px;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:'Khmer OS','Segoe UI','Poppins',sans-serif;
    background:linear-gradient(135deg,#fff0f7,#ffe4f0);
    color:var(--dark);
    line-height:1.7;
    overflow-x:hidden;
}

/* ================= HEADER ================= */

.header{
    position:sticky;
    top:0;
    z-index:1000;

    background:rgba(255,255,255,0.92);
    backdrop-filter:blur(14px);

    box-shadow:0 4px 25px rgba(0,0,0,0.06);
    border-bottom:1px solid rgba(255,20,147,0.12);
}

.navbar{
    max-width:1300px;
    margin:auto;
    padding:1rem 2rem;

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:1rem;
}

.logo h1{
    font-size:2rem;
    font-weight:800;

    background:linear-gradient(
        135deg,
        var(--deep-pink),
        var(--vibrant-blue)
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.logo span{
    color:var(--navy-blue);
    font-size:.9rem;
}

.nav-links{
    display:flex;
    align-items:center;
    gap:1rem;
    flex-wrap:wrap;
    list-style:none;
}

.nav-links li a{
    text-decoration:none;
    color:#222;
    font-weight:600;

    padding:.7rem 1.2rem;
    border-radius:40px;

    position:relative;
    transition:.3s;
}

.nav-links li a:hover{
    background:rgba(255,20,147,.08);
    color:var(--deep-pink);
}

.nav-links li a::after{
    content:'';
    position:absolute;
    left:50%;
    bottom:6px;

    width:0;
    height:2px;

    background:var(--deep-pink);

    transition:.3s;
    transform:translateX(-50%);
}

.nav-links li a:hover::after{
    width:60%;
}

.btn-outline-pink{
    border:2px solid var(--deep-pink);
    color:var(--deep-pink);
}

.btn-outline-pink:hover{
    background:var(--deep-pink);
    color:white !important;
}

.btn-solid-blue{
    background:var(--vibrant-blue);
    color:white !important;
}

.btn-solid-blue:hover{
    background:var(--navy-blue);
}

/* ================= HERO ================= */

.hero{
    background:
    linear-gradient(
        rgba(0,0,0,.55),
        rgba(0,0,0,.55)
    ),
    url('https://i.pinimg.com/1200x/6a/88/0b/6a880bfc0a0f6c88819191819388a0d7.jpg');

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    min-height:90vh;

    display:flex;
    align-items:center;
    justify-content:center;

    text-align:center;

    padding:4rem 2rem;

    border-bottom-left-radius:60px;
    border-bottom-right-radius:60px;

    position:relative;
    overflow:hidden;
}

.hero::before{
    content:'';
    position:absolute;
    width:500px;
    height:500px;

    background:rgba(255,255,255,.05);

    border-radius:50%;

    top:-150px;
    right:-120px;
}

.hero-content{
    position:relative;
    z-index:2;
    max-width:900px;
}

.hero-content h2{
    font-size:4rem;
    line-height:1.2;
    color:white;
    margin-bottom:1.5rem;
    font-weight:800;

    text-shadow:0 10px 20px rgba(0,0,0,.35);
}

.hero-content p{
    font-size:1.3rem;
    color:#f3f3f3;
    margin-bottom:2rem;
}

.btn-cta{
    background:linear-gradient(
        135deg,
        var(--deep-pink),
        #ff4db8
    );

    color:white;
    border:none;

    padding:1rem 2.4rem;

    font-size:1.1rem;
    font-weight:700;

    border-radius:50px;

    cursor:pointer;

    transition:.3s;

    box-shadow:0 10px 25px rgba(255,20,147,.35);
}

.btn-cta:hover{
    transform:translateY(-4px);
    background:linear-gradient(
        135deg,
        var(--vibrant-blue),
        #4dabff
    );
}

/* ================= SECTION ================= */

.section{
    max-width:1250px;
    margin:6rem auto;
    padding:0 2rem;
}

.section-title{
    font-size:2.5rem;
    margin-bottom:3rem;
    font-weight:800;

    border-left:8px solid var(--deep-pink);
    padding-left:1rem;

    color:#12263f;
}

/* ================= ABOUT ================= */

.about{
    padding:6rem 2rem;
}

.about-wrapper{
    max-width:1250px;
    margin:auto;

    display:flex;
    gap:3rem;
    align-items:center;
    flex-wrap:wrap;

    /* background:white; */

    padding:3rem;
    border-radius:40px;

    /* box-shadow:var(--shadow-sm); */
}

.about-text{
    flex:1.2;
}

.about-text p{
    margin-bottom:1.2rem;
    color:#444;
    font-size:1.05rem;
}

.about-highlight{
    color:var(--deep-pink);
    font-weight:700;
}

.stats{
    display:flex;
    gap:1rem;
    margin-top:2rem;
    flex-wrap:wrap;
}

.stat-item{
    background:linear-gradient(
        135deg,
        #fff0f7,
        #ffe2f0
    );

    padding:1rem 1.5rem;

    border-radius:25px;

    box-shadow:0 5px 15px rgba(255,20,147,.08);
}

.stat-number{
    font-size:2rem;
    font-weight:800;
    color:var(--deep-pink);
}

.about-img{
    flex:1;
}

.about-img img{
    width:100%;
    border-radius:35px;
    box-shadow:var(--shadow-md);
}

/* ================= CURRICULUM ================= */

.curriculum-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:2rem;
}

.card{
    background:white;

    border-radius:32px;
    overflow:hidden;

    transition:.35s;

    box-shadow:0 15px 40px rgba(0,0,0,.06);

    position:relative;
}

.card:hover{
    transform:translateY(-10px);
    box-shadow:0 30px 50px rgba(255,20,147,.18);
}

.card::before{
    content:'';
    position:absolute;
    top:0;
    left:0;

    width:100%;
    height:6px;

    background:linear-gradient(
        90deg,
        var(--deep-pink),
        var(--vibrant-blue)
    );
}

.card-icon img{
    width:100%;
    height:260px;
    object-fit:cover;
    display:block;
}

.card h3{
    padding:1.5rem 1.5rem .5rem;
    color:var(--navy-blue);
    font-size:1.5rem;
}

.card p{
    padding:0 1.5rem 2rem;
    color:#555;
}

/* ================= CONTACT ================= */

.container{
    max-width:1250px;
    margin:auto;
    padding:0 2rem;
}

.page-header{
    text-align:center;
    margin-bottom:3rem;
}

.page-header h1{
    font-size:3rem;
    margin-bottom:1rem;

    background:linear-gradient(
        135deg,
        var(--deep-pink),
        var(--vibrant-blue)
    );

    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.page-header p{
    color:#555;
}

.contact-grid{
    display:grid;
    grid-template-columns:1.3fr .9fr;
    gap:2rem;
}

.details-card,
.newsletter-card,
.hours-card{
    background:white;
    border-radius:32px;
    padding:2rem;
    box-shadow:var(--shadow-sm);
}

.details-card{
    border-top:6px solid var(--deep-pink);
}

.section-badge{
    display:inline-block;

    background:#ffe2f0;

    padding:.4rem 1rem;

    border-radius:30px;

    color:var(--deep-pink);
    font-weight:700;

    margin-bottom:1rem;
}

.info-block{
    display:flex;
    gap:1rem;

    margin:2rem 0;

    padding-bottom:1rem;

    border-bottom:1px dashed #ffd0e7;
}

.info-icon{
    width:55px;
    height:55px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ffe6f1;

    font-size:1.5rem;
}

.info-content h3{
    margin-bottom:.3rem;
    color:var(--navy-blue);
}

.newsletter-card h3,
.hours-card h3{
    margin-bottom:1rem;
    color:var(--deep-pink);
}

.newsletter-group{
    display:flex;
    gap:1rem;
    margin-bottom:1rem;
    flex-wrap:wrap;
}

.newsletter-group input{
    flex:1;

    padding:1rem;

    border-radius:40px;

    border:1px solid #ffc0dd;

    outline:none;
}

.newsletter-group input:focus{
    border-color:var(--vibrant-blue);
}

.btn-subscribe{
    background:var(--deep-pink);
    color:white;

    border:none;

    padding:0 1.6rem;

    border-radius:40px;

    font-weight:700;

    transition:.3s;
}

.btn-subscribe:hover{
    background:var(--vibrant-blue);
    transform:translateY(-3px);
}

.hours-list{
    display:flex;
    flex-direction:column;
    gap:1rem;
}

.hour-row{
    display:flex;
    justify-content:space-between;

    border-bottom:1px dashed #ffd0e7;

    padding-bottom:.6rem;
}

.day{
    color:var(--deep-pink);
    font-weight:700;
}

.time{
    color:#444;
}

/* ================= FOOTER ================= */

footer{
    background:linear-gradient(
        135deg,
        #0d1b2a,
        #162c46
    );

    color:white;

    text-align:center;

    padding:4rem 2rem;

    margin-top:5rem;

    border-top-left-radius:40px;
    border-top-right-radius:40px;
}

footer p{
    margin:.5rem 0;
}

/* ================= ANIMATION ================= */

.card,
.about-wrapper,
.details-card,
.newsletter-card,
.hours-card{
    animation:fadeUp .8s ease;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(40px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ================= RESPONSIVE ================= */

@media(max-width:992px){

    .hero-content h2{
        font-size:3rem;
    }

    .contact-grid{
        grid-template-columns:1fr;
    }
}

@media(max-width:768px){

    .navbar{
        flex-direction:column;
    }

    .hero{
        min-height:80vh;
    }

    .hero-content h2{
        font-size:2.3rem;
    }

    .hero-content p{
        font-size:1rem;
    }

    .section-title{
        font-size:2rem;
    }

    .about-wrapper{
        padding:2rem;
    }

    .card-icon img{
        height:220px;
    }

    .page-header h1{
        font-size:2.2rem;
    }

    .newsletter-group{
        flex-direction:column;
    }

    .btn-subscribe{
        padding:1rem;
    }
}

@media(max-width:500px){

    .hero-content h2{
        font-size:1.8rem;
    }

    .section-title{
        font-size:1.5rem;
    }

    .navbar{
        padding:1rem;
    }

    .nav-links{
        justify-content:center;
    }

    .about-wrapper{
        border-radius:25px;
    }

    .card{
        border-radius:25px;
    }

    .details-card,
    .newsletter-card,
    .hours-card{
        border-radius:25px;
    }
}
</style>
</head>
<body>

<header class="header">
    <div class="navbar">
        <div class="logo">
            <h1>E-Education <span>University</span></h1>
        </div>
        <ul class="nav-links">
            <li><a href="#home">ទំព័រដើម</a></li>
            <li><a href="#about">អំពីយើង</a></li>
            <li><a href="#curriculum">កម្មវិធីសិក្សា</a></li>
            <li><a href="#contact">ទំនាក់ទំនង</a></li>
            <li><a href="../login.php" class="btn-outline-pink" style="border-radius:40px;">ចូលប្រើ</a></li>
            <!-- <li><a href="#" id="signUpNavBtn" class="btn-solid-blue" style="border-radius:40px;">ចុះឈ្មោះ</a></li> -->
        </ul>
    </div>
</header>

<main>
    <!-- ផ្នែកទំព័រដើម -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h2>ពង្រឹងអនាគតរបស់អ្នក <br>ជាមួយ E-Education</h2>
            <p>ការសិក្សាប្រកបដោយភាពច្នៃប្រឌិត សហគមន៍សកល និងកម្មវិធីសិក្សាទំនើប — ទាំងអស់នៅកន្លែងតែមួយ។ </p>
            <button class="btn-cta" id="exploreBtn">ស្វែងយល់កម្មវិធី →</button>
        </div>
    </section>

    <!-- អំពីយើង -->
   <section id="about" class="about">
      <div class="container about-wrapper">
        <div class="about-text">
          <div class="about-text">
                <p>បង្កើតឡើងក្នុងឆ្នាំ ២០២១ <strong class="about-highlight">សាកលវិទ្យាល័យ E-Education</strong> រួមបញ្ចូលគរុកោសល្យទំនើប និងនវានុ<br><br> វត្តន៍ឌីជីថល។ យើងមិនមែនគ្រាន់តែជាសាកលវិទ្យាល័យទេ — យើងគឺជាចលនាដែលផ្តល់អំណាច<br><br>ដល់សិស្សានុសិស្ស តាមរយៈការអប់រំដែលអាចបត់បែនបាន និងគុណភាពខ្ពស់។</p>
                <br>
                <p>ជាមួយគ្រូបង្រៀនជំនាញជាង ២១ រូប និងភាពជាដៃគូឧស្សាហកម្ម E-Education ផ្តល់សញ្ញាបត្រផ្នែក AI, សិល្បៈច្នៃប្រឌិត, ពាណិជ្ជកម្ម និងបច្ចេកវិទ្យាប្រកបដោយចីរភាព — ទាំងអស់ត្រូវបានបញ្ជូនតាមរយៈគំរូឌីជីថល និងចម្រុះ។</p>
                <br>
                <p>🌟 បេសកកម្មរបស់យើង៖ បង្កើតអ្នកដឹកនាំដែលត្រៀមខ្លួនសម្រាប់អនាគត គិតបែបរិះគន់ ធ្វើសកម្មភាពប្រកបដោយក្រមសីលធម៌ និងបង្កើតថ្មីដោយរួមបញ្ចូល។</p>
            
          <div class="stats">
            <div class="stat-item"><span class="stat-number">1200+</span><br>សិស្សសកម្ម</div>
            <div class="stat-item"><span class="stat-number">98%</span><br>អត្រាជាប់ប្រឡង</div>
            <div class="stat-item"><span class="stat-number">35+</span><br>ក្លឹបសិស្ស</div>
          </div>
        </div>
</div>
        <div class="about-img">
          <img src="https://i.pinimg.com/1200x/64/31/55/643155cd8caec4c2779194a5da75e707.jpg" alt="campus illustration" style="border-radius: 48px;">
        </div>
      </div>
    </section>

    <!-- កម្មវិធីសិក្សា -->
    <section id="curriculum" class="section">
        <h2 class="section-title">📚 កម្មវិធីសិក្សារបស់យើង — រចនាឡើងសម្រាប់ថ្ងៃស្អែក</h2>
        <div class="curriculum-cards">
            <div class="card">
                <div class="card-icon">
                    <img src="https://i.pinimg.com/736x/22/5e/d6/225ed64777b68c6108f4894f3da7c662.jpg" alt="">

                </div>
                <h3>AI & វិទ្យាសាស្ត្រទិន្នន័យ</h3>
                <p>ម៉ាស៊ីនរៀន, AI បង្កើត, ការវិភាគទិន្នន័យធំ — គម្រោងជាក់ស្តែងជាមួយទិន្នន័យពិត។</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <img src="https://i.pinimg.com/736x/9a/47/8d/9a478d7c874a5dc00e9c5f78809b8fd3.jpg" alt="">
                </div>
                <h3>បច្ចេកវិទ្យាច្នៃប្រឌិត</h3>
                <p>UI/UX, រចនាឌីជីថល, ផលិតកម្មមេឌៀអន្តរកម្ម និងនិទានរឿងតាមឌីជីថល។</p>
            </div>
            <div class="card">
             <div class="card-icon">
               <img src="https://i.pinimg.com/736x/78/df/0c/78df0c00bc8a365b0669e71ee09435d4.jpg" alt="Web Development">
             </div>
    
               <h3>ការបង្កើតគេហទំព័រ និងកម្មវិធី Software</h3>
    
               <p>
                 ផ្តល់សេវាកម្មរចនា និងអភិវឌ្ឍគេហទំព័រ កម្មវិធី Software 
                  និងប្រព័ន្ធគ្រប់គ្រងទិន្នន័យ ដោយប្រើបច្ចេកវិទ្យាទំនើប 
                  មានសុវត្ថិភាព ងាយស្រួលប្រើ និងមានប្រសិទ្ធភាពខ្ពស់។
             </p>
            </div>
          
        </div>
    
    </section>

    <!-- ទំនាក់ទំនង -->
<div class="container" id="contact">
    <!-- header -->
    <div class="page-header">
        <h1>📞 Contact Information</h1>
        <p>Have questions? We'd love to hear from you. ✨</p>
    </div>

    <div class="contact-grid">
        <!-- LEFT SIDE: Our Details (Location, Phone, Email) -->
        <div class="details-card">
            <div class="section-badge">💬 Get in Touch</div>
            <h2>Our Details</h2>
            <div class="intro-text">
                Send us a message and we'll respond as soon as possible. We value every connection.
            </div>

            <!-- Location -->
            <div class="info-block">
                <div class="info-icon">📍</div>
                <div class="info-content">
                    <h3>Our Location</h3>
                    <address>25, 123, Your Town, Pleasant Market, Pleasant Park, Colorado</address>
                </div>
            </div>

            <!-- Phone Numbers (as per the note) -->
            <div class="info-block">
                <div class="info-icon">📞</div>
                <div class="info-content">
                    <h3>Phone Numbers</h3>
                    <p>55, 123, Your Town, Pleasant Market, Pleasant Park, Colorado</p>
                    <!-- note: the text from image says exactly that, we keep it creative yet consistent -->
                    <small style="color:#ff1493;">+1 (555) 123-4567 · Support hotline</small>
                </div>
            </div>

            <!-- Email Address -->
            <div class="info-block">
                <div class="info-icon">✉️</div>
                <div class="info-content">
                    <h3>Email Address</h3>
                    <p>25, 123, Your Town, Pleasant Market, Pleasant Park, Colorado</p>
                    <p style="color:#1e88e5; margin-top: 4px;">hello@pleasantuniversity.com · support@e-education.com</p>
                </div>
            </div>

           
            <!-- Email address line from description again -->
            <div class="email-extra">
                📧 <strong>Email Address</strong> — 25, 123, Your Town, Pleasant Market, Pleasant Park, Colorado (official correspondence)
            </div>
        </div>

        <!-- RIGHT SIDE: Newsletter + Business Hours -->
        <div class="right-col">
            <!-- Newsletter subscription -->
            <div class="newsletter-card">
                <h3>📧 Subscribe to our newsletter</h3>
                <p>Get updates, exclusive promotions, and university insights — straight to your inbox.</p>
                <div class="newsletter-group">
                    <input type="text" id="newslettername" placeholder="Your Name " aria-label="name for newsletter">
                    <button class="btn-subscribe" id="subscribeBtn">Subscribe →</button>
                </div>
                <br>
                <div class="newsletter-group">
                    <input type="email" id="newsletterEmail" placeholder="Your email address" aria-label="Email for newsletter">
                    <button class="btn-subscribe" id="subscribeBtn">Subscribe →</button>
                </div>
                <div id="subscribeFeedback" class="subscribe-feedback"></div>
                <div style="margin-top: 0.8rem; font-size: 0.75rem; color:#a05e7e;">✨ Pink & Blue weekly highlights</div>
            </div>

            <!-- Business Hours card (matching image content exactly) -->
            <div class="hours-card">
                <h3>🕒 Business Hours</h3>
                <div class="hours-list">
                    <div class="hour-row">
                        <span class="day">Monday - Friday:</span>
                        <span class="time">7:00 AM - 6:00 PM</span>
                    </div>
                    <div class="hour-row">
                        <span class="day">Saturday:</span>
                        <span class="time">8:00 AM - 5:00 PM</span>
                    </div>
                    <div class="hour-row">
                        <span class="day">Sunday:</span>
                        <span class="time">9:00 AM - 5:00 PM</span>
                    </div>
                </div>
                <div style="margin-top: 1.2rem; background: #fce4f0; border-radius: 1rem; padding: 0.7rem; text-align: center;">
                    🌟 Response within 24h on business days
                </div>
            </div>

            <!-- additional small detail: "for updates and promotions" already inside newsletter card -->
        </div>
    </div>

    <!-- bonus: extra footer for stylish closure -->
    
</div>
</main>

<footer>
    <p> ២០២៦ សាកលវិទ្យាល័យ E-Education </p>

    <p style="margin-top: 10px; font-size:0.85rem;">បំផុសគំនិតអ្នកដឹកនាំជំនាន់ក្រោយ | #EduReimagined</p>
    
    <p>© 2025 E-Education | Blending   innovation — We're here to connect.</p>
    
</footer>

<script>
    // Modal logic for Sign In and Sign UP (Khmer version)
    const signinModal = document.getElementById('signinModal');
    const signupModal = document.getElementById('signupModal');
    const signInNavBtn = document.getElementById('signInNavBtn');
    const signUpNavBtn = document.getElementById('signUpNavBtn');

    function openModal(modal) {
        modal.style.display = 'flex';
    }
    function closeModal(modal) {
        modal.style.display = 'none';
        clearModalMessages();
    }
    function clearModalMessages() {
        const msgSignin = document.getElementById('signinMessage');
        const msgSignup = document.getElementById('signupMessage');
        if (msgSignin) msgSignin.innerText = '';
        if (msgSignup) msgSignup.innerText = '';
    }

    signInNavBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal(signupModal);
        openModal(signinModal);
    });
    signUpNavBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal(signinModal);
        openModal(signupModal);
    });

    document.getElementById('closeSigninModal').addEventListener('click', () => closeModal(signinModal));
    document.getElementById('closeSignupModal').addEventListener('click', () => closeModal(signupModal));
    window.addEventListener('click', (e) => {
        if (e.target === signinModal) closeModal(signinModal);
        if (e.target === signupModal) closeModal(signupModal);
    });

    // ចូលប្រើ (demo)
    document.getElementById('modalSigninBtn').addEventListener('click', () => {
        const email = document.getElementById('signinEmail').value.trim();
        const pwd = document.getElementById('signinPassword').value.trim();
        const msgDiv = document.getElementById('signinMessage');
        if (!email || !pwd) {
            msgDiv.innerText = '⚠️ សូមបំពេញអ៊ីមែល និងពាក្យសម្ងាត់។';
            msgDiv.style.color = '#ff1493';
        } else {
            msgDiv.innerText = `✨ ស្វាគមន៍មកវិញ ${email.split('@')[0]}! (ការចូលប្រើសាកល្បងជោគជ័យ)`;
            msgDiv.style.color = '#1e88e5';
            setTimeout(() => {
                closeModal(signinModal);
                document.getElementById('signinEmail').value = '';
                document.getElementById('signinPassword').value = '';
                msgDiv.innerText = '';
            }, 1500);
        }
    });

    // ចុះឈ្មោះ demo
    document.getElementById('modalSignupBtn').addEventListener('click', () => {
        const name = document.getElementById('signupName').value.trim();
        const email = document.getElementById('signupEmail').value.trim();
        const pass = document.getElementById('signupPassword').value.trim();
        const msgDiv = document.getElementById('signupMessage');
        if (!name || !email || !pass) {
            msgDiv.innerText = '⚠️ សូមបំពេញគ្រប់វាល។';
            msgDiv.style.color = '#ff1493';
        } else if (pass.length < 4) {
            msgDiv.innerText = '🔒 ពាក្យសម្ងាត់ត្រូវមានយ៉ាងហោចណាស់ ៤ តួ។';
        } else {
            msgDiv.innerText = `🎉 បានបង្កើតគណនីសម្រាប់ ${name}! ឥឡូវអ្នកអាចចូលប្រើបាន។`;
            msgDiv.style.color = '#0d47a1';
            setTimeout(() => {
                closeModal(signupModal);
                document.getElementById('signupName').value = '';
                document.getElementById('signupEmail').value = '';
                document.getElementById('signupPassword').value = '';
                msgDiv.innerText = '';
            }, 1600);
        }
    });

    // ទំនាក់ទំនង form
    const contactForm = document.getElementById('contactForm');
    const formFeedback = document.getElementById('formFeedback');
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const name = document.getElementById('contactName').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const msg = document.getElementById('contactMsg').value.trim();
        if (!name || !email || !msg) {
            formFeedback.innerText = '❌ សូមបំពេញព័ត៌មានទាំងអស់មុនពេលផ្ញើ។';
            formFeedback.style.color = '#d81b60';
            return;
        }
        if (!email.includes('@')) {
            formFeedback.innerText = '📧 សូមបញ្ចូលអាសយដ្ឋានអ៊ីមែលឲ្យបានត្រឹមត្រូវ។';
            return;
        }
        formFeedback.innerText = `✅ សូមអរគុណ ${name}! សាររបស់អ្នកត្រូវបានផ្ញើរួចរាល់។ យើងនឹងទាក់ទងអ្នកឆាប់ៗ។`;
        formFeedback.style.color = '#0f67b3';
        contactForm.reset();
        setTimeout(() => {
            formFeedback.innerText = '';
        }, 4000);
    });

    // smooth scroll & explore button
    document.querySelectorAll('.nav-links a[href^="#"], .btn-cta').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const hash = this.getAttribute('href');
            if (hash && hash !== '#') {
                e.preventDefault();
                const target = document.querySelector(hash);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else if (this.id === 'exploreBtn') {
                const curriculumSection = document.getElementById('curriculum');
                if (curriculumSection) curriculumSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    document.querySelector('.logo').addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // active menu underline effect
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-links li a');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            if (pageYOffset >= sectionTop) {
                current = section.getAttribute('id');
            }
        });
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${current}` && current) {
                link.style.borderBottom = '2px solid #ff1493';
                link.style.fontWeight = 'bold';
            } else {
                link.style.borderBottom = 'none';
                link.style.fontWeight = '500';
            }
        });
    });
</script>
</body>
</html>