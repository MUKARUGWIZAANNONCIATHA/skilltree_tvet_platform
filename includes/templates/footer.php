<?php
/**
 * Site Footer Template - Minimal & Essential
 * Path: /includes/templates/footer.php
 */
?>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <div class="footer-logo">
                    <span class="logo-icon">🌳</span>
                    <span>SkillTree<span>TVET</span></span>
                </div>
                <p>Empowering Rwandan youth with practical skills for the future.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

           <div class="footer-col">
    <h4>Quick Links</h4>
    <ul>
        <li><a href="/student/dashboard.php">Dashboard</a></li>
        <li><a href="/student/my-learning.php">My Learning</a></li>
        <li><a href="/student/skill-tree.php">Skill Tree</a></li>
        <li><a href="/student/internships.php">Internships</a></li>
        <li><a href="/student/companies.php">Companies</a></li>   <!-- NEW LINK -->
        <li><a href="/student/community.php">Community</a></li>
    </ul>
</div>

            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="/student/help.php">Help Center</a></li>
                    <li><a href="/faq.php">FAQ</a></li>
                    <li><a href="/privacy.php">Privacy Policy</a></li>
                    <li><a href="/terms.php">Terms of Use</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contact</h4>
                <p><i class="fas fa-envelope"></i> <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a></p>
                <p><i class="fas fa-phone"></i> <a href="tel:+250782513700">+250 782 513 700</a></p>
                <p><i class="fas fa-map-marker-alt"></i> Gisagara, Rwanda</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SkillTree TVET Platform. All rights reserved.</p>
        </div>
    </footer>

    <style>
        .footer {
            background: #1a1a2e;
            color: #ccc;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            padding: 0 24px;
        }
        .footer-logo {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .footer-logo .logo-icon {
            font-size: 24px;
        }
        .footer-logo span:last-child {
            color: #ff8c42;
        }
        .footer-col p {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .footer-col a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-col a:hover {
            color: #ff8c42;
        }
        .footer-col h4 {
            color: white;
            margin-bottom: 18px;
            font-size: 18px;
            font-weight: 600;
        }
        .footer-col ul {
            list-style: none;
            padding: 0;
        }
        .footer-col ul li {
            margin-bottom: 8px;
        }
        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }
        .social-links a {
            width: 34px;
            height: 34px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .social-links a:hover {
            background: #ff8c42;
            transform: translateY(-2px);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 25px;
            margin-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 13px;
        }
        @media (max-width: 768px) {
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .social-links {
                justify-content: center;
            }
        }
    </style>

    <script>
        // Mobile menu toggle (unchanged)
        document.querySelector('.nav-toggle')?.addEventListener('click', function() {
            document.querySelector('.nav-menu')?.classList.toggle('active');
        });
    </script>
</body>
</html>