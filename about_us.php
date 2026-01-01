<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us | DEWASUFA</title>
  <style>
    /* Global Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f9f9f9;
      color: #2c3e50;
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Header Styles */
    header {
      background-color: white;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      padding: 15px 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Back Button - Sekarang Hijau */
    .back-btn {
      background: #2ecc71; /* 🌿 Hijau cerah */
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 8px 16px;
      border-radius: 50px;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background 0.3s, transform 0.2s;
      font-size: 14px;
    }

    .back-btn:hover {
      background: #27ae60; /* Hijau lebih gelap saat hover */
      transform: scale(1.05);
    }

    /* Logo */
    .logo {
      display: flex;
      align-items: center;
      font-size: 18px;
      font-weight: bold;
      color: #2c3e50;
      text-decoration: none;
    }

    .logo::before {
      content: '';
      margin-right: 8px;
      font-size: 20px;
    }

    /* Navigation */
    nav ul {
      display: flex;
      list-style: none;
    }

    nav ul li {
      margin-left: 25px;
    }

    nav ul li a {
      color: #2c3e50;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    nav ul li a:hover {
      color: #2ecc71; /* Sesuaikan hover link dengan warna hijau */
    }

    /* Login Button - Sekarang Hijau */
    .login-btn {
      background: #2ecc71; /* 🌿 Hijau cerah */
      color: white;
      padding: 8px 20px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s;
    }

    .login-btn:hover {
      background: #27ae60; /* Hijau lebih gelap saat hover */
    }

    /* About Section */
    .about-section {
      padding: 60px 0;
      background-color: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin: 30px auto;
      max-width: 1200px;
      border-radius: 10px;
    }

    .section-title {
      font-size: 2.2rem;
      color: #2c3e50;
      margin-bottom: 20px;
      text-align: center;
    }

    .section-title::after {
      content: '';
      display: block;
      width: 60px;
      height: 3px;
      background: #2ecc71; /* Garis bawah judul juga hijau */
      margin: 15px auto;
    }

    .about-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-top: 30px;
    }

    .about-image {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .about-image img {
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    .about-text {
      padding: 20px;
    }

    .about-text h3 {
      color: #2c3e50;
      margin-bottom: 15px;
      font-size: 1.5rem;
    }

    .about-text p {
      margin-bottom: 15px;
      color: #555;
    }

    /* Mission & Vision — Hijau Muda */
    .mission-vision {
      background: #e8f5e9; /* Hijau muda lembut */
      padding: 40px 0;
      margin-top: 40px;
      border-radius: 10px;
    }

    .mv-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .mv-card {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
      text-align: center;
    }

    .mv-card h3 {
      color: #2c3e50;
      margin-bottom: 10px;
      font-size: 1.3rem;
    }

    .mv-card p {
      color: #555;
      font-size: 0.95rem;
    }

    /* Footer */
    footer {
      background-color: white;
      color: #2c3e50;
      padding: 30px 0;
      text-align: center;
      margin-top: 40px;
      border-top: 1px solid #eee;
    }

    .footer-links {
      margin-top: 15px;
    }

    .footer-links a {
      color: #2ecc71; /* Link footer juga hijau */
      text-decoration: none;
      margin: 0 10px;
      transition: color 0.3s;
    }

    .footer-links a:hover {
      color: #27ae60;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        gap: 15px;
      }

      .about-content {
        grid-template-columns: 1fr;
      }

      .section-title {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <div class="header-content">
      <!-- Back Button (Pojok Kiri Atas) -->
      <a href="javascript:history.back()" class="back-btn">Back</a>

      <a href="#" class="logo">DEWASUFA</a>
      <nav>
        <ul>
          <li><a href="index.php#index">Home</a></li>
          <li><a href="contact_us.php#contact">Contact</a></li>
        </ul>
      </nav>
      <a href="login.php" class="login-btn">Log In</a>
    </div>
  </header>

  <!-- About Section -->
  <section class="about-section">
    <div class="container">
      <h2 class="section-title">About DEWASUFA</h2>
      <div class="about-content">
        <div class="about-image">
          <img src="assets/img/hero-about-us.jpg" alt="DEWASUFA">
        </div>
        <div class="about-text">
          <h3>Welcome to DEWASUFA</h3>
          <p>Nestled among Bali’s natural wonders, DEWASUFA is a sanctuary where the magic of waterfalls, sunrise serenity, and golden sunsets come together. More than just a place to stay, it is a space where tranquility unfolds, inspiration grows, and unforgettable moments are created.</p>
          <p>Founded with the belief that nature holds the power to heal and reconnect, we designed DEWASUFA to feel like a peaceful escape a retreat where guests can breathe, relax, and discover new beauty each day. Every corner is crafted to reflect harmony, comfort, and the timeless charm of Bali’s landscapes.</p>
          <p>Our rooms and surroundings embrace natural elegance, while our curated experiences from waterfall explorations to sunrise viewpoints and calming sunset rituals are created to help you feel grounded and refreshed.</p>
          <p>Whether you’re traveling solo, with friends, or with someone special, DEWASUFA is a place where every sunrise welcomes you, every sunset soothes you, and every moment feels uniquely yours.</p>
        </div>
      </div>

      <!-- Mission & Vision -->
      <div class="mission-vision">
        <div class="mv-grid">
          <div class="mv-card">
            <h3>Our Mission</h3>
            <p>To create a serene and inspiring sanctuary where guests can reconnect with nature through the beauty of waterfalls, sunrise calmness, and breathtaking sunsets an experience that brings peace, clarity, and joy.</p>
          </div>
          <div class="mv-card">
            <h3>Our Vision</h3>
            <p>To become Bali’s most tranquil and memorable nature-inspired retreat, offering an unforgettable blend of comfort, harmony, and the enchanting elements of sunrise, sunset, and cascading waterfalls.</p>
          </div>
          <div class="mv-card">
            <h3>Our Promise</h3>
            <p>A peaceful escape awaits no stress, no distractions. At DEWASUFA, we welcome every guest with warmth, care, and a commitment to make you feel at home, surrounded by nature’s most beautiful moments.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; 2025 DEWASUFA. All Rights Reserved.</p>
    <div class="footer-links">
      <a href="#">Legal Notice</a> · 
      <a href="#">Privacy Policy</a> · 
      <a href="#">Accessibility Policy</a>
    </div>
  </footer>

</body>
</html>