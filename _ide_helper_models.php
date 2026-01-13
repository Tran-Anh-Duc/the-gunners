<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string|null $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Action withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAction {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name Tên khách hàng
 * @property string|null $phone Số điện thoại
 * @property string|null $email Email
 * @property string|null $address Địa chỉ giao hàng
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCustomer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $user
 * @property-read int|null $user_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departments withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDepartments {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id Kho
 * @property int $product_id Sản phẩm
 * @property string $inventory_date Ngày thống kê
 * @property numeric $opening_quantity Tồn đầu ngày
 * @property numeric $import_quantity Tổng nhập
 * @property numeric $export_quantity Tổng xuất
 * @property numeric $closing_quantity Tồn cuối ngày
 * @property numeric $import_value Tổng tiền nhập
 * @property numeric $export_value Tổng tiền xuất
 * @property numeric $closing_value Tổng giá trị tồn cuối
 * @property numeric $average_cost Giá trung bình
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereAverageCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereClosingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereClosingValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereExportQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereExportValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereImportQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereImportValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereInventoryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereOpeningQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInventory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string|null $icon
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperModule {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id Kho
 * @property int $product_id Sản phẩm
 * @property numeric $opening_quantity Số lượng tồn đầu kỳ
 * @property numeric $opening_unit_price Giá đơn vị tồn đầu kỳ
 * @property numeric $opening_total_value Thành tiền tồn đầu kỳ
 * @property string $period Kỳ kế toán - YYYY-MM-01
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereOpeningQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereOpeningTotalValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereOpeningUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpeningInventory whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOpeningInventory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $customer_id Khách hàng
 * @property int $status_id Trạng thái đơn hàng - FK từ bảng statuses
 * @property string $order_date Ngày đặt hàng
 * @property numeric $total_amount Tổng tiền đơn hàng
 * @property string|null $note Ghi chú
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Shipment|null $shipment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOrder {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id Đơn hàng
 * @property int $product_id Sản phẩm
 * @property numeric $quantity Số lượng đặt
 * @property numeric $price Giá bán
 * @property numeric $subtotal Thành tiền (quantity * price)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOrderItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id Đơn hàng
 * @property int $status_id Trạng thái thanh toán - FK -> statuses
 * @property string $payment_method Hình thức thanh toán
 * @property numeric $amount Số tiền thanh toán
 * @property string|null $paid_date Ngày thanh toán
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $module_id
 * @property string $action_id
 * @property string $name
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Action|null $action
 * @property-read \App\Models\Module $module
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPermission {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $code Mã sản phẩm
 * @property string|null $name Tên sản phẩm
 * @property numeric|null $price Giá sản phẩm
 * @property string|null $description Mô tả sản phẩm
 * @property int|null $unit_id Đơn vị tính - FK từ bảng units
 * @property int $status_id Trạng thái - FK từ bảng statuses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @property-read \App\Models\Unit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProduct {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissionsRole
 * @property-read int|null $permissions_role_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRole {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $order_id Đơn hàng
 * @property int|null $shipper_id Nhân viên giao hàng
 * @property int|null $vehicle_id Phương tiện giao hàng
 * @property int $status_id Trạng thái giao hàng - FK -> statuses
 * @property string|null $delivery_date Ngày giao
 * @property string|null $note Ghi chú
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User|null $shipper
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereShipperId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shipment whereVehicleId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShipment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id Kho nhập
 * @property int|null $supplier_id Nhà cung cấp
 * @property int|null $created_by Người tạo phiếu
 * @property string $date Ngày nhập kho
 * @property numeric $total_amount Tổng tiền nhập
 * @property string $status Trạng thái phiếu nhập
 * @property string|null $note Ghi chú
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockInItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockIn whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockIn {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stock_in_id Phiếu nhập kho
 * @property int $product_id Sản phẩm
 * @property numeric $quantity Số lượng nhập
 * @property numeric $price Giá nhập
 * @property numeric $subtotal Thành tiền (quantity * price)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\StockIn $stockIn
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereStockInId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockInItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockInItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id Kho xuất
 * @property int|null $related_order_id Đơn hàng liên quan
 * @property int|null $created_by Người lập phiếu
 * @property string $date Ngày xuất kho
 * @property numeric $total_amount Tổng tiền xuất
 * @property string $status Trạng thái phiếu xuất
 * @property string|null $note Ghi chú
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockOutItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Order|null $order
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereRelatedOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOut whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockOut {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stock_out_id Phiếu xuất kho
 * @property int $product_id Sản phẩm
 * @property numeric $quantity Số lượng xuất
 * @property numeric $price Giá xuất
 * @property numeric $subtotal Thành tiền (quantity * price)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\StockOut $stockOut
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereStockOutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockOutItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStockOutItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name Tên đơn vị tính, ví dụ: cái, kg, hộp
 * @property string|null $code Ký hiệu, ví dụ: pcs, kg, box
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUnit {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $department_id
 * @property int|null $status_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property string|null $avatar
 * @property string $role
 * @property int $is_active
 * @property string|null $last_login_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $change_password_at
 * @property-read \App\Models\Departments|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Departments> $departments
 * @property-read int|null $departments_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\UserStatus|null $status
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereChangePasswordAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $department_id
 * @property string $assigned_at
 * @property string|null $is_main
 * @property string|null $position
 * @property string|null $ended_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDepartment whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserDepartment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property string|null $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $usersStatus
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserStatus {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property int $status_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWarehouse {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $user_id
 * @property int|null $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Role|null $role
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseUser whereWarehouseId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWarehouseUser {}
}

