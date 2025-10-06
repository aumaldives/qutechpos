<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
    <div class="box box-solid">
        <div class="box-header with-border">
            <i class="fas fa-calendar-check"></i>
            <h3 class="box-title">@lang('essentials::lang.my_leave_balance')</h3>
        </div>
        <div class="box-body p-10">
            <table class="table no-margin">
                <tbody>
                    <tr>
                        <th class="bg-light-gray" colspan="3">@lang('essentials::lang.leave_balance_summary')</th>
                    </tr>
                    @forelse($user_leave_balances as $balance)
                        <tr>
                            <td><strong>{{ $balance['leave_type'] }}</strong></td>
                            <td class="text-center">
                                <span class="badge bg-green">{{ $balance['remaining_count'] }}</span>
                                <small class="text-muted">/ {{ $balance['max_count'] }}</small>
                            </td>
                            <td class="text-right">
                                @if($balance['is_paid'])
                                    <span class="label label-success">@lang('lang_v1.paid')</span>
                                @else
                                    <span class="label label-default">@lang('lang_v1.unpaid')</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">@lang('essentials::lang.no_leave_types_configured')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>