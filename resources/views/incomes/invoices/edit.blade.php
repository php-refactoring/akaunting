@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans_choice('general.invoices', 1)]))

@section('content')
    <div class="card">
        {!! Form::model($invoice, [
            'id' => 'invoice',
            'method' => 'PATCH',
            'route' => ['invoices.update', $invoice->id],
            '@submit.prevent' => 'onSubmit',
            '@keydown' => 'form.errors.clear($event.target.name)',
            'files' => true,
            'role' => 'form',
            'class' => 'form-loading-button',
            'novalidate' => true
        ]) !!}

            <div class="card-body">
                <div class="row">
                    {{ Form::selectAddNewGroup('contact_id', trans_choice('general.customers', 1), 'user', $customers, $invoice->contact_id, ['required' => 'required', 'change' => 'onChangeContact']) }}

                    {{ Form::selectGroup('currency_code', trans_choice('general.currencies', 1), 'exchange-alt', $currencies, $invoice->currency_code, ['required' => 'required', 'change' => 'onChangeCurrency']) }}

                    {{ Form::dateGroup('invoiced_at', trans('invoices.invoice_date'), 'calendar', ['id' => 'invoiced_at', 'class' => 'form-control datepicker', 'required' => 'required', 'date-format' => 'Y-m-d', 'autocomplete' => 'off'], Date::parse($invoice->invoiced_at)->toDateString()) }}

                    {{ Form::dateGroup('due_at', trans('invoices.due_date'), 'calendar', ['id' => 'due_at', 'class' => 'form-control datepicker', 'required' => 'required', 'date-format' => 'Y-m-d', 'autocomplete' => 'off'], Date::parse($invoice->due_at)->toDateString()) }}

                    {{ Form::textGroup('invoice_number', trans('invoices.invoice_number'), 'file') }}

                    {{ Form::textGroup('order_number', trans('invoices.order_number'), 'shopping-cart',[]) }}

                    <div class="col-md-12 mb-4">
                        {!! Form::label('items', trans_choice($text_override['items'], 2), ['class' => 'control-label']) !!}
                        <div class="table-responsive">
                            <table class="table table-bordered" id="items">
                                <thead class="thead-light">
                                    <tr class="row">
                                        @stack('actions_th_start')
                                            <th class="col-md-1 action-column border-right-0 border-bottom-0">{{ trans('general.actions') }}</th>
                                        @stack('actions_th_end')

                                        @stack('name_th_start')
                                            <th class="col-md-3 text-left border-right-0 border-bottom-0">{{ trans('general.name') }}</th>
                                        @stack('name_th_end')

                                        @stack('quantity_th_start')
                                            <th class="col-md-2 text-center border-right-0 border-bottom-0">{{ trans($text_override['quantity']) }}</th>
                                        @stack('quantity_th_end')

                                        @stack('price_th_start')
                                            <th class="col-md-2 text-right border-right-0 border-bottom-0">{{ trans($text_override['price']) }}</th>
                                        @stack('price_th_end')

                                        @stack('taxes_th_start')
                                            <th class="col-md-2 text-right border-right-0 border-bottom-0">{{ trans_choice('general.taxes', 1) }}</th>
                                        @stack('taxes_th_end')

                                        @stack('total_th_start')
                                            <th class="col-md-2 text-right border-bottom-0">{{ trans('invoices.total') }}</th>
                                        @stack('total_th_end')
                                    </tr>
                                </thead>
                                <tbody id="invoice-item-rows">
                                    @include('incomes.invoices.item')

                                    @stack('add_item_td_start')
                                        <tr class="row" id="addItem">
                                            <td class="col-md-1 action-column border-right-0 border-bottom-0"><button type="button" @click="onAddItem" id="button-add-item" data-toggle="tooltip" title="{{ trans('general.add') }}" class="btn btn-icon btn-outline-success btn-lg" data-original-title="{{ trans('general.add') }}"><i class="fa fa-plus"></i></button></td>
                                            <td class="col-md-11 text-right border-bottom-0"></td>
                                        </tr>
                                    @stack('add_item_td_end')

                                    @stack('sub_total_td_start')
                                        <tr class="row" id="tr-subtotal">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <strong>{{ trans('invoices.sub_total') }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0">
                                                <span id="sub-total" v-html="totals.sub">0</span>
                                            </td>
                                        </tr>
                                    @stack('sub_total_td_end')

                                    @stack('add_discount_td_start')
                                        <tr class="row" id="tr-discount">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <el-popover
                                                    popper-class="p-0 h-0"
                                                    placement="bottom"
                                                    width="300"
                                                    v-model="discount">
                                                    <div class="card">
                                                        <div class="discount card-body">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-6">
                                                                    <div class="input-group input-group-merge">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text" id="input-discount">
                                                                                <i class="fa fa-percent"></i>
                                                                            </span>
                                                                        </div>
                                                                        {!! Form::number('pre_discount', null, ['id' => 'pre-discount', 'class' => 'form-control text-right']) !!}
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="discount-description">
                                                                        <strong>{{ trans('invoices.discount_desc') }}</strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="discount card-footer">
                                                            <div class="row text-center">
                                                                <div class="col-md-12">
                                                                    <a href="javascript:void(0)" @click="discount = false" class="btn btn-icon btn-outline-secondary">
                                                                        <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                                                                        <span class="btn-inner--text">{{ trans('general.cancel') }}</span>
                                                                    </a>
                                                                    {!! Form::button('<span class="fa fa-save"></span> &nbsp;' . trans('general.save'), ['type' => 'button', 'id' => 'save-discount', '@click' => 'onAddDiscount', 'class' => 'btn btn-success']) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <el-link slot="reference" type="primary" v-if="!totals.discount_text">{{ trans('invoices.add_discount') }}</el-link>
                                                    <el-link slot="reference" type="primary" v-if="totals.discount_text" v-html="totals.discount_text"></el-link>
                                                </el-popover>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0">
                                                <span id="discount-total" v-html="totals.discount"></span>
                                                {!! Form::hidden('discount', null, ['id' => 'discount', 'class' => 'form-control text-right', 'v-model' => 'form.discount']) !!}
                                            </td>
                                        </tr>
                                    @stack('add_discount_td_end')

                                    @stack('tax_total_td_start')
                                        <tr class="row" id="tr-tax">
                                            <td class="col-md-10 text-right border-right-0 border-bottom-0">
                                                <strong>{{ trans_choice('general.taxes', 1) }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right border-bottom-0">
                                                <span id="tax-total" v-html="totals.tax">0</span>
                                            </td>
                                        </tr>
                                    @stack('tax_total_td_end')

                                    @stack('grand_total_td_start')
                                        <tr class="row" id="tr-total">
                                            <td class="col-md-10 text-right border-right-0">
                                                <strong>{{ trans('invoices.total') }}</strong>
                                            </td>
                                            <td class="col-md-2 text-right">
                                                <span id="grand-total" v-html="totals.total">0</span>
                                            </td>
                                        </tr>
                                    @stack('grand_total_td_end')
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ Form::textareaGroup('notes', trans_choice('general.notes', 2)) }}

                    {{ Form::textareaGroup('footer', trans('general.footer')) }}

                    {{ Form::selectAddNewGroup('category_id', trans_choice('general.categories', 1), 'folder', $categories, $invoice->category_id) }}

                    {{ Form::recurring('edit', $invoice) }}

                    {{ Form::fileGroup('attachment', trans('general.attachment')) }}

                    {{ Form::hidden('contact_name', old('contact_name'), ['id' => 'contact_name', 'v-model' => 'form.contact_name']) }}
                    {{ Form::hidden('contact_email', old('contact_email'), ['id' => 'contact_email', 'v-model' => 'form.contact_email']) }}
                    {{ Form::hidden('contact_tax_number', old('contact_tax_number'), ['id' => 'contact_tax_number', 'v-model' => 'form.contact_tax_number']) }}
                    {{ Form::hidden('contact_phone', old('contact_phone'), ['id' => 'contact_phone', 'v-model' => 'form.contact_phone']) }}
                    {{ Form::hidden('contact_address', old('contact_address'), ['id' => 'contact_address', 'v-model' => 'form.contact_address']) }}
                    {{ Form::hidden('currency_rate', old('currency_rate', 1), ['id' => 'currency_rate', 'v-model' => 'form.contact_rate']) }}
                    {{ Form::hidden('invoice_status_code', old('invoice_status_code', 'draft'), ['id' => 'invoice_status_code', 'v-model' => 'form.invoice_status_code']) }}
                    {{ Form::hidden('amount', old('amount', '0'), ['id' => 'amount', 'v-model' => 'form.amount']) }}
                </div>
            </div>

            @permission('update-incomes-invoices')
                <div class="card-footer">
                    <div class="row float-right">
                        {{ Form::saveButtons('incomes/invoices') }}
                    </div>
                </div>
            @endpermission
        {!! Form::close() !!}
    </div>
@endsection

@push('scripts_start')
    <script type="text/javascript">
        var invoice_items = {!! json_encode($invoice->items()->select(['item_id', 'name', 'quantity', 'price', 'total', 'tax'])->get()) !!};
    </script>

    <script src="{{ asset('public/js/incomes/invoices.js?v=' . version('short')) }}"></script>
@endpush
