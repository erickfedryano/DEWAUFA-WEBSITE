<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us - Dewasufa</title>
  <style>
    /* Reset & Global Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      color: #333;
      background: #fff;
      position: relative;
    }

    /* Tombol Back di pojok kiri atas */
    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px; /* Berubah dari right ke left */
      background: #2ecc71; /* Biru terang */
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      z-index: 100;
      box-shadow: none;
      transition: background 0.2s;
    }

    .back-btn:hover {
      background: #2ecc71;
    }

    .contact-section {
      background: #fff;
      overflow: hidden;
      position: relative;
      min-height: 100vh;
    }

    /* Header Section */
    .header-bg {
      position: relative;
      height: 400px;
      background: url('assets/img/tukad-cepung.jpg') center/cover no-repeat;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 2rem;
    }

    .header-content {
      background: rgba(255, 255, 255, 0.85);
      padding: 1.5rem 2rem;
      border-radius: 12px;
    }

    .header-bg h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
      color: #2ecc71;
      text-shadow: none;
    }

    .header-bg p {
      font-size: 1.1rem;
      max-width: 600px;
      color: #555;
    }

    /* Contact Cards */
    .contact-cards {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem;
      padding: 3rem 2rem;
      background: #fff;
    }

    .card {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 2rem;
      width: 300px;
      text-align: center;
      transition: transform 0.3s ease;
      box-shadow: none;
    }

    .card:hover {
      transform: translateY(-3px);
    }

    .card i {
      font-size: 2.5rem;
      color: #2ecc71;
      margin-bottom: 1rem;
    }

    .card h3 {
      font-size: 1.3rem;
      margin-bottom: 1rem;
      color: #2ecc71;
    }

    .card p {
      color: #666;
      font-size: 0.95rem;
      margin-bottom: 1rem;
    }

    .card .detail {
      font-weight: 600;
      color: #2ecc71;
      font-size: 1.1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .back-btn {
        top: 15px;
        left: 15px; /* Sesuaikan di mobile */
        padding: 6px 12px;
        font-size: 13px;
      }

      .header-bg h1 {
        font-size: 2rem;
      }

      .contact-cards {
        padding: 2rem 1rem;
      }

      .card {
        width: 100%;
        max-width: 350px;
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- Tombol Back di kiri atas -->
  <a href="javascript:history.back()" class="back-btn">
    <i class=""></i> Back
  </a>

  <section class="contact-section">
    <div class="header-bg">
      <div class="header-content">
        <h1>CONTACT US</h1>
        <p>Need an expert? You are more than welcomed to leave your contact info and we will be in touch shortly.</p>
      </div>
    </div>

    <div class="contact-cards">
      <div class="card">
        <i class="fas fa-home"></i>
        <h3>VISIT US</h3>
        <p>Discover our tranquil retreat surrounded by waterfalls, sunrise viewpoints, and breathtaking sunset horizons. Step into a space where nature’s beauty welcomes you at every moment we can’t wait to host you.</p>
        <div class="detail">Jl. Tukad Batanghari, Denpasar, Bali</div>
      </div>

      <div class="card">
        <i class="fas fa-phone"></i>
        <h3>CALL US</h3>
        <p>Have questions about our waterfall spots, sunrise tours, or sunset experiences? Our team is here to help you plan the perfect nature escape.</p>
        <div class="detail">+62 812 3456 7890</div>
      </div>

      <div class="card">
        <i class="fas fa-envelope"></i>
        <h3>CONTACT US</h3>
        <p>Send us an email for reservations, inquiries, or special requests. We respond within 24 hours.</p>
        <div class="detail">info@dewasufa.com</div>
      </div>
    </div>
  </section>

</body>
</html>