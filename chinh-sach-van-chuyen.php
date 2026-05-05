<?php
require_once __DIR__ . '/core/auth.php';
$pageTitle = 'Chính sách vận chuyển - PIXCAM';
require_once __DIR__ . '/includes/header.php';
?>
<div style="max-width:900px;margin:40px auto;padding:0 20px;line-height:1.8;">
  <h1 style="font-size:28px;margin-bottom:30px;color:#333;">Chính Sách Vận Chuyển</h1>

  <section style="background:#fff;border-radius:8px;padding:30px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color:#ee5022;margin-bottom:15px;">1. Phí vận chuyển</h2>
    <table style="width:100%;border-collapse:collapse;font-size:14px;">
      <thead>
        <tr style="background:#f9f9f9;">
          <th style="padding:12px;text-align:left;border:1px solid #eee;">Khu vực</th>
          <th style="padding:12px;text-align:left;border:1px solid #eee;">Thời gian</th>
          <th style="padding:12px;text-align:left;border:1px solid #eee;">Phí vận chuyển</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding:12px;border:1px solid #eee;">Nội thành TP.HCM & Hà Nội</td>
          <td style="padding:12px;border:1px solid #eee;">1-2 ngày</td>
          <td style="padding:12px;border:1px solid #eee;color:#ee5022;font-weight:600;">30.000 ₫</td>
        </tr>
        <tr style="background:#f9f9f9;">
          <td style="padding:12px;border:1px solid #eee;">Tỉnh thành khác</td>
          <td style="padding:12px;border:1px solid #eee;">3-5 ngày</td>
          <td style="padding:12px;border:1px solid #eee;color:#ee5022;font-weight:600;">30.000 ₫</td>
        </tr>
        <tr>
          <td style="padding:12px;border:1px solid #eee;">Vùng sâu, vùng xa</td>
          <td style="padding:12px;border:1px solid #eee;">5-7 ngày</td>
          <td style="padding:12px;border:1px solid #eee;color:#ee5022;font-weight:600;">30.000 ₫</td>
        </tr>
      </tbody>
    </table>
  </section>

  <section style="background:#fff;border-radius:8px;padding:30px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color:#ee5022;margin-bottom:15px;">2. Đơn vị vận chuyển</h2>
    <p>PIXCAM hợp tác với các đơn vị vận chuyển uy tín: GHN, GHTK, J&T Express để đảm bảo hàng hóa được giao đến tay khách hàng an toàn và đúng hạn.</p>
  </section>

  <section style="background:#fff;border-radius:8px;padding:30px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color:#ee5022;margin-bottom:15px;">3. Theo dõi đơn hàng</h2>
    <p>Sau khi đặt hàng thành công, bạn có thể theo dõi trạng thái đơn hàng tại trang <a href="/shop-php/order-history.php" style="color:#ee5022;">Lịch sử mua hàng</a>.</p>
  </section>

  <section style="background:#fff;border-radius:8px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color:#ee5022;margin-bottom:15px;">4. Liên hệ</h2>
    <p>📧 Email: shipping@pixcam.com</p>
    <p>📞 Hotline: 1800 xxxx (miễn phí, 8h-22h)</p>
  </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>