<div class="pos-tab-content active">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('kitchen.auto_cook_category_settings')</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-12">
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        @lang('kitchen.auto_cook_category_help')
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group">
                        <label>@lang('kitchen.auto_cook_categories') @show_tooltip(__('kitchen.auto_cook_categories_help'))</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-tags"></i>
                            </span>
                            {!! Form::select('auto_cook_categories[]', [], $auto_cook_categories, ['class' => 'form-control', 'id' => 'auto_cook_categories', 'multiple' => true, 'style' => 'width: 100%']) !!}
                        </div>
                        <p class="help-block">
                            @lang('kitchen.auto_cook_categories_description')
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="callout callout-warning">
                        <h4><i class="fas fa-exclamation-triangle"></i> @lang('kitchen.important_note')</h4>
                        <p>@lang('kitchen.auto_cook_behavior_note')</p>
                        <ul class="list-unstyled" style="margin-left: 20px;">
                            <li><i class="fas fa-check text-green"></i> @lang('kitchen.auto_cook_example_1')</li>
                            <li><i class="fas fa-times text-red"></i> @lang('kitchen.auto_cook_example_2')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>