<?php $__env->startSection('title', __('role.edit_role')); ?>

<?php $__env->startSection('content'); ?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1><?php echo app('translator')->get( 'role.edit_role' ); ?></h1>
</section>

<!-- Main content -->
<section class="content">
    <?php
      $pos_settings = !empty(session('business.pos_settings')) ? json_decode(session('business.pos_settings'), true) : [];
    ?>
    <?php $__env->startComponent('components.widget', ['class' => 'box-primary']); ?>
        <?php echo Form::open(['url' => action([\App\Http\Controllers\RoleController::class, 'update'], [$role->id]), 'method' => 'PUT', 'id' => 'role_form' ]); ?>

        <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <?php echo Form::label('name', __( 'user.role_name' ) . ':*'); ?>

              <?php echo Form::text('name', str_replace( '#' . auth()->user()->business_id, '', $role->name) , ['class' => 'form-control', 'required', 'placeholder' => __( 'user.role_name' ) ]); ?>

          </div>
        </div>
        </div>
        <div class="row">
        <div class="col-md-3">
          <label><?php echo app('translator')->get( 'user.permissions' ); ?>:</label> 
        </div>
        </div>

        <div class="row check_group">
          <div class="col-md-1">
            <h4><?php echo app('translator')->get( 'lang_v1.others' ); ?></h4>
          </div>
          <div class="col-md-2">
            <div class="checkbox">
                <label>
                  <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                </label>
              </div>
          </div>
          <div class="col-md-9">
              <?php if(in_array('service_staff', $enabled_modules)): ?>
                <div class="col-md-12">
                  <div class="checkbox">
                    <label>
                    <?php echo Form::checkbox('is_service_staff', 1, $role->is_service_staff, 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'restaurant.service_staff' ), false); ?>

                  </label>
                  <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('restaurant.tooltip_service_staff') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'view_export_buttons', in_array('view_export_buttons', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_export_buttons' ), false); ?>

                  </label>
                </div>
              </div>
          </div>
        </div>
        <hr>

        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.user' ); ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'user.view', in_array('user.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.user.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'user.create', in_array('user.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.user.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'user.update', in_array('user.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.user.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'user.delete', in_array('user.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.user.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'user.roles' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'roles.view', in_array('roles.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_role' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'roles.create', in_array('roles.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.add_role' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'roles.update', in_array('roles.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.edit_role' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'roles.delete', in_array('roles.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_role' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.supplier' ); ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[supplier_view]', 'supplier.view', in_array('supplier.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_supplier' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[supplier_view]', 'supplier.view_own', in_array('supplier.view_own', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_supplier' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'supplier.create', in_array('supplier.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.supplier.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'supplier.update', in_array('supplier.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.supplier.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'supplier.delete', in_array('supplier.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.supplier.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.customer' ); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.customer_permissions_tooltip') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view]', 'customer.view', in_array('customer.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_customer' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view]', 'customer.view_own', in_array('customer.view_own', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_customer' ), false); ?>

              </label>
            </div>
            <hr>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view_by_sell]', 'customer_with_no_sell_one_month', in_array('customer_with_no_sell_one_month', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.customer_with_no_sell_one_month' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view_by_sell]', 'customer_with_no_sell_three_month', in_array('customer_with_no_sell_three_month', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.customer_with_no_sell_three_month' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view_by_sell]', 'customer_with_no_sell_six_month', in_array('customer_with_no_sell_six_month', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.customer_with_no_sell_six_month' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view_by_sell]', 'customer_with_no_sell_one_year', in_array('customer_with_no_sell_one_year', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.customer_with_no_sell_one_year' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[customer_view_by_sell]', 'customer_irrespective_of_sell', in_array('customer_irrespective_of_sell', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.customer_irrespective_of_sell' ), false); ?>

              </label>
            </div>
            <hr>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'customer.create', in_array('customer.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.customer.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'customer.update', in_array('customer.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.customer.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'customer.delete', in_array('customer.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.customer.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'business.product' ); ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'product.view', in_array('product.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.product.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'product.create', in_array('product.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.product.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'product.update', in_array('product.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.product.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'product.delete', in_array('product.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.product.delete' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'product.opening_stock', in_array('product.opening_stock', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.add_opening_stock' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_purchase_price', in_array('view_purchase_price', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.view_purchase_price'), false); ?>

              </label>
              <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.view_purchase_price_tooltip') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <?php if(in_array('purchases', $enabled_modules) || in_array('stock_adjustment', $enabled_modules) ): ?>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.purchase' ); ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[purchase_view]', 'purchase.view', in_array('purchase.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_purchase_n_stock_adjustment' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[purchase_view]', 'view_own_purchase', in_array('view_own_purchase', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.view_own_purchase_n_stock_adjustment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase.create', in_array('purchase.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.purchase.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase.update', in_array('purchase.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.purchase.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase.delete', in_array('purchase.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.purchase.delete' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase.payments', in_array('purchase.payments', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.add_purchase_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_purchase_payment', in_array('edit_purchase_payment', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.edit_purchase_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'delete_purchase_payment', in_array('delete_purchase_payment', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.delete_purchase_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase.update_status', in_array('purchase.update_status', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.update_status'), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <?php endif; ?>
        <?php if(!empty($common_settings['enable_purchase_requisition'])): ?>
          <div class="row check_group">
            <div class="col-md-1">
              <h4><?php echo app('translator')->get( 'lang_v1.purchase_requisition' ); ?></h4>
            </div>
            <div class="col-md-2">
              <div class="checkbox">
                  <label>
                    <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                  </label>
                </div>
            </div>
            <div class="col-md-9">
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::radio('radio_option[purchase_requisition_view]', 'purchase_requisition.view_all', in_array('purchase_requisition.view_all', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_purchase_requisition' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::radio('radio_option[purchase_requisition_view]', 'purchase_requisition.view_own', in_array('purchase_requisition.view_own', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_purchase_requisition' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'purchase_requisition.create', in_array('purchase_requisition.create', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.create_purchase_requisition' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'purchase_requisition.delete', in_array('purchase_requisition.delete', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_purchase_requisition' ), false); ?>

                  </label>
                </div>
              </div>

            </div>
          </div>
          <hr>
        <?php endif; ?>
        <?php if(!empty($common_settings['enable_purchase_order'])): ?>
          <div class="row check_group">
            <div class="col-md-1">
              <h4><?php echo app('translator')->get( 'lang_v1.purchase_order' ); ?></h4>
            </div>
            <div class="col-md-2">
              <div class="checkbox">
                  <label>
                    <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                  </label>
                </div>
            </div>
            <div class="col-md-9">
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::radio('radio_option[purchase_order_view]', 'purchase_order.view_all', in_array('purchase_order.view_all', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_purchase_order' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::radio('radio_option[purchase_order_view]', 'purchase_order.view_own', in_array('purchase_order.view_own', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_purchase_order' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'purchase_order.create', in_array('purchase_order.create', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.create_purchase_order' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'purchase_order.update', in_array('purchase_order.update', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.edit_purchase_order' ), false); ?>

                  </label>
                </div>
              </div>
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'purchase_order.delete', in_array('purchase_order.delete', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_purchase_order' ), false); ?>

                  </label>
                </div>
              </div>

            </div>
          </div>
        <?php endif; ?>

        <div class="row check_group">
            <div class="col-md-1">
                <h4><?php echo app('translator')->get( 'sale.pos_sale' ); ?></h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                    </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'sell.view', in_array('sell.view', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.sell.view' ), false); ?>

                      </label>
                    </div>
                </div>
                <?php if(in_array('pos_sale', $enabled_modules)): ?>
                    <div class="col-md-12">
                        <div class="checkbox">
                          <label>
                            <?php echo Form::checkbox('permissions[]', 'sell.create', in_array('sell.create', $role_permissions), 
                            [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.sell.create' ), false); ?>

                          </label>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'sell.update', in_array('sell.update', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.sell.update' ), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'sell.delete', in_array('sell.delete', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.sell.delete' ), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'edit_product_price_from_pos_screen', in_array('edit_product_price_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.edit_product_price_from_pos_screen'), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'edit_product_discount_from_pos_screen', in_array('edit_product_discount_from_pos_screen', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.edit_product_discount_from_pos_screen'), false); ?>

                      </label>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'edit_pos_payment', 
                            in_array('edit_pos_payment', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.add_edit_payment'), false); ?>

                      </label>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'print_invoice', in_array('print_invoice', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.print_invoice'), false); ?>

                      </label>
                    </div>
                  </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'sale.sale' ); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.sell_permissions_tooltip') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <?php if(in_array('add_sale', $enabled_modules)): ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[sell_view]', 'direct_sell.view', in_array('direct_sell.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_sale' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[sell_view]', 'view_own_sell_only', in_array('view_own_sell_only', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_sell_only' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_paid_sells_only', in_array('view_paid_sells_only', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_paid_sells_only' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_due_sells_only', in_array('view_due_sells_only', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_due_sells_only' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_partial_sells_only', in_array('view_partial_sells_only', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_partially_paid_sells_only' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_overdue_sells_only', in_array('view_overdue_sells_only', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_overdue_sells_only' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'direct_sell.access', in_array('direct_sell.access', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.add_sell' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'direct_sell.update', in_array('direct_sell.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.update_sale' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'direct_sell.delete', in_array('direct_sell.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_sell' ), false); ?>

              </label>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_commission_agent_sell', in_array('view_commission_agent_sell', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_commission_agent_sell' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'sell.payments', in_array('sell.payments', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.add_sell_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_sell_payment', in_array('edit_sell_payment', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.edit_sell_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'delete_sell_payment', in_array('delete_sell_payment', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.delete_sell_payment'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_product_price_from_sale_screen', in_array('edit_product_price_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.edit_product_price_from_sale_screen'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_product_discount_from_sale_screen', in_array('edit_product_discount_from_sale_screen', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.edit_product_discount_from_sale_screen'), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'discount.access', in_array('discount.access', $role_permissions), ['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.discount.access'), false); ?>

              </label>
            </div>
          </div>
          <?php if(in_array('types_of_service', $enabled_modules)): ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'access_types_of_service', in_array('access_types_of_service', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.access_types_of_service' ), false); ?>

              </label>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'access_sell_return', in_array('access_sell_return', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.access_all_sell_return' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'access_own_sell_return', in_array('access_own_sell_return', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.access_own_sell_return' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_invoice_number', in_array('edit_invoice_number', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.add_edit_invoice_number' ), false); ?>

              </label>
            </div>
          </div>
          
        </div>
        </div>
        <hr>
        <?php if(!empty($pos_settings['enable_sales_order'])): ?>
        <div class="row check_group">
          <div class="col-md-1">
            <h4><?php echo app('translator')->get( 'lang_v1.sales_order' ); ?></h4>
          </div>
          <div class="col-md-2">
            <div class="checkbox">
                <label>
                  <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                </label>
              </div>
          </div>
          <div class="col-md-9">
            <div class="col-md-12">
              <div class="checkbox">
                <label>
                  <?php echo Form::radio('radio_option[so_view]', 'so.view_all', in_array('so.view_all', $role_permissions), 
                  [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_so' ), false); ?>

                </label>
              </div>
            </div>
            <div class="col-md-12">
              <div class="checkbox">
                <label>
                  <?php echo Form::radio('radio_option[so_view]', 'so.view_own', in_array('so.view_own', $role_permissions), 
                  [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_so' ), false); ?>

                </label>
              </div>
            </div>
            <div class="col-md-12">
              <div class="checkbox">
                <label>
                  <?php echo Form::checkbox('permissions[]', 'so.create', in_array('so.create', $role_permissions), 
                  [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.create_so' ), false); ?>

                </label>
              </div>
            </div>
            <div class="col-md-12">
              <div class="checkbox">
                <label>
                  <?php echo Form::checkbox('permissions[]', 'so.update', in_array('so.update', $role_permissions), 
                  [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.edit_so' ), false); ?>

                </label>
              </div>
            </div>
            <div class="col-md-12">
              <div class="checkbox">
                <label>
                  <?php echo Form::checkbox('permissions[]', 'so.delete', in_array('so.delete', $role_permissions), 
                  [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_so' ), false); ?>

                </label>
              </div>
            </div>

          </div>
        </div>
        <hr>
      <?php endif; ?>
      <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'sale.draft' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
        <div class="checkbox">
          <label>
            <?php echo Form::radio('radio_option[draft_view]', 'draft.view_all', in_array('draft.view_all', $role_permissions), 
            [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_drafts' ), false); ?>

          </label>
        </div>
      </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[draft_view]', 'draft.view_own', in_array('draft.view_own', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_drafts' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'draft.update', in_array('draft.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.edit_draft' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'draft.delete', in_array('draft.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_draft' ), false); ?>

              </label>
            </div>
          </div>

        </div>
      </div>
      <hr>
      <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'lang_v1.quotation' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
        <div class="checkbox">
          <label>
            <?php echo Form::radio('radio_option[quotation_view]', 'quotation.view_all', in_array('quotation.view_all', $role_permissions), 
            [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_all_quotations' ), false); ?>

          </label>
        </div>
      </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[quotation_view]', 'quotation.view_own', in_array('quotation.view_own', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_own_quotations' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'quotation.update', in_array('quotation.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.edit_quotation' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'quotation.delete', in_array('quotation.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_quotation' ), false); ?>

              </label>
            </div>
          </div>

        </div>
      </div>
      <hr>
        <div class="row check_group">
            <div class="col-md-1">
              <h4><?php echo app('translator')->get( 'lang_v1.shipments' ); ?></h4>
            </div>
            <div class="col-md-2">
                <div class="checkbox">
                  <label>
                    <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                  </label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::radio('radio_option[shipping_view]', 'access_shipping', in_array('access_shipping', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.access_all_shipments'), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::radio('radio_option[shipping_view]', 'access_own_shipping', in_array('access_own_shipping', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.access_own_shipping'), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'access_pending_shipments_only', in_array('access_pending_shipments_only', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.access_pending_shipments_only'), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'access_commission_agent_shipping', in_array('access_commission_agent_shipping', $role_permissions), ['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.access_commission_agent_shipping'), false); ?>

                      </label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'cash_register.cash_register' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_cash_register', in_array('view_cash_register', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_cash_register' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'close_cash_register', in_array('close_cash_register', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.close_cash_register' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.brand' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'brand.view', in_array('brand.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.brand.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'brand.create', in_array('brand.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.brand.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'brand.update', in_array('brand.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.brand.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'brand.delete', in_array('brand.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.brand.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.tax_rate' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'tax_rate.view', in_array('tax_rate.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.tax_rate.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'tax_rate.create', in_array('tax_rate.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.tax_rate.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'tax_rate.update', in_array('tax_rate.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.tax_rate.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'tax_rate.delete', in_array('tax_rate.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.tax_rate.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.unit' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'unit.view', in_array('unit.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.unit.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'unit.create', in_array('unit.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.unit.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'unit.update', in_array('unit.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.unit.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'unit.delete', in_array('unit.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.unit.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'category.category' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'category.view', in_array('category.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.category.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'category.create', in_array('category.create', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.category.create' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'category.update', in_array('category.update', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.category.update' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'category.delete', in_array('category.delete', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.category.delete' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.report' ); ?></h4>
        </div>
        <div class="col-md-2">
            <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
        <?php if(in_array('purchases', $enabled_modules) || in_array('add_sale', $enabled_modules) || in_array('pos_sale', $enabled_modules)): ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'purchase_n_sell_report.view', in_array('purchase_n_sell_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.purchase_n_sell_report.view' ), false); ?>

              </label>
            </div>
          </div>
        <?php endif; ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'tax_report.view', in_array('tax_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.tax_report.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'contacts_report.view', in_array('contacts_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.contacts_report.view' ), false); ?>

              </label>
            </div>
          </div>
          <?php if(in_array('expenses', $enabled_modules)): ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'expense_report.view', in_array('expense_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.expense_report.view' ), false); ?>

              </label>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'profit_loss_report.view', in_array('profit_loss_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.profit_loss_report.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'stock_report.view', in_array('stock_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.stock_report.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'trending_product_report.view', in_array('trending_product_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.trending_product_report.view' ), false); ?>

              </label>
            </div>
          </div>

          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'register_report.view', in_array('register_report.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.register_report.view' ), false); ?>

              </label>
            </div>
          </div>

          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'sales_representative.view', in_array('sales_representative.view', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.sales_representative.view' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'view_product_stock_value', in_array('view_product_stock_value', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.view_product_stock_value' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'role.settings' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'business_settings.access', in_array('business_settings.access', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.business_settings.access' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'barcode_settings.access', in_array('barcode_settings.access', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.barcode_settings.access' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'invoice_settings.access', in_array('invoice_settings.access', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.invoice_settings.access' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'access_printers', in_array('access_printers', $role_permissions),['class' => 'input-icheck']); ?>

                <?php echo e(__('lang_v1.access_printers'), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <?php if(in_array('expenses', $enabled_modules)): ?>
            <hr>
            <div class="row check_group">
                <div class="col-md-1">
                  <h4><?php echo app('translator')->get( 'lang_v1.expense' ); ?></h4>
                </div>
                <div class="col-md-2">
                  <div class="checkbox">
                      <label>
                        <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

                      </label>
                    </div>
                </div>
                <div class="col-md-9">
                  <div class="col-md-12">
                        <div class="checkbox">
                          <label>
                            <?php echo Form::radio('radio_option[expense_view]', 'all_expense.access', in_array('all_expense.access', $role_permissions), 
                            [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.access_all_expense' ), false); ?>

                          </label>
                        </div>
                      </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                      <label>
                        <?php echo Form::radio('radio_option[expense_view]', 'view_own_expense', in_array('view_own_expense', $role_permissions),['class' => 'input-icheck']); ?>

                        <?php echo e(__('lang_v1.view_own_expense'), false); ?>

                      </label>
                        </div>
                  </div>
                  <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'expense.add', in_array('expense.add', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'expense.add_expense' ), false); ?>

                      </label>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'expense.edit', in_array('expense.edit', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'expense.edit_expense' ), false); ?>

                      </label>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="checkbox">
                      <label>
                        <?php echo Form::checkbox('permissions[]', 'expense.delete', in_array('expense.delete', $role_permissions), 
                        [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_expense' ), false); ?>

                      </label>
                    </div>
                  </div>
                </div>
            </div>
        <?php endif; ?>
        <hr>
        <div class="row">
        <div class="col-md-3">
          <h4><?php echo app('translator')->get( 'role.dashboard' ); ?></h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'dashboard.data', in_array('dashboard.data', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'role.dashboard.data' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <div class="row check_group">
        <div class="col-md-3">
          <h4><?php echo app('translator')->get( 'account.account' ); ?></h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'account.access', in_array('account.access', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.access_accounts' ), false); ?>

              </label>
            </div>
          </div>

          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'edit_account_transaction', in_array('edit_account_transaction', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.edit_account_transaction' ), false); ?>

              </label>
            </div>
          </div>

          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'delete_account_transaction', in_array('delete_account_transaction', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'lang_v1.delete_account_transaction' ), false); ?>

              </label>
            </div>
          </div>
          
        </div>
        </div>
        <hr>
        <?php if(in_array('booking', $enabled_modules)): ?>
        <div class="row check_group">
        <div class="col-md-1">
          <h4><?php echo app('translator')->get( 'restaurant.bookings' ); ?></h4>
        </div>
        <div class="col-md-2">
          <div class="checkbox">
              <label>
                <input type="checkbox" class="check_all input-icheck" > <?php echo e(__( 'role.select_all' ), false); ?>

              </label>
            </div>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[bookings_view]', 'crud_all_bookings', in_array('crud_all_bookings', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'restaurant.add_edit_view_all_booking' ), false); ?>

              </label>
            </div>
          </div>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::radio('radio_option[bookings_view]', 'crud_own_bookings', in_array('crud_own_bookings', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__( 'restaurant.add_edit_view_own_booking' ), false); ?>

              </label>
            </div>
          </div>
        </div>
        </div>
        <hr>
        <?php endif; ?>
        <div class="row">
        <div class="col-md-3">
          <h4><?php echo app('translator')->get( 'lang_v1.access_selling_price_groups' ); ?></h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('permissions[]', 'access_default_selling_price', in_array('access_default_selling_price', $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e(__('lang_v1.default_selling_price'), false); ?>

              </label>
            </div>
          </div>
          <?php if(count($selling_price_groups) > 0): ?>
          <?php $__currentLoopData = $selling_price_groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $selling_price_group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('spg_permissions[]', 'selling_price_group.' . $selling_price_group->id, in_array('selling_price_group.' . $selling_price_group->id, $role_permissions), 
                [ 'class' => 'input-icheck']); ?> <?php echo e($selling_price_group->name, false); ?>

              </label>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php endif; ?>
        </div>
        </div>
        <?php if(in_array('tables', $enabled_modules)): ?>
          <div class="row">
            <div class="col-md-3">
              <h4><?php echo app('translator')->get( 'restaurant.restaurant' ); ?></h4>
            </div>
            <div class="col-md-9">
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <?php echo Form::checkbox('permissions[]', 'access_tables', in_array('access_tables', $role_permissions), 
                    [ 'class' => 'input-icheck']); ?> <?php echo e(__('lang_v1.access_tables'), false); ?>

                  </label>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
        <?php echo $__env->make('role.partials.module_permissions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="row">
        <div class="col-md-12">
           <button type="submit" class="btn btn-primary pull-right"><?php echo app('translator')->get( 'messages.update' ); ?></button>
        </div>
        </div>

        <?php echo Form::close(); ?>

    <?php echo $__env->renderComponent(); ?>
</section>
<!-- /.content -->
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/role/edit.blade.php ENDPATH**/ ?>