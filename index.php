<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<title>Selamat Datang - Calon Dewan Ambalan 2026</title>
	<style>
		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		body {
			min-height: 100vh;
			min-height: 100dvh;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: radial-gradient(circle at 10% 20%, #4c1d95 0%, #6b21a8 45%, #7e22ce 100%);
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
		}

		.welcome-card {
			width: 100%;
			max-width: 500px;
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.16);
			border-radius: 24px;
			backdrop-filter: blur(10px);
			-webkit-backdrop-filter: blur(10px);
			padding: 40px 30px;
			text-align: center;
			box-shadow: 0 25px 40px rgba(0, 0, 0, 0.25);
		}

		.logo {
			width: 110px;
			height: 110px;
			object-fit: contain;
			margin-bottom: 20px;
			filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.35));
		}

		h1 {
			font-size: 28px;
			margin-bottom: 16px;
			line-height: 1.3;
			font-weight: 700;
		}

		p {
			font-size: 16px;
			line-height: 1.7;
			color: rgba(255, 255, 255, 0.9);
			margin-bottom: 28px;
			font-weight: 500;
		}

		.btn-login {
			display: inline-block;
			text-decoration: none;
			color: #ffffff;
			background: linear-gradient(135deg, #fb7185 0%, #f43f5e 100%);
			padding: 14px 40px;
			border-radius: 12px;
			font-weight: 700;
			letter-spacing: 0.2px;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		.btn-login:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 24px rgba(244, 63, 94, 0.35);
		}

		/* ============================================
		   KHUSUS MOBILE / HANDPHONE (max-width: 768px)
		   ============================================ */
		@media screen and (max-width: 768px) {
			body {
				padding: 15px;
				align-items: center;
			}

			.welcome-card {
				max-width: 100%;
				padding: 30px 20px;
				border-radius: 20px;
			}

			.logo {
				width: 80px;
				height: 80px;
				margin-bottom: 16px;
			}

			h1 {
				font-size: 24px;
				font-weight: 700;
				margin-bottom: 14px;
				line-height: 1.3;
			}

			p {
				font-size: 16px;
				font-weight: 500;
				line-height: 1.7;
				margin-bottom: 22px;
			}

			.btn-login {
				display: block;
				width: 100%;
				padding: 14px 20px;
			}
		}

		/* ============================================
		   KHUSUS DESKTOP (min-width: 769px)
		   ============================================ */
		@media screen and (min-width: 769px) {
			.welcome-card {
				max-width: 600px;
				padding: 40px 35px;
			}

			.logo {
				width: 110px;
				height: 110px;
				margin-bottom: 24px;
			}

			h1 {
				font-size: 32px;
				font-weight: 700;
				margin-bottom: 18px;
				line-height: 1.3;
			}

			p {
				font-size: 16px;
				font-weight: 500;
				line-height: 1.8;
				margin-bottom: 28px;
			}

			.btn-login {
				padding: 14px 45px;
				font-size: 16px;
			}
		}

		/* Untuk HP sangat kecil */
		@media screen and (max-width: 360px) {
			h1 {
				font-size: 20px;
			}
			p {
				font-size: 14px;
			}
		}
	</style>
</head>
<body>
	<main class="welcome-card">
		<img src="assets/logo-da.png" alt="Logo Dewan Ambalan" class="logo">
		<h1>Selamat Datang <br> Calon Dewan Ambalan 2026</h1>
		<p>
			Website ini adalah website yang dirancang sebagai website penghitungan nilai dan penentuan keputusan <span style="color: #00ff00; font-weight: 700;">Lulus</span> / <span style="color: #ff0000; font-weight: 700;">Tidak Lulus</span> kalian semua para Calon Dewan Ambalan 2026. Di dalam website ini, kalian dapat melihat rincian nilai dari setiap rintangan dan proses yang telah berhasil kalian lewati untuk menjadi Dewan Ambalan Sejati.
		</p>
		<a href="user/login.php" class="btn-login">Masuk</a>
	</main>
</body>
</html>