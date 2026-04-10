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

## 5.1. Xác thực và business context

Endpoint xác thực hiện tại:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

### Luồng đăng ký

Khi gọi `POST /api/auth/register`:

1. Hệ thống tạo `user` hệ thống.
2. Hệ thống tạo `business` mặc định cho user đó.
3. Hệ thống tạo membership `owner` trong `business_users`.
4. Hệ thống bật sẵn các module core:
   - `products`
   - `inventory`
   - `orders`
   - `customers`
   - `suppliers`
   - `payments`
5. Hệ thống trả về `access_token`.

### Luồng đăng nhập

Khi gọi `POST /api/auth/login`:

1. Hệ thống tìm user theo email.
2. Kiểm tra password.
3. Kiểm tra `users.is_active = true`.
4. Kiểm tra user còn ít nhất một membership `active`.
5. Hệ thống cập nhật `last_login_at`.
6. Trả về JWT token.

### Business context được resolve như thế nào

Mọi API nghiệp vụ đều đi qua `BusinessContext`.

Thứ tự resolve hiện tại:

1. Nếu request gửi `business_id` và user có quyền trong business đó thì dùng giá trị này.
2. Nếu request không gửi `business_id`, hệ thống lấy business active hiện tại từ JWT context.
3. Nếu không có user đăng nhập nhưng có `business_id`, hệ thống vẫn cho phép dùng trong test hoặc script nội bộ.
4. Nếu vẫn không xác định được, request bị lỗi `Business context is required`.

### Điểm tester cần lưu ý

- mọi route nghiệp vụ đều đi sau middleware `jwt`;
- phần lớn route còn đi tiếp qua middleware `permission:{module},{action}`;
- `logout` dùng cơ chế blacklist token trong cache, không phải session server truyền thống;
- quyền đang được map ở tầng ứng dụng từ membership hiện tại.

---

## 5.2. Thiết lập master data và quản lý user trong business

Sau khi đăng nhập, thứ tự setup khuyến nghị hiện tại là:

1. `units`
2. `warehouses`
3. `categories`
4. `products`
5. `customers`
6. `suppliers`
7. `users` trong business nếu cần phân vai

### Các endpoint master data hiện có

- `units`
- `warehouses`
- `categories`
- `products`
- `customers`
- `suppliers`
- `users`

Với hầu hết master data, hệ thống đang dùng cùng một pattern:

1. resolve `business_id`;
2. query hoặc ghi dữ liệu theo đúng business hiện tại;
3. validate format ở FormRequest;
4. kiểm tra chéo khóa ngoại theo business ở tầng service nếu có;
5. trả record đã eager load relation cần thiết.

### Các điểm khác biệt cần hiểu

- `units`, `warehouses`, `categories` hiện tự gán `is_active = true` nếu không gửi.
- `products` bắt buộc có `unit_id`, `category_id` là optional.
- `customers` và `suppliers` hiện là master data CRUD khá thẳng, chủ yếu dùng để liên kết chứng từ.
- `users` là luồng đặc biệt vì tách 2 lớp dữ liệu:
  - bảng `users`: tài khoản hệ thống
  - bảng `business_users`: role, status, is_owner theo từng business

### Workflow user trong business

Khi tạo user bằng `POST /api/users`:

1. Hệ thống tạo tài khoản ở bảng `users`.
2. Password được hash tại backend.
3. Hệ thống tạo membership trong `business_users`.
4. Role mặc định là `staff` nếu không gửi.
5. Membership status mặc định là `active` nếu không gửi.

Khi cập nhật user bằng `PUT /api/users/{id}`:

- dữ liệu tài khoản và dữ liệu membership được update tách riêng;
- đổi `password` sẽ được hash lại;
- có thể đổi `role`, `membership_status`, `is_owner`.

Khi xóa user bằng `DELETE /api/users/{id}`:

1. Hệ thống xóa membership của user trong business hiện tại trước.
2. Nếu user vẫn còn membership ở business khác, tài khoản hệ thống vẫn được giữ lại.
3. Chỉ khi user không còn membership nào thì mới xóa record `users`.

---

## 5.3. Workflow sản phẩm

Endpoint chính:

- `GET /api/products`
- `POST /api/products`
- `GET /api/products/{id}`
- `PUT /api/products/{id}`
- `DELETE /api/products/{id}`

### Luồng tạo và cập nhật

Khi tạo `product`:

1. Request validate dữ liệu cơ bản.
2. Service kiểm tra `unit_id` và `category_id` có thuộc đúng business hiện tại không.
3. Nếu không gửi `sku`, backend tự sinh SKU theo business.
4. Backend tự gán default:
   - `product_type = simple`
   - `track_inventory = true`
   - `cost_price = 0`
   - `sale_price = 0`
   - `is_active = true`
