<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;

/**
 * Lớp nền chung cho toàn bộ API controller.
 *
 * Tách riêng lớp này để các controller API dùng một tên base rõ nghĩa hơn,
 * tránh việc IDE phải resolve trực tiếp symbol `Controller` ở từng file.
 */
abstract class ApiController extends BaseController
{
}
