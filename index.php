<?php
// index.php
// Landing page SIM Mahasiswa - Universitas Muhammadiyah Bengkulu
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Mahasiswa | Sistem Informasi Mahasiswa</title>
    <meta name="description" content="SIM Mahasiswa - Sistem Informasi Mahasiswa untuk pengelolaan data mahasiswa, program studi, fakultas dan pengguna secara terintegrasi.">

    <style>
        :root{
            --primary:#1167df;
            --primary-dark:#0b4fae;
            --primary-soft:#eaf4ff;
            --navy:#102f59;
            --text:#18385f;
            --muted:#617b9d;
            --white:#fff;
            --bg:#f6fbff;
            --border:#dbe8f7;
            --success:#1db77a;
            --shadow:0 18px 50px rgba(16,63,111,.12);
            --radius:18px;
        }

        *{box-sizing:border-box;margin:0;padding:0}

        html{scroll-behavior:smooth}

        body{
            font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;
            color:var(--text);
            background:var(--white);
            line-height:1.6;
        }

        a{text-decoration:none;color:inherit}

        .container{
            width:min(1180px,92%);
            margin:auto;
        }

        /* NAVBAR */
        .navbar{
            height:76px;
            background:rgba(255,255,255,.96);
            border-bottom:1px solid #eef3f8;
            display:flex;
            align-items:center;
            position:sticky;
            top:0;
            z-index:1000;
            backdrop-filter:blur(12px);
        }

        .nav-inner{
            display:flex;
            align-items:center;
            justify-content:space-between;
            width:100%;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:12px;
        }

        .brand-icon{
            width:48px;
            height:48px;
            border-radius:14px;
            background:linear-gradient(145deg,#0e5ecb,#17427b);
            color:white;
            display:grid;
            place-items:center;
            box-shadow:0 8px 20px rgba(17,103,223,.22);
        }

        .brand-icon svg{width:29px;height:29px}

        .brand-text strong{
            display:block;
            color:var(--navy);
            font-size:20px;
            line-height:1.1;
        }

        .brand-text span{
            font-size:12px;
            color:#567293;
        }

        .nav-menu{
            display:flex;
            align-items:center;
            gap:36px;
            list-style:none;
            margin-left:40px;
        }

        .nav-menu a{
            color:#294362;
            font-size:14px;
            font-weight:500;
            padding:28px 0;
            position:relative;
        }

        .nav-menu a:hover,
        .nav-menu a.active{color:var(--primary)}

        .nav-menu a.active:after{
            content:"";
            position:absolute;
            bottom:10px;
            left:0;
            right:0;
            height:3px;
            border-radius:5px;
            background:var(--primary);
        }

        .nav-actions{display:flex;gap:12px}

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:9px;
            min-height:44px;
            padding:0 22px;
            border-radius:11px;
            border:1px solid transparent;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            transition:.25s ease;
        }

        .btn-outline{
            color:var(--primary);
            border-color:var(--primary);
            background:white;
        }

        .btn-outline:hover{
            background:var(--primary-soft);
            transform:translateY(-2px);
        }

        .btn-primary{
            color:white;
            background:linear-gradient(135deg,#1474ec,#0d5bc9);
            box-shadow:0 10px 25px rgba(17,103,223,.22);
        }

        .btn-primary:hover{
            transform:translateY(-2px);
            box-shadow:0 14px 30px rgba(17,103,223,.28);
        }

        .mobile-toggle{
            display:none;
            border:0;
            background:transparent;
            color:var(--navy);
            font-size:26px;
            cursor:pointer;
        }

        /* HERO */
        .hero{
            position:relative;
            overflow:hidden;
            background:
                radial-gradient(circle at 90% 20%,rgba(103,180,255,.23),transparent 28%),
                linear-gradient(135deg,#f3faff 0%,#e9f4ff 100%);
            min-height:520px;
            display:flex;
            align-items:center;
        }

        .hero:before{
            content:"";
            position:absolute;
            width:430px;
            height:430px;
            border-radius:50%;
            background:rgba(255,255,255,.42);
            left:-240px;
            bottom:-220px;
        }

        .hero-inner{
            display:grid;
            grid-template-columns:48% 52%;
            align-items:center;
            gap:15px;
            position:relative;
            z-index:2;
            padding:50px 0;
        }

        .eyebrow{
            display:inline-flex;
            padding:7px 13px;
            border-radius:30px;
            color:#1269dc;
            background:#dcebff;
            font-size:13px;
            font-weight:700;
            margin-bottom:14px;
        }

        .hero h1{
            font-size:48px;
            line-height:1.12;
            letter-spacing:-1.5px;
            color:#102f59;
            margin-bottom:18px;
        }

        .hero h1 span{color:#1571e9}

        .hero p{
            color:#5b7596;
            font-size:17px;
            max-width:570px;
            margin-bottom:27px;
        }

        .hero-buttons{
            display:flex;
            flex-wrap:wrap;
            gap:13px;
        }

        .hero-visual{
            min-height:390px;
            display:flex;
            justify-content:flex-end;
            align-items:center;
            position:relative;
        }

        .campus-shape{
            position:absolute;
            right:-25px;
            top:5px;
            width:350px;
            height:290px;
            opacity:.17;
        }

        .laptop{
            width:555px;
            position:relative;
            z-index:3;
        }

        .laptop-screen{
            background:#092e5e;
            border:9px solid #153e70;
            border-radius:17px 17px 8px 8px;
            padding:11px;
            box-shadow:0 25px 55px rgba(17,57,99,.22);
        }

        .screen-inner{
            background:#f7fbff;
            border-radius:5px;
            min-height:278px;
            display:grid;
            grid-template-columns:110px 1fr;
            overflow:hidden;
        }

        .mock-sidebar{
            background:linear-gradient(180deg,#0d5fc6,#0b3f82);
            color:white;
            padding:15px 10px;
        }

        .mock-logo{
            font-size:12px;
            font-weight:700;
            padding:7px;
            margin-bottom:10px;
        }

        .mock-menu{
            padding:8px 7px;
            margin:4px 0;
            border-radius:6px;
            font-size:8px;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .mock-menu.active{background:rgba(255,255,255,.16)}

        .mock-content{padding:16px}

        .mock-content h4{
            font-size:15px;
            color:#17375d;
            margin-bottom:11px;
        }

        .stat-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:8px;
            margin-bottom:13px;
        }

        .stat{
            border:1px solid #e4edf7;
            background:white;
            padding:10px;
            border-radius:7px;
        }

        .stat small{font-size:7px;color:#7890ad;display:block}
        .stat strong{font-size:13px;color:#163d68}

        .table-box{
            background:white;
            border:1px solid #e3edf7;
            border-radius:7px;
            padding:10px;
        }

        .table-title{
            font-size:8px;
            font-weight:700;
            margin-bottom:7px;
            color:#24486f;
        }

        .row{
            display:grid;
            grid-template-columns:70px 1fr 80px 45px;
            gap:5px;
            padding:5px 0;
            border-top:1px solid #edf2f7;
            font-size:6px;
            color:#7890a9;
        }

        .badge{
            background:#dcf8eb;
            color:#159467;
            border-radius:10px;
            padding:2px 5px;
            text-align:center;
        }

        .laptop-base{
            width:590px;
            height:16px;
            background:linear-gradient(#d8e5f1,#a9c1d8);
            border-radius:3px 3px 25px 25px;
            margin:-1px auto 0;
            position:relative;
        }

        .laptop-base:after{
            content:"";
            position:absolute;
            width:150px;
            height:4px;
            border-radius:0 0 10px 10px;
            background:#8ea9c1;
            left:50%;
            transform:translateX(-50%);
            top:0;
        }

        .student{
            position:absolute;
            right:-10px;
            bottom:4px;
            z-index:5;
            width:125px;
            height:180px;
        }

        .student-head{
            width:60px;
            height:68px;
            border-radius:48% 48% 45% 45%;
            background:#f2bd91;
            position:absolute;
            top:0;
            left:32px;
        }

        .student-hair{
            position:absolute;
            top:-5px;
            left:26px;
            width:72px;
            height:44px;
            border-radius:55% 55% 25% 25%;
            background:#182e48;
            z-index:2;
        }

        .student-body{
            position:absolute;
            left:18px;
            top:57px;
            width:95px;
            height:112px;
            border-radius:40px 40px 15px 15px;
            background:#1472db;
            transform:rotate(-4deg);
        }

        .student-shirt{
            position:absolute;
            left:30px;
            top:66px;
            width:70px;
            height:88px;
            background:#f7fafc;
            border-radius:10px;
            z-index:1;
        }

        .student-bag{
            position:absolute;
            right:-2px;
            top:65px;
            width:34px;
            height:87px;
            background:#102f59;
            border-radius:9px;
            transform:rotate(-9deg);
        }

        /* FEATURES */
        .features{
            padding:42px 0 45px;
            background:white;
        }

        .feature-grid{
            display:grid;
            grid-template-columns:repeat(5,1fr);
            gap:28px;
        }

        .feature{
            text-align:left;
        }

        .feature-icon{
            width:64px;
            height:64px;
            border-radius:15px;
            display:grid;
            place-items:center;
            margin-bottom:14px;
            background:#edf6ff;
            color:#1470df;
        }

        .feature:nth-child(2) .feature-icon{background:#e8faf4;color:#18a775}
        .feature:nth-child(3) .feature-icon{background:#f1edff;color:#7658df}
        .feature:nth-child(4) .feature-icon{background:#fff1e5;color:#e9893a}
        .feature:nth-child(5) .feature-icon{background:#e7f9fb;color:#15a7b4}

        .feature-icon svg{width:28px;height:28px}

        .feature h3{
            font-size:17px;
            color:#17385f;
            margin-bottom:5px;
        }

        .feature p{
            color:#7188a3;
            font-size:13px;
            line-height:1.55;
        }

        /* WHY */
        .why{
            background:#f1f8ff;
            padding:64px 0 70px;
            overflow:hidden;
        }

        .why-grid{
            display:grid;
            grid-template-columns:48% 52%;
            align-items:center;
            gap:45px;
        }

        .why h2{
            font-size:32px;
            line-height:1.2;
            color:#102f59;
            margin-bottom:12px;
        }

        .why p{
            color:#607b9a;
            font-size:15px;
            max-width:560px;
        }

        .checks{
            list-style:none;
            margin-top:20px;
            display:grid;
            gap:11px;
        }

        .checks li{
            display:flex;
            align-items:center;
            gap:10px;
            font-size:14px;
            color:#4d698a;
        }

        .check{
            width:19px;
            height:19px;
            border-radius:50%;
            background:#1472df;
            color:white;
            display:grid;
            place-items:center;
            font-size:11px;
            flex:none;
        }

        .why-card{
            background:white;
            border:1px solid #e0edf9;
            border-radius:25px;
            min-height:285px;
            padding:28px;
            box-shadow:0 18px 45px rgba(31,91,145,.08);
            position:relative;
        }

        .why-card:before{
            content:"";
            position:absolute;
            width:240px;
            height:240px;
            border-radius:50%;
            background:#eaf5ff;
            right:-55px;
            top:-65px;
        }

        .mini-dashboard{
            position:relative;
            z-index:2;
            display:grid;
            grid-template-columns:80px 1fr;
            height:230px;
            border:1px solid #dceaf6;
            border-radius:12px;
            overflow:hidden;
            background:#fbfdff;
        }

        .mini-side{background:#0b559f;padding:15px 10px}
        .mini-side i{display:block;height:7px;border-radius:5px;background:rgba(255,255,255,.4);margin:9px 0}
        .mini-side i:first-child{background:white}

        .mini-main{padding:16px}
        .mini-line{height:9px;width:45%;background:#dbeafa;border-radius:8px;margin-bottom:14px}
        .mini-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
        .mini-box{height:52px;background:white;border:1px solid #e4edf6;border-radius:7px}
        .mini-chart{height:95px;background:white;border:1px solid #e4edf6;border-radius:8px;margin-top:10px;position:relative;overflow:hidden}
        .bars{position:absolute;left:20px;right:20px;bottom:15px;height:65px;display:flex;align-items:end;gap:9px}
        .bar{width:15px;background:#7fb5f3;border-radius:4px 4px 0 0}
        .bar:nth-child(1){height:35%}.bar:nth-child(2){height:58%}.bar:nth-child(3){height:45%}.bar:nth-child(4){height:82%}.bar:nth-child(5){height:68%}.bar:nth-child(6){height:95%}

        .security-badge{
            position:absolute;
            right:20px;
            bottom:20px;
            width:65px;
            height:65px;
            background:#0f70dd;
            border:7px solid white;
            border-radius:20px 20px 28px 28px;
            z-index:5;
            display:grid;
            place-items:center;
            color:white;
            box-shadow:0 12px 25px rgba(17,103,223,.25);
        }

        /* CTA */
        .cta{
            padding:70px 0;
            background:white;
        }

        .cta-box{
            background:linear-gradient(135deg,#0c55ae,#1375e6);
            color:white;
            border-radius:25px;
            padding:45px 55px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:30px;
            box-shadow:0 20px 50px rgba(18,98,195,.2);
        }

        .cta-box h2{font-size:30px;line-height:1.2;margin-bottom:7px}
        .cta-box p{opacity:.88;font-size:14px}
        .cta .btn{background:white;color:#0d61c7}

        /* FOOTER */
        footer{
            background:#0d2748;
            color:#d5e2f1;
            padding:35px 0 25px;
        }

        .footer-grid{
            display:grid;
            grid-template-columns:1.5fr 1fr 1fr;
            gap:50px;
        }

        footer .brand-text strong{color:white}
        footer .brand-text span{color:#a9bfd9}

        footer h4{color:white;margin-bottom:12px;font-size:14px}
        footer p, footer li{font-size:13px;color:#a9bfd9}
        footer ul{list-style:none;display:grid;gap:7px}
        footer a:hover{color:white}

        .copyright{
            border-top:1px solid rgba(255,255,255,.1);
            margin-top:28px;
            padding-top:18px;
            font-size:12px;
            color:#89a4c2;
            text-align:center;
        }


        /* HERO FOTO UNIVERSITAS MUHAMMADIYAH BENGKULU */
        .hero-umb{
            min-height:650px;
            position:relative;
            overflow:hidden;
            display:flex;
            align-items:center;
            background:
                linear-gradient(90deg,rgba(7,40,91,.80) 0%,rgba(7,49,105,.55) 42%,rgba(5,53,112,.10) 75%),
                url('gedung.png') center center / cover no-repeat;
        }

        .hero-overlay{
            position:absolute;
            inset:0;
            background:
                linear-gradient(180deg,rgba(4,38,85,.12) 0%,rgba(4,45,98,.04) 55%,rgba(4,33,75,.38) 100%);
            pointer-events:none;
        }

        .hero-content-umb{
            width:min(1180px,92%);
            position:relative;
            z-index:3;
            display:flex;
            align-items:center;
            min-height:650px;
        }

        .hero-copy{
            width:55%;
            padding:50px 0;
            color:white;
        }

        .hero-copy .eyebrow{
            color:white;
            background:rgba(255,255,255,.16);
            border:1px solid rgba(255,255,255,.28);
            backdrop-filter:blur(8px);
        }

        .hero-copy h1{
            color:white;
            font-size:50px;
            line-height:1.12;
            letter-spacing:-1.4px;
            margin-bottom:18px;
            text-shadow:0 3px 20px rgba(0,0,0,.22);
        }

        .hero-copy h1 span{
            display:block;
            color:#8bc4ff;
        }

        .hero-copy p{
            color:rgba(255,255,255,.92);
            font-size:17px;
            max-width:610px;
            margin-bottom:28px;
            text-shadow:0 2px 10px rgba(0,0,0,.25);
        }

        .hero-light-btn{
            color:white !important;
            border-color:rgba(255,255,255,.85) !important;
            background:rgba(255,255,255,.08) !important;
            backdrop-filter:blur(5px);
        }

        .hero-light-btn:hover{
            color:#1167df !important;
            background:white !important;
        }

        .hero-bottom-fade{
            position:absolute;
            z-index:2;
            left:0;
            right:0;
            bottom:0;
            height:85px;
            background:linear-gradient(transparent,rgba(3,28,61,.28));
            pointer-events:none;
        }

        @media(max-width:950px){
            .hero-umb,
            .hero-content-umb{
                min-height:590px;
            }

            .hero-copy{
                width:68%;
            }

            .hero-copy h1{
                font-size:42px;
            }
        }

        @media(max-width:720px){
            .hero-umb{
                min-height:620px;
                background-position:center center;
            }

            .hero-content-umb{
                min-height:620px;
                align-items:flex-end;
                padding-bottom:55px;
            }

            .hero-copy{
                width:100%;
                padding:30px 0;
            }

            .hero-copy h1{
                font-size:35px;
            }

            .hero-copy p{
                font-size:15px;
            }
        }

        @media(max-width:950px){
            .nav-menu{gap:20px;margin-left:10px}
            .hero h1{font-size:40px}
            .hero-inner{grid-template-columns:1fr}
            .hero-visual{justify-content:center;margin-top:15px}
            .feature-grid{grid-template-columns:repeat(3,1fr)}
            .why-grid{grid-template-columns:1fr}
            .why-card{margin-top:10px}
        }

        @media(max-width:720px){
            .navbar{height:68px}
            .nav-menu,.nav-actions{display:none}
            .mobile-toggle{display:block}

            .nav-menu.open{
                display:flex;
                position:absolute;
                left:0;
                right:0;
                top:68px;
                background:white;
                padding:15px 5%;
                flex-direction:column;
                align-items:flex-start;
                gap:0;
                box-shadow:0 15px 25px rgba(0,0,0,.08);
            }

            .nav-menu.open a{padding:10px 0}

            .hero{min-height:auto}
            .hero-inner{padding:55px 0 30px}
            .hero h1{font-size:35px}
            .hero p{font-size:15px}
            .hero-visual{min-height:280px;transform:scale(.82);transform-origin:center top;margin-bottom:-45px}
            .student{display:none}

            .feature-grid{grid-template-columns:1fr 1fr;gap:25px}
            .why{padding:50px 0}
            .why h2{font-size:28px}
            .cta-box{padding:35px 25px;flex-direction:column;align-items:flex-start}
            .footer-grid{grid-template-columns:1fr;gap:25px}
        }

        @media(max-width:450px){
            .brand-text strong{font-size:16px}
            .brand-text span{font-size:10px}
            .brand-icon{width:42px;height:42px}
            .hero h1{font-size:31px}
            .hero-buttons .btn{width:100%}
            .feature-grid{grid-template-columns:1fr}
            .laptop{transform:scale(.72)}
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 64 64" fill="none">
                    <path d="M8 22 32 9l24 13-24 13L8 22Z" fill="currentColor"/>
                    <path d="M17 28v12c0 7 7 12 15 12s15-5 15-12V28L32 36 17 28Z" fill="currentColor" opacity=".9"/>
                    <path d="M56 23v14" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="brand-text">
                <strong>SIM Mahasiswa</strong>
                <span>Sistem Informasi Mahasiswa</span>
            </div>
        </a>

        <ul class="nav-menu" id="navMenu">
            <li><a href="#beranda" class="active">Beranda</a></li>
            <li><a href="#tentang">Tentang</a></li>
            <li><a href="#fitur">Fitur</a></li>
            <li><a href="#kontak">Kontak</a></li>
        </ul>

        <div class="nav-actions">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="login.php" class="btn btn-primary">Daftar</a>
        </div>

        <button class="mobile-toggle" onclick="toggleMenu()" aria-label="Menu">☰</button>
    </div>
</header>

<main>

<section class="hero hero-umb" id="beranda">
    <div class="hero-overlay"></div>

    <div class="hero-content-umb container">
        <div class="hero-copy">
            <div class="eyebrow">Sistem Informasi Mahasiswa</div>

            <h1>
                Kelola Data Mahasiswa
                <span>dengan Lebih Mudah</span>
            </h1>

            <p>
                SIM Mahasiswa Universitas Muhammadiyah Bengkulu membantu
                mengelola data mahasiswa, program studi, fakultas, dan
                pengguna secara terintegrasi, cepat, dan aman.
            </p>

            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    <span>→</span> Masuk ke Sistem
                </a>
                <a href="#fitur" class="btn btn-outline hero-light-btn">
                    Pelajari Lebih Lanjut
                </a>
            </div>
        </div>
    </div>

    <div class="hero-bottom-fade"></div>
</section>

<section class="features" id="fitur">
    <div class="container">
        <div class="feature-grid">

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 11a4 4 0 1 0-3.99-4A4 4 0 0 0 16 11ZM8 11a3.5 3.5 0 1 0-3.49-3.5A3.5 3.5 0 0 0 8 11Zm8 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4ZM8 13c-2.33 0-7 1.17-7 3.5V19h5v-2c0-1.05.51-2 1.38-2.75A8.7 8.7 0 0 1 11 13.2 12 12 0 0 0 8 13Z"/>
                    </svg>
                </div>
                <h3>Manajemen Mahasiswa</h3>
                <p>Kelola data mahasiswa dengan lengkap dan terstruktur.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 4h16v16H4V4Zm3 3v10h2V7H7Zm4 0v10h2V7h-2Zm4 0v10h2V7h-2Z"/>
                    </svg>
                </div>
                <h3>Program Studi</h3>
                <p>Atur informasi program studi dan kurikulum.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 21V9l9-6 9 6v12h-5v-7H8v7H3Zm7-3h4v-4h-4v4Z"/>
                    </svg>
                </div>
                <h3>Fakultas</h3>
                <p>Kelola data fakultas dan unit kerja.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0H5Z"/>
                    </svg>
                </div>
                <h3>Pengguna</h3>
                <p>Kelola akun pengguna dengan hak akses yang aman.</p>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm3 4v2h8V7H8Zm0 4v2h8v-2H8Zm0 4v2h5v-2H8Z"/>
                    </svg>
                </div>
                <h3>Audit Log</h3>
                <p>Pantau aktivitas sistem dengan detail.</p>
            </div>

        </div>
    </div>
</section>

<section class="why" id="tentang">
    <div class="container why-grid">
        <div>
            <div class="eyebrow">Mengapa Memilih Kami?</div>
            <h2>Sistem yang Andal dan Terpercaya</h2>
            <p>
                SIM Mahasiswa dirancang untuk memberikan kemudahan dalam
                pengelolaan data akademik dengan fitur yang lengkap,
                keamanan data yang terjamin, dan tampilan yang user-friendly.
            </p>

            <ul class="checks">
                <li><span class="check">✓</span> Data terpusat dan terintegrasi</li>
                <li><span class="check">✓</span> Keamanan data dengan sistem audit log</li>
                <li><span class="check">✓</span> Akses cepat dan mudah dari berbagai perangkat</li>
            </ul>
        </div>

        <div class="why-card">
            <div class="mini-dashboard">
                <div class="mini-side">
                    <i></i><i></i><i></i><i></i><i></i><i></i>
                </div>
                <div class="mini-main">
                    <div class="mini-line"></div>
                    <div class="mini-cards">
                        <div class="mini-box"></div>
                        <div class="mini-box"></div>
                        <div class="mini-box"></div>
                    </div>
                    <div class="mini-chart">
                        <div class="bars">
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                            <span class="bar"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="security-badge">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3 5 6v5c0 4.5 2.8 8.5 7 10 4.2-1.5 7-5.5 7-10V6l-7-3Z"/>
                    <rect x="9" y="10" width="6" height="5" rx="1"/>
                    <path d="M10 10V8a2 2 0 0 1 4 0v2"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<section class="cta" id="kontak">
    <div class="container">
        <div class="cta-box">
            <div>
                <h2>Siap Mengelola Data Mahasiswa?</h2>
                <p>Masuk ke sistem dan mulai kelola data akademik secara lebih mudah.</p>
            </div>
            <a href="login.php" class="btn">Masuk ke Sistem →</a>
        </div>
    </div>
</section>

</main>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand">
                    <div class="brand-icon">
                        <svg viewBox="0 0 64 64" fill="none">
                            <path d="M8 22 32 9l24 13-24 13L8 22Z" fill="currentColor"/>
                            <path d="M17 28v12c0 7 7 12 15 12s15-5 15-12V28L32 36 17 28Z" fill="currentColor" opacity=".9"/>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <strong>SIM Mahasiswa</strong>
                        <span>Sistem Informasi Mahasiswa</span>
                    </div>
                </div>
                <p style="margin-top:15px;max-width:430px;">
                    Platform pengelolaan data mahasiswa yang terintegrasi
                    untuk mendukung kebutuhan administrasi akademik.
                </p>
            </div>

            <div>
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="#beranda">Beranda</a></li>
                    <li><a href="#tentang">Tentang</a></li>
                    <li><a href="#fitur">Fitur</a></li>
                    <li><a href="#kontak">Kontak</a></li>
                </ul>
            </div>

            <div>
                <h4>Informasi</h4>
                <ul>
                    <li>Universitas Muhammadiyah Bengkulu</li>
                    <li>Jl. Bali, Kampung Bali</li>
                    <li>Bengkulu, Bengkulu</li>
                    <li><a href="login.php">Login Sistem</a></li>
                </ul>
            </div>
        </div>

        <div class="copyright">
            © <?php echo date('Y'); ?> SIM Mahasiswa. All rights reserved.
        </div>
    </div>
</footer>

<script>
function toggleMenu(){
    document.getElementById('navMenu').classList.toggle('open');
}

document.querySelectorAll('.nav-menu a').forEach(function(link){
    link.addEventListener('click', function(){
        document.getElementById('navMenu').classList.remove('open');
    });
});
</script>

</body>
</html>