5. Record được lưu trong `products` và load kèm `unit`, `category`.

Khi cập nhật `product`:

- chỉ cần gửi các field muốn đổi;
- `sku` bị cấm trong request update;
- nếu gửi `unit_id` hoặc `category_id`, service sẽ kiểm tra lại business scope;
- có thể bỏ `category` bằng cách gửi `category_id = null`.

### Filter danh sách product hiện tại

- `sku`
- `name`
- `barcode`
- `is_active`
- `category_id`
- `unit_id`

Ví dụ:

- `GET /api/products?sku=SKU-001`
- `GET /api/products?name=san-pham-demo`
- `GET /api/products?barcode=8938`
- `GET /api/products?is_active=1`
- `GET /api/products?category_id=1&unit_id=1`

---

## 5.4. Workflow đơn hàng

Endpoint chính:

- `GET /api/orders`
- `POST /api/orders`
- `GET /api/orders/{id}`
- `PUT /api/orders/{id}`
- `POST /api/orders/{id}/confirm`
- `POST /api/orders/{id}/cancel`

### Luồng hiện tại

1. Hệ thống kiểm tra `warehouse_id` và `customer_id` có thuộc business hiện tại không.
2. Backend dựng snapshot item từ `products`:
   - `product_id`
   - `product_sku`
   - `product_name`
   - `quantity`
   - `unit_price`
   - `discount_amount`
3. Nếu item không gửi `unit_price`, backend lấy mặc định từ `product.sale_price`.
4. Backend tự tính lại:
   - `subtotal`
   - `discount_amount`
   - `shipping_amount`
   - `total_amount`
5. `order_no` được tự sinh nếu client không gửi.

### Trạng thái hiện có

- `draft`
- `confirmed`
- `completed`
- `cancelled`

### Điểm quan trọng

- `confirm` và `cancel` của order hiện chỉ đổi `status`.
- Order hiện không tạo inventory movement.
- Muốn trừ tồn thực tế, phải đi qua `stock_out`.
- `payment_status` của order cuối cùng được sync lại từ payment đã `paid`, không phải chỉ dựa vào giá trị client gửi lúc tạo đơn.

---

## 5.5. Workflow nhập kho

Endpoint chính:

- `GET /api/stock-in`
- `POST /api/stock-in`
- `GET /api/stock-in/{id}`
- `PUT /api/stock-in/{id}`
- `POST /api/stock-in/{id}/confirm`
- `POST /api/stock-in/{id}/cancel`

### Luồng hiện tại

1. Hệ thống kiểm tra `warehouse_id` và `supplier_id`.
2. Backend build snapshot item nhập kho:
   - `product_id`
   - `product_sku`
   - `product_name`
   - `quantity`
   - `unit_cost`
3. Backend tự tính:
   - `subtotal`
   - `discount_amount`
   - `total_amount`
4. `stock_in_no` được tự sinh nếu không gửi.
5. Sau khi create, update, confirm hoặc cancel, service luôn gọi `InventoryLedgerService::syncStockIn()`.

### Trạng thái và tác động tồn

- `draft`: lưu chứng từ nhưng không tạo movement
- `confirmed`: tạo movement dương vào `inventory_movements`
- `cancelled`: xóa movement cũ của chứng từ và rebuild lại `current_stocks`

### Kiểu chứng từ đang hỗ trợ

- `purchase`
- `return`
- `opening`

### Kết luận nghiệp vụ

Phiếu nhập kho hiện là nguồn chính để:

- tăng tồn kho;
- ghi nhận giá vốn đầu vào;
- làm dữ liệu nền cho moving average về sau.

---

## 5.6. Workflow xuất kho

Endpoint chính:

- `GET /api/stock-out`
- `POST /api/stock-out`
- `GET /api/stock-out/{id}`
- `PUT /api/stock-out/{id}`
- `POST /api/stock-out/{id}/confirm`
- `POST /api/stock-out/{id}/cancel`

### Luồng hiện tại

1. Hệ thống kiểm tra `warehouse_id`, `order_id`, `customer_id` theo business hiện tại.
2. Backend build snapshot item xuất kho.
3. Nếu item không gửi `unit_price`, backend lấy mặc định từ `product.sale_price`.
4. Backend tính `subtotal` và `total_amount`.
5. Sau khi create, update, confirm hoặc cancel, service luôn gọi `InventoryLedgerService::syncStockOut()`.

### Trạng thái và tác động tồn

- `draft`: chưa tạo movement
- `confirmed`: tạo movement âm
- `cancelled`: xóa movement cũ của chứng từ và rebuild lại tồn

