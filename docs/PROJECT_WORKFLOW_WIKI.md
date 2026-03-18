# Wiki Workflow Hiện Tại Của Dự Án

## 1. Mục tiêu tài liệu

Tài liệu này giúp PM, tester và người mới vào dự án nắm nhanh:

- dự án đang giải quyết bài toán gì;
- các thành phần nghiệp vụ chính đang có;
- luồng thao tác hiện tại từ đăng ký đến vận hành kho;
- trạng thái nào làm thay đổi tồn kho hoặc thanh toán;
- nên test theo thứ tự nào để ra đúng kết quả.

Tài liệu này mô tả workflow hiện tại của hệ thống theo implementation đang có trong codebase, không phải định hướng tương lai.

---

## 2. Bức tranh tổng thể

Đây là một hệ thống quản lý kho mini theo mô hình multi-tenant.

Mỗi tenant trong hệ thống là một `business` và có thể có:

- người dùng và membership riêng;
- danh mục sản phẩm riêng;
- kho riêng;
- khách hàng và nhà cung cấp riêng;
- chứng từ nhập kho, xuất kho, kiểm kho, thanh toán riêng;
- tồn kho hiện tại riêng.

Hệ thống dùng `JWT` cho xác thực API và mọi dữ liệu nghiệp vụ đều đi theo `business scope`.

Nói ngắn gọn:

1. Người dùng đăng ký hoặc đăng nhập.
2. Hệ thống xác định business đang thao tác.
3. Người dùng tạo dữ liệu master.
4. Người dùng tạo chứng từ nhập, bán, xuất, kiểm kho, thanh toán.
5. Hệ thống đồng bộ ledger tồn kho và bảng tồn hiện tại.

---

## 3. Các nhóm dữ liệu chính

### 3.1. Nhóm nền tảng

- `businesses`: tenant hoặc shop
- `users`: tài khoản hệ thống
- `business_users`: user thuộc business nào, giữ role và trạng thái membership
- `business_modules`: module nào đang được bật cho business

### 3.2. Nhóm master data

- `units`: đơn vị tính
- `warehouses`: kho
- `products`: sản phẩm
- `customers`: khách hàng
- `suppliers`: nhà cung cấp

### 3.3. Nhóm chứng từ bán hàng và kho

- `orders` + `order_items`
- `stock_in` + `stock_in_items`
- `stock_out` + `stock_out_items`
- `stock_adjustments` + `stock_adjustment_items`
- `payments`

### 3.4. Nhóm tồn kho

- `inventory_movements`: nguồn sự thật của tồn kho
- `current_stocks`: bảng tổng hợp để đọc tồn nhanh

---

## 4. Vai trò người dùng hiện tại

Hiện tại hệ thống đang vận hành với 3 vai trò chính:

- `owner`
- `manager`
- `staff`

### 4.1. Cách hiểu nhanh

- `owner`: toàn quyền trong business hiện tại
- `manager`: có nhiều quyền quản lý hơn staff
- `staff`: dùng được các luồng vận hành cơ bản

### 4.2. Điểm quan trọng

Quyền hiện tại đang map theo role ở tầng ứng dụng, chưa dùng một hệ RBAC động hoàn chỉnh từ database.

PM/tester nên hiểu:

- quyền là theo `business`, không phải toàn hệ thống;
- cùng một user có thể về mặt thiết kế tham gia nhiều business;
- dữ liệu user và membership là 2 lớp khác nhau.

---

## 5. Workflow nghiệp vụ hiện tại

## 5.1. Đăng ký và đăng nhập

### Đăng ký

Khi gọi `POST /api/auth/register`:

1. Hệ thống tạo user hệ thống.
2. Hệ thống tạo business mặc định cho user đó.
3. Hệ thống tạo membership `owner`.
4. Hệ thống bật các module mặc định:
   - products
   - inventory
   - orders
   - customers
   - suppliers
   - payments
5. Hệ thống trả về `access_token`.

### Đăng nhập

Khi gọi `POST /api/auth/login`:

1. Hệ thống tìm user theo email.
2. Kiểm tra password.
3. Kiểm tra user đang active.
4. Kiểm tra user còn ít nhất 1 membership active.
5. Trả về JWT token.

### Điểm tester cần lưu ý

- user bị khóa thì không đăng nhập được;
- user không còn membership active thì không đăng nhập được;
- token mang theo `business_id`, `role`, `is_owner`.

---

## 5.2. Thiết lập dữ liệu master

Sau khi đăng nhập, thứ tự setup khuyến nghị là:

1. `units`
2. `warehouses`
3. `products`
4. `customers`
5. `suppliers`
6. `users` trong business nếu cần phân vai

### Giải thích

- `product` bắt buộc cần `unit`
- các chứng từ kho cần `warehouse`
- `order` thường cần `customer`
- `stock_in` thường cần `supplier`
- `payment` có thể liên kết `customer`, `supplier`, `order`, `stock_in`

### Gợi ý test nhanh

