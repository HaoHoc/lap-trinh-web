<footer>
    <div class="footer_container">
        <div class="logo_footer">
            <img src="/shop-php/assets/img/home/logo.svg" alt="Pixcam Logo" class="logo_footerIcon">
        </div>
        <div class="contact_footer">

            <div class="footer-column">
                <h4 class="title_contact">LIÊN HỆ</h4>
                <ul class="content_contact">
                    <li class="address_contact">
                        <i class="fa-solid fa-location-dot"></i> 02, Võ Oanh, Bình Thạnh, TPHCM
                    </li>
                    <li class="address_contact">
                        <i class="fa-solid fa-phone"></i> Hotline: 0336673831
                    </li>
                    <li class="address_contact">
                        <i class="fa-solid fa-envelope"></i> Email: pixcam@gmail.com
                    </li>
                </ul>
            </div>

            <div class="footer-column">
                <h4 class="title_contact">CHÍNH SÁCH</h4>
                <ul class="content_contact">
                    <li class="address_contact">
                        <a href="/shop-php/chinh-sach-thanh-vien.php">Chính sách thành viên</a>
                    </li>
                    <li class="address_contact">
                        <a href="/shop-php/chinh-sach-doi-tra.php">Chính sách đổi trả</a>
                    </li>
                    <li class="address_contact">
                        <a href="/shop-php/chinh-sach-van-chuyen.php">Chính sách vận chuyển</a>
                    </li>
                </ul>
            </div>

            <div class="footer-column">
                <h4 class="title_contact">ĐĂNG KÝ NHẬN TIN</h4>
                <ul class="content_contact">
                    <li class="address_contact">
                        Nhận thông tin sản phẩm mới nhất và các chương trình khuyến mại.
                    </li>
                </ul>
            </div>

            <div class="footer-column">
                <h4 class="title_contact">KẾT NỐI</h4>
                <ul class="social_links">
                    <li>
                        <a href="#" aria-label="Facebook">
                            <i class="fa-brands fa-facebook"></i>
                        </a>
                    </li>
                    <li>
                        <a href="#" aria-label="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</footer>

<!-- Nút "Lên đầu trang" (thay useState showBackToTop) -->
<button id="backToTop" title="Lên đầu trang" aria-label="Back to top"
        style="display:none; position:fixed; bottom:30px; right:30px; z-index:999; cursor:pointer;">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Hiện/ẩn nút Back to Top khi scroll (thay useEffect + useState)
const backToTopBtn = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
    if (backToTopBtn) {
        backToTopBtn.style.display = window.scrollY > 300 ? 'block' : 'none';
    }
});
backToTopBtn?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>

</body>
</html>