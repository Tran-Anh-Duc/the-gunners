# Wiki Workflow Hiện Tại Của Dự Án

## 1. Mục tiêu tài liệu

Tài liệu này giúp PM, tester và người mới vào dự án nắm nhanh:

- dự án đang giữ lại những phần nào sau khi cắt bớt nghiệp vụ;
- các thành phần nền tảng và master data đang còn trong codebase;
- luồng thao tác hiện tại từ đăng ký đến quản lý dữ liệu cơ bản;
- thứ tự test phù hợp để xác nhận phần lõi vẫn hoạt động đúng.

Tài liệu này mô tả workflow theo implementation hiện có trong codebase.

---

## 2. Bức tranh tổng thể

Đây là một hệ thống multi-tenant, trong đó mỗi tenant là một `business`.

Mỗi `business` hiện có thể quản lý:

- người dùng và membership riêng;
- module bật hoặc tắt theo business;
- đơn vị tính, kho, danh mục sản phẩm;
- sản phẩm;
- khách hàng;
- nhà cung cấp.

Hệ thống dùng `JWT` cho xác thực API và mọi thao tác dữ liệu đều đi theo `business scope`.

Luồng tổng quát hiện tại:

1. Người dùng đăng ký hoặc đăng nhập.
2. Hệ thống resolve business đang thao tác.
3. Người dùng thiết lập master data.
4. Người dùng quản lý catalog sản phẩm và danh bạ liên quan.

---

## 3. Các nhóm dữ liệu chính

### 3.1. Nhóm nền tảng

- `businesses`: tenant hoặc shop
- `users`: tài khoản hệ thống
- `business_users`: quan hệ user theo business, giữ role và trạng thái membership
- `business_modules`: module đang được bật cho business
- `business_sequences`: bộ đếm phục vụ sinh mã ổn định theo business

### 3.2. Nhóm master data

- `units`: đơn vị tính
- `warehouses`: kho
- `categories`: nhóm sản phẩm
- `products`: catalog sản phẩm
- `customers`: khách hàng
- `suppliers`: nhà cung cấp

---

## 4. Vai trò người dùng hiện tại

Hiện tại hệ thống đang vận hành với 3 vai trò chính:

- `owner`
- `manager`
- `staff`

### 4.1. Cách hiểu nhanh

- `owner`: toàn quyền trong business hiện tại
- `manager`: có quyền quản lý trên phần lớn master data
- `staff`: chủ yếu thao tác các luồng cơ bản được mở

### 4.2. Điểm quan trọng

Quyền hiện tại đang map theo role ở tầng ứng dụng, chưa dùng RBAC động đầy đủ từ database.

PM hoặc tester nên hiểu:

- quyền là theo `business`, không phải toàn hệ thống;
- một user có thể tham gia nhiều business;
- dữ liệu tài khoản và membership là hai lớp tách nhau.

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

1. Hệ thống tạo `user`.
2. Hệ thống tạo `business` mặc định cho user đó.
3. Hệ thống tạo membership `owner` trong `business_users`.
4. Hệ thống bật sẵn các module core:
   - `products`
   - `inventory`
   - `customers`
   - `suppliers`
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
- `logout` dùng blacklist token trong cache;
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
- `customers` và `suppliers` hiện là master data CRUD khá thẳng.
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

## 6. Các nguyên tắc nghiệp vụ quan trọng

## 6.1. Business scope là bắt buộc

Mọi request nghiệp vụ đều đi theo `business_id`.

Nếu frontend không truyền `business_id`, hệ thống có thể tự suy ra từ membership active hiện tại.

Điều này giúp tránh đọc hoặc ghi nhầm dữ liệu giữa các tenant.

## 6.2. Master data phải cùng business

Các khóa ngoại như `unit_id`, `category_id`, membership hoặc các relation master data khác đều phải thuộc cùng business hiện tại.

Validation format nằm ở request, còn chốt business scope nằm ở tầng service.

## 6.3. SKU là mã ổn định theo business

Nếu frontend không gửi `sku`, backend sẽ tự sinh theo `business_sequences`.

Khi sản phẩm đã tạo xong, API update không cho sửa `sku` để tránh làm lệch tham chiếu catalog.

---

## 7. Thứ tự test khuyến nghị cho tester

## 7.1. Happy path hiện tại

1. Đăng ký user mới
2. Đăng nhập
3. Tạo đơn vị tính
4. Tạo kho
5. Tạo category
6. Tạo sản phẩm
7. Tạo khách hàng
8. Tạo nhà cung cấp
9. Tạo thêm user trong business
10. Kiểm tra filter tìm kiếm theo tên hoặc SKU

## 7.2. Test phân quyền

Nên test:

- owner thấy và làm được hết
- manager làm được phần quản lý phù hợp
- staff bị giới hạn ở một số route

---

## 8. Gợi ý checklist cho PM

PM có thể dùng checklist sau khi demo:

- Có đăng ký và vào hệ thống được không
- Có tạo business mặc định cho owner không
- Có quản lý user trong business được không
- Có CRUD được đơn vị tính, kho và category không
- Có CRUD được sản phẩm đúng business không
- Có quản lý được khách hàng và nhà cung cấp không
- Có kiểm soát quyền theo role hoặc module không

---

## 9. Giới hạn hiện tại của workflow

Đây là các giới hạn cần hiểu đúng khi test:

- project hiện chỉ giữ lại phần nền và master data;
- quyền vẫn đang map cứng theo role ở tầng code;
- chưa có RBAC động đầy đủ từ database;
- hệ thống đang ưu tiên đơn giản, dễ hiểu và dễ maintain.

---

## 10. Kết luận ngắn gọn

Nếu cần nhớ nhanh dự án hiện tại, có thể tóm tắt bằng một câu:

`Auth -> Business -> User/Membership -> Master Data -> Product Catalog`

Trong đó:

- `business scope` là nguyên tắc bắt buộc;
- `users` và `business_users` là hai lớp dữ liệu tách nhau;
- `products` là phần nghiệp vụ sâu nhất còn được giữ lại trong codebase.
