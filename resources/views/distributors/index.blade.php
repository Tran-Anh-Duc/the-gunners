@extends('layouts.app')

@section('title','Danh sách Distributors')

@section('content')
    <h1>Danh sách Distributors</h1>

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>Tên</th>
            <th>Parent ID</th>
            <th>Group</th>
            <th>Tổng Doanh số cá nhân (3 tháng)</th>
            <th>Tổng Doanh số nhóm (3 tháng)</th>
            <th>Tổng thưởng</th>
        </tr>
        </thead>
        <tbody>
        @forelse($distributors as $d)
            <tr>
                <td>{{ $d->distributor_id }}</td>
                <td>{{ $d->name }}</td>
                <td>{{ $d->parent_id }}</td>
                <td>{{ $d->group_code }}</td>
                <td>{{ number_format(($d->sum_T + $d->sum_T_1 + $d->sum_T_2) ?? 0) }}</td>
                <td>{{ number_format(($d->grp_T + $d->grp_T_1 + $d->grp_T_2) ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Chưa có dữ liệu</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