Nếu chưa có master data, tester gần như không test được các luồng chứng từ phía sau.

---

## 5.3. Workflow nhập kho

Endpoint chính:

- `POST /api/stock-in`
- `PUT /api/stock-in/{id}`
- `POST /api/stock-in/{id}/confirm`
- `POST /api/stock-in/{id}/cancel`

### Luồng hiện tại

1. Người dùng tạo phiếu nhập kho.
2. Hệ thống kiểm tra `warehouse` và `supplier` có thuộc đúng business không.
3. Hệ thống build snapshot item nhập kho.
4. Hệ thống tính `subtotal`, `discount_amount`, `total_amount`.
5. Nếu chứng từ ở trạng thái `confirmed`, hệ thống tạo movement tồn kho.

### Trạng thái và tác động

- `draft`: chưa tác động tồn kho
- `confirmed`: cộng tồn kho
- `cancelled`: gỡ tác động tồn kho của chứng từ đó

### Kết luận nghiệp vụ

Phiếu nhập kho là nguồn vào của tồn và cũng là nơi cung cấp giá vốn đầu vào.

---

## 5.4. Workflow đơn hàng

Endpoint chính:

- `POST /api/orders`
- `PUT /api/orders/{id}`
- `POST /api/orders/{id}/confirm`
- `POST /api/orders/{id}/cancel`

### Luồng hiện tại

1. Người dùng tạo đơn hàng.
2. Hệ thống kiểm tra `warehouse`, `customer`.
3. Hệ thống build snapshot item.
4. Hệ thống tính lại tiền ở backend:
   - `subtotal`
   - `discount_amount`
   - `shipping_amount`
   - `total_amount`
5. Đơn hàng được lưu với `status` và `payment_status`.

### Điểm quan trọng

Đơn hàng hiện tại không tự động trừ kho.

Muốn trừ kho thực tế, phải đi qua `stock_out`.

### Kết luận nghiệp vụ

`order` là chứng từ bán hàng và doanh thu.
`stock_out` mới là chứng từ vận hành kho.

---

## 5.5. Workflow xuất kho

Endpoint chính:

- `POST /api/stock-out`
- `PUT /api/stock-out/{id}`
- `POST /api/stock-out/{id}/confirm`
- `POST /api/stock-out/{id}/cancel`

### Luồng hiện tại

1. Người dùng tạo phiếu xuất kho.
2. Hệ thống kiểm tra `warehouse`, `order`, `customer`.
3. Hệ thống build snapshot item xuất kho.
4. Hệ thống tính `subtotal` và `total_amount`.
5. Nếu chứng từ `confirmed`, hệ thống ghi movement âm vào ledger.
6. Hệ thống tính giá vốn theo moving average.

### Trạng thái và tác động

- `draft`: chưa trừ kho
- `confirmed`: trừ kho
- `cancelled`: bỏ tác động trừ kho trước đó

### Điểm rất quan trọng

Giá vốn xuất không lấy cứng từ giá bán.

Hệ thống sẽ tính lại giá vốn theo tồn hiện có và moving average trong `InventoryLedgerService`.

---

## 5.6. Workflow kiểm kho và điều chỉnh tồn

Endpoint chính:

- `POST /api/stock-adjustments`
- `PUT /api/stock-adjustments/{id}`
- `POST /api/stock-adjustments/{id}/confirm`
- `POST /api/stock-adjustments/{id}/cancel`

### Luồng hiện tại

1. Người dùng chọn kho và danh sách sản phẩm cần kiểm.
2. Hệ thống lấy:
   - `expected_qty` từ request hoặc tồn hiện tại
   - `counted_qty` từ số kiểm thực tế
3. Hệ thống tính:
   - `difference_qty = counted_qty - expected_qty`
4. Nếu chứng từ `confirmed`, hệ thống tạo movement điều chỉnh.

### Ý nghĩa nghiệp vụ

- `difference_qty > 0`: tăng tồn
- `difference_qty < 0`: giảm tồn
- `difference_qty = 0`: không tạo chênh lệch thực tế

### Khi nào dùng

Luồng này dùng khi tồn hệ thống lệch so với tồn thực tế.

---

## 5.7. Workflow thanh toán

Endpoint chính:

- `POST /api/payments`
- `PUT /api/payments/{id}`
- `POST /api/payments/{id}/confirm`
- `POST /api/payments/{id}/cancel`

### Luồng hiện tại

Payment có thể là:

- `direction = in`: thu tiền
- `direction = out`: chi tiền

Payment có thể liên kết với:

- `order`
- `stock_in`
- `customer`
- `supplier`

### Cách hệ thống xử lý

1. Kiểm tra tất cả khóa ngoại có đúng business hiện tại không.
2. Tạo hoặc cập nhật payment.
3. Nếu payment liên kết `order`, hệ thống sync lại:
   - `paid_amount`
   - `payment_status`

### Quy ước payment status của order

- `unpaid`: chưa thu gì
- `partial`: thu một phần
- `paid`: thu đủ hoặc vượt tổng tiền đơn

