# Unresolved Security and Production Issues

## Tổng quan
Tài liệu này ghi lại các rủi ro bảo mật, vận hành production và tối ưu hiệu năng **chưa xử lý**.  
Mục tiêu: dùng làm checklist bắt buộc cho team trước khi deploy production.

> Phạm vi: chỉ tổng hợp vấn đề và hướng xử lý, không thay đổi logic/code/config trong tài liệu này.

## Must fix before production

- [ ] **Lộ thông tin exception ra client**
  - Hiện trạng: API có nguy cơ trả `message`, `file`, `line` khi exception.
  - Rủi ro: lộ thông tin nội bộ, hỗ trợ attacker dò cấu trúc hệ thống.
  - Yêu cầu xử lý: production chỉ trả lỗi chung; chi tiết ghi log server.

- [ ] **Nguy cơ leo thang quyền user (role/owner)**
  - Hiện trạng: manager có thể gửi `role=owner` hoặc `is_owner=true`.
  - Rủi ro: user không đủ quyền tự nâng đặc quyền owner.
  - Yêu cầu xử lý: bổ sung rule/policy rõ ràng; chỉ owner/admin hợp lệ mới được gán/chỉnh quyền owner.

- [ ] **Route debug public `/api/test`**
  - Hiện trạng: route debug đang public hoặc có nguy cơ không được bảo vệ đúng mức.
  - Rủi ro: tăng attack surface và rò rỉ hành vi nội bộ.
  - Yêu cầu xử lý: xoá route khỏi production hoặc bọc middleware auth/internal.

- [ ] **Login/register thiếu rate limit**
  - Hiện trạng: auth public endpoints có nguy cơ chưa có throttle đủ chặt.
  - Rủi ro: brute-force, credential stuffing.
  - Yêu cầu xử lý: thêm throttle/rate limit cho endpoints auth public.

- [ ] **JWT error message quá chi tiết**
  - Hiện trạng: có nguy cơ trả trực tiếp exception message JWT cho client.
  - Rủi ro: lộ thông tin xác thực token.
  - Yêu cầu xử lý: client chỉ nhận message chung (ví dụ `Invalid token`).

- [ ] **Thiếu business-scope validation ở một số FK**
  - Hiện trạng: một số `Rule::exists` chưa ràng theo `business_id`.
  - Trọng điểm: `warehouse_id` / `product_id` / `unit_id` trong update warehouse document.
  - Rủi ro: tham chiếu chéo tenant/business.
  - Yêu cầu xử lý: đảm bảo mọi validate FK đều scope theo `business_id`.

- [ ] **Docker/compose đang dev-oriented, chưa an toàn production**
  - Hiện trạng: `docker-compose.compare.yml` mang tính local/dev.
  - Rủi ro: cấu hình debug, service expose và secret yếu không phù hợp production.
  - Yêu cầu xử lý:
    - tách compose production riêng;
    - không để `APP_DEBUG=true`;
    - không expose DB/phpMyAdmin public;
    - không dùng credential yếu/hardcoded;
    - app container chạy non-root.

## Should fix before production

- [ ] **Một số lỗi runtime/nghiệp vụ cần kiểm tra và chốt hành vi**
  - Delete warehouse document có thể lỗi do base repository chưa wire đúng.
  - Inventory opening partial update có thể lỗi khi thiếu `warehouse_id`/`product_id`.
  - Destroy inventory opening cần xác nhận có thực sự xoá hay chưa.

- [ ] **Dependency/security audit bắt buộc trước release**
  - Chạy lại `composer audit` ngay trước production.
  - Vá các package có advisory mức High/Critical.
  - Kiểm tra và thay thế package abandoned nếu ảnh hưởng runtime/security.

## Nice to have

- [ ] **Performance/optimization hậu kỳ**
  - Whitelist `sort_by` theo từng endpoint để tránh query xấu.
  - Tránh `whereRaw(REPLACE(...))` vì làm giảm khả năng tận dụng index.
  - Cân nhắc Redis cho cache/session/queue ở production.
  - Commit npm lockfile và dùng `npm ci` để build reproducible.

## Ghi chú Docker production

- [ ] Không dùng trực tiếp `docker-compose.compare.yml` cho production.
- [ ] Tạo file compose production tối giản, chỉ giữ service cần thiết.
- [ ] Không publish cổng DB/public admin tool ra Internet.
- [ ] Quản lý secret qua env/secret manager; tránh hardcoded credential.
- [ ] App chạy non-root, image gọn, tắt debug, logging theo chuẩn production.

## Checklist xác nhận trước deploy

- [ ] Đã tắt debug production và xác nhận không lộ chi tiết exception ra client.
- [ ] Đã chặn leo thang quyền owner qua API user management.
- [ ] Đã gỡ hoặc khoá route debug/internal.
- [ ] Đã bật rate limit cho login/register.
- [ ] Đã chuẩn hoá JWT error response (không lộ exception nội bộ).
- [ ] Đã rà soát toàn bộ `Rule::exists` theo business scope.
- [ ] Đã kiểm thử lại các luồng delete/update có nguy cơ runtime lỗi.
- [ ] Đã chạy lại `composer audit` và xử lý advisory nghiêm trọng.
- [ ] Đã chốt phương án Docker production (non-root, no public DB/phpMyAdmin, secret an toàn).
- [ ] Đã thống nhất tiêu chí Go/No-Go với team trước release.
