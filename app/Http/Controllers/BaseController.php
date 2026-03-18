<?php

namespace App\Http\Controllers;

/**
 * Lớp nền riêng của ứng dụng.
 *
 * Dùng tên riêng `BaseController` để controller con không phải kế thừa trực tiếp
 * từ symbol `Controller`, qua đó giảm xung đột phân tích tĩnh của IDE.
 */
abstract class BaseController extends Controller
{
}