### Kiểu chứng từ đang hỗ trợ

- `sale`
- `return`
- `adjustment`

### Điểm rất quan trọng

- Giá bán trên item không phải giá vốn.
- Khi rebuild ledger, hệ thống tính lại `unit_cost` của movement xuất theo moving average tại thời điểm đó.
- Nếu việc xác nhận hoặc cập nhật chứng từ `confirmed` làm tồn âm, hệ thống sẽ chặn bằng lỗi `Insufficient stock to confirm this document.`

---

## 5.7. Workflow kiểm kho và điều chỉnh tồn

Endpoint chính:

- `GET /api/stock-adjustments`
- `POST /api/stock-adjustments`
- `GET /api/stock-adjustments/{id}`
- `PUT /api/stock-adjustments/{id}`
- `POST /api/stock-adjustments/{id}/confirm`
- `POST /api/stock-adjustments/{id}/cancel`

### Luồng hiện tại

1. Người dùng chọn `warehouse_id` và danh sách item kiểm kho.
2. Với từng item:
   - `counted_qty` là bắt buộc
   - `expected_qty` có thể do request gửi hoặc backend tự lấy từ `current_stocks`
3. Backend tính:
   - `difference_qty = counted_qty - expected_qty`
4. `unit_cost` được chọn theo thứ tự ưu tiên:
   - request gửi lên
   - `current_stocks.avg_unit_cost`
   - `products.cost_price`
5. Sau khi create, update, confirm hoặc cancel, service luôn gọi `InventoryLedgerService::syncStockAdjustment()`.

### Ý nghĩa nghiệp vụ

- `difference_qty > 0`: tạo movement `adjustment_in`
- `difference_qty < 0`: tạo movement `adjustment_out`
- `difference_qty = 0`: không tạo movement

### Khi nào dùng

Luồng này dùng khi muốn chốt lại tồn thực tế so với số tồn hệ thống đang giữ.

---

## 5.8. Workflow thanh toán

Endpoint chính:

- `GET /api/payments`
- `POST /api/payments`
- `GET /api/payments/{id}`
- `PUT /api/payments/{id}`
- `POST /api/payments/{id}/confirm`
- `POST /api/payments/{id}/cancel`

### Luồng hiện tại

Payment có thể liên kết với:

- `order`
- `stock_in`
- `customer`
- `supplier`

Khi create hoặc update payment:

1. Service kiểm tra toàn bộ khóa ngoại liên quan có thuộc đúng business hiện tại không.
2. `payment_no` được tự sinh nếu không gửi.
3. Default hiện tại:
   - `direction = in`
   - `method = cash`
   - `status = paid`
4. Sau khi lưu, nếu payment có liên kết order thì hệ thống sync lại:
   - `orders.paid_amount`
   - `orders.payment_status`

### Trạng thái và hướng payment

- `direction = in`: thu tiền
- `direction = out`: chi tiền
- `status`: `pending`, `paid`, `failed`, `cancelled`

### Quy ước payment summary của order

`paid_amount` chỉ cộng các payment:

- `direction = in`
- `status = paid`

`payment_status` của order được suy ra như sau:

- `unpaid`: chưa thu gì
- `partial`: đã thu một phần
- `paid`: đã thu đủ hoặc vượt tổng tiền đơn

### Điểm cần hiểu rõ

- `confirm` payment hiện map sang trạng thái `paid`.
- `cancel` payment hiện map sang trạng thái `cancelled`.
- Payment không làm thay đổi tồn kho.
- Payment hiện chủ yếu sync summary cho `order`; `stock_in` mới chỉ là quan hệ tham chiếu nghiệp vụ.

---

## 5.9. Workflow xem tồn kho hiện tại

Endpoint chính:

- `GET /api/inventory/stocks`

### Hệ thống đang làm gì

Màn hình tồn kho hiện tại không đọc trực tiếp từ `inventory_movements`.

Nó đọc từ `current_stocks`, là read model đã được rebuild từ ledger.

### Filter đang hỗ trợ

- `warehouse_id`
- `product_id`
- `product_name`
- `sku`

### Quy ước hiện tại của bảng tồn

- `inventory_movements` là nguồn sự thật
- `current_stocks` là bảng đọc nhanh cho UI
- nếu một cặp `warehouse-product` có tồn `<= 0`, dòng trong `current_stocks` sẽ bị xóa

### Cách kiểm tra khi thấy tồn sai

1. chứng từ nào đang ở trạng thái `confirmed`
2. movement của chứng từ đó đã được tạo hay xóa đúng chưa
3. `current_stocks` đã được rebuild đúng cho cặp kho - sản phẩm bị ảnh hưởng chưa

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