### Điểm cần hiểu rõ

Payment không làm thay đổi tồn kho.
Payment chỉ ảnh hưởng nghiệp vụ tài chính của đơn hàng hoặc nhập hàng.

---

## 5.8. Workflow xem tồn kho

Endpoint chính:

- `GET /api/inventory/stocks`

### Hệ thống đang làm gì

Màn hình tồn kho không đọc trực tiếp từ `inventory_movements`.

Nó đọc từ `current_stocks`, là bảng tổng hợp đã được rebuild từ ledger.

### Ý nghĩa

- `inventory_movements`: nguồn sự thật
- `current_stocks`: bảng đọc nhanh cho UI

### Kết luận

Nếu tester thấy tồn sai, cần kiểm tra theo thứ tự:

1. chứng từ nào đã `confirmed`
2. movement đã được tạo chưa
3. current stock đã được rebuild đúng chưa

---

## 6. Các nguyên tắc nghiệp vụ quan trọng

## 6.1. Business scope là bắt buộc

Mọi request nghiệp vụ đều đi theo `business_id`.

Nếu frontend không truyền `business_id`, hệ thống có thể tự suy ra từ membership active hiện tại.

Điều này giúp tránh đọc hoặc ghi nhầm dữ liệu giữa các tenant.

## 6.2. Snapshot item

Các bảng item như:

- `order_items`
- `stock_in_items`
- `stock_out_items`
- `stock_adjustment_items`

đều lưu snapshot tên, SKU, giá hoặc số lượng tại thời điểm phát sinh.

Mục đích:

- dữ liệu lịch sử không bị đổi khi catalog thay đổi;
- báo cáo và đối soát dễ hơn.

## 6.3. Confirm và cancel mới là điểm có tác động lớn

Trong phần lớn luồng chứng từ kho:

- tạo `draft` không làm thay đổi tồn;
- `confirm` mới làm phát sinh tác động;
- `cancel` sẽ gỡ hoặc rebuild lại tác động đó.

## 6.4. Ledger là nguồn sự thật

Hệ thống đang đi theo triết lý:

- ledger trước;
- read model sau.

Nghĩa là:

1. tạo hoặc xóa movement trong `inventory_movements`
2. rebuild `current_stocks`

---

## 7. Thứ tự test khuyến nghị cho tester

## 7.1. Happy path đầy đủ

1. Đăng ký user mới
2. Đăng nhập
3. Tạo đơn vị tính
4. Tạo kho
5. Tạo sản phẩm
6. Tạo khách hàng
7. Tạo nhà cung cấp
8. Tạo phiếu nhập kho ở trạng thái `confirmed`
9. Kiểm tra tồn kho tăng
10. Tạo đơn hàng
11. Tạo phiếu xuất kho liên kết đơn hàng và `confirm`
12. Kiểm tra tồn kho giảm
13. Tạo payment `in` cho order
14. Kiểm tra `paid_amount` và `payment_status` của order
15. Tạo stock adjustment nếu muốn test chênh lệch tồn

## 7.2. Test trạng thái

Nên test riêng:

- `draft -> confirmed`
- `confirmed -> cancelled`
- update chứng từ sau đó confirm lại

## 7.3. Test phân quyền

Nên test:

- owner thấy và làm được hết
- manager làm được phần quản lý phù hợp
- staff bị giới hạn ở một số route

---

## 8. Gợi ý checklist cho PM

PM có thể dùng checklist sau khi demo:

- Có đăng ký và vào hệ thống được không
- Có tạo business mặc định cho owner không
- Có quản lý master data đủ để vận hành không
- Có nhập kho và tăng tồn đúng không
- Có tạo đơn hàng và tính tiền đúng không
- Có xuất kho và giảm tồn đúng không
- Có kiểm kho và điều chỉnh tồn đúng không
- Có thu tiền và cập nhật trạng thái thanh toán đúng không
- Có xem được tồn kho hiện tại nhanh không
- Có kiểm soát quyền theo module hoặc role không

---

## 9. Giới hạn hiện tại của workflow

Đây là các giới hạn cần hiểu đúng khi test:

- đơn hàng chưa tự động sinh xuất kho;
- payment summary chủ yếu sync cho `order`;
- quyền đang map cứng theo role ở tầng code;
- workflow trạng thái chưa phải state machine chặt;
- hệ thống đang tối ưu cho MVP, ưu tiên dễ hiểu và dễ maintain.

---

## 10. Kết luận ngắn gọn

Nếu cần hiểu dự án thật nhanh, có thể nhớ theo 1 câu:

`Auth -> Business -> Master Data -> Nhập kho -> Đơn hàng -> Xuất kho -> Thanh toán -> Kiểm kho -> Xem tồn`

Trong đó:

- `stock_in`, `stock_out`, `stock_adjustment` là các chứng từ tác động trực tiếp tới tồn;
- `inventory_movements` là nguồn sự thật;
- `current_stocks` là bảng để UI đọc nhanh;
- mọi thứ luôn phải đi theo `business scope`.
