<?php
// includes/footer.php
?>
    <style>
        .modern-footer {
            background: linear-gradient(135deg, #2dd4f6 0%, #086a82 100%);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            margin-top: auto;
            position: relative;
        }

        /* Glassmorphism Overlay (Memberikan kedalaman warna) */
        .modern-footer::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: url('data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.05" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E');
            pointer-events: none;
        }

        .footer-grid {
            max-width: 1200px; margin: 0 auto; padding: 70px 40px;
            display: grid; grid-template-columns: 2.5fr 1fr 1.5fr; gap: 60px;
            position: relative; z-index: 1;
        }

        /* Brand Area */
        .footer-brand { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .footer-logo { width: 65px; height: 65px; background: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .footer-logo img { width: 90%; height: auto; }
        .footer-brand-title { font-family: 'Playfair Display', serif; font-size: 40px; font-weight: 700; color: #ffffff; letter-spacing: 1px; }
        .footer-desc { font-size: 14.5px; line-height: 1.8; color: rgba(255, 255, 255, 0.9); font-weight: 300; text-align: justify; }

        /* Widget Titles */
        .footer-title { font-size: 20px; font-weight: 600; margin-bottom: 25px; color: #ffffff; position: relative; display: inline-block; padding-bottom: 8px; }
        .footer-title::after { content: ''; position: absolute; left: 0; bottom: 0; width: 40px; height: 3px; background-color: #ffffff; border-radius: 2px; }

        /* Links List */
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 15px; }
        .footer-links a {
            color: rgba(255, 255, 255, 0.9); text-decoration: none; font-size: 15px; font-weight: 400;
            display: inline-flex; align-items: center; transition: all 0.3s ease;
        }
        .footer-links a::before {
            content: '\f105'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            margin-right: 8px; font-size: 12px; opacity: 0; transform: translateX(-10px); transition: all 0.3s ease;
        }
        .footer-links a:hover { color: #ffffff; transform: translateX(5px); }
        .footer-links a:hover::before { opacity: 1; transform: translateX(0); }

        /* Contact List */
        .contact-list { list-style: none; padding: 0; margin: 0; }
        .contact-item { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px; font-size: 14.5px; font-weight: 400; line-height: 1.6; color: rgba(255, 255, 255, 0.9); }
        .contact-item i { font-size: 18px; margin-top: 3px; color: #ffffff; width: 20px; text-align: center; }

        /* Footer Bottom Bar */
        .footer-bottom { background: rgba(0, 0, 0, 0.15); backdrop-filter: blur(5px); border-top: 1px solid rgba(255, 255, 255, 0.1); position: relative; z-index: 1; }
        .bottom-wrapper { max-width: 1200px; margin: 0 auto; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        
        .copyright { font-size: 14px; font-weight: 300; color: #e2e8f0; display: flex; align-items: center; gap: 8px; }
        
        /* Modern Social Icons with Glow */
        .social-icons { display: flex; gap: 15px; }
        .social-icons a {
            display: flex; justify-content: center; align-items: center;
            width: 38px; height: 38px; background: rgba(255,255,255,0.1); color: #ffffff;
            border-radius: 10px; font-size: 16px; text-decoration: none;
            border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .social-icons a:hover {
            background: #ffffff; color: #086a82; transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 992px) {
            .footer-grid { grid-template-columns: 1fr; gap: 40px; padding: 50px 25px; }
            .bottom-wrapper { flex-direction: column; gap: 20px; text-align: center; justify-content: center; }
        }
    </style>

    <footer class="modern-footer">
        <div class="footer-grid">
            
            <!-- About Section -->
            <div>
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="assets/img/logo.png" alt="Logo Sidera" onerror="this.src='https://ui-avatars.com/api/?name=S+I&background=ffffff&color=111827&bold=true&rounded=true'">
                    </div>
                    <div class="footer-brand-title">SIDERA</div>
                </div>
                <p class="footer-desc">
                    Sistem Informasi Desa Terpadu yang didedikasikan untuk mengoptimalkan pelayanan masyarakat, transparansi data administratif, serta digitalisasi tata kelola pemerintahan desa yang akurat dan responsif.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="footer-title">Layanan</h3>
                <ul class="footer-links">
                    <li><a href="index.php#profil_desa">Profil Desa</a></li>
                    <li><a href="index.php#infografis">Data Kependudukan</a></li>
                    <li><a href="arsip_desa.php">E-Arsip Dokumen</a></li>
                    <li><a href="index.php#timeline">Surat Elektronik</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h3 class="footer-title">Hubungi Kami</h3>
                <ul class="contact-list">
                    <li class="contact-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Kesempuh, Serage, Kec. Praya Barat Daya, Kabupaten Lombok Tengah, Nusa Tenggara Barat. 83571</span>
                    </li>
                    <li class="contact-item">
                        <i class="fa-solid fa-phone"></i>
                        <span>+62 877 6655 4433</span>
                    </li>
                    <li class="contact-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span>pemdes.serage@gmail.com</span>
                    </li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <div class="bottom-wrapper">
                <div class="copyright">
                    <i class="fa-regular fa-copyright"></i>
                    <span>2026 &bull; Created By team of KKN ITKA Serage. All rights reserved.</span>
                </div>
                <div class="social-icons">
                    <a href="#" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.tiktok.com/@cerite_serage?_r=1&_d=eg1263gl974d27&sec_uid=MS4wLjABAAAAFScaNanjPEEpiOn7kL1lvBwQ_UJMbW1DLf88eIg9Xz18SXfxpmoX52xZ7HxQUsjm&share_author_id=7612228915465045013&sharer_language=id&source=h5_t&u_code=f285dfaljl4m4g&timestamp=1788144722&user_id=7612228915465045013&sec_user_id=MS4wLjABAAAAFScaNanjPEEpiOn7kL1lvBwQ_UJMbW1DLf88eIg9Xz18SXfxpmoX52xZ7HxQUsjm&item_author_type=1&utm_source=whatsapp&utm_campaign=client_share&utm_medium=android&share_iid=7603202694362302229&share_link_id=f937dd78-eaa0-42e8-801f-9ea97d99f8b7&share_app_id=1180&ugbiz_name=ACCOUNT&ug_btm=b8727%2Cb7360&social_share_type=5&enable_checksum=1" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>