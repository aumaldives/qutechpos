<?php $__env->startSection('title', __( 'user.add_user' )); ?>

<?php $__env->startSection('content'); ?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1><?php echo app('translator')->get( 'user.add_user' ); ?></h1>
</section>

<!-- Main content -->
<section class="content">
<?php echo Form::open(['url' => action([\App\Http\Controllers\ManageUserController::class, 'store']), 'method' => 'post', 'id' => 'user_add_form' ]); ?>

  <div class="row">
    <div class="col-md-12">
  <?php $__env->startComponent('components.widget'); ?>
      <div class="col-md-2">
        <div class="form-group">
          <?php echo Form::label('surname', __( 'business.prefix' ) . ':'); ?>

            <?php echo Form::text('surname', null, ['class' => 'form-control', 'placeholder' => __( 'business.prefix_placeholder' ) ]); ?>

        </div>
      </div>
      <div class="col-md-5">
        <div class="form-group">
          <?php echo Form::label('first_name', __( 'business.first_name' ) . ':*'); ?>

            <?php echo Form::text('first_name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.first_name' ) ]); ?>

        </div>
      </div>
      <div class="col-md-5">
        <div class="form-group">
          <?php echo Form::label('last_name', __( 'business.last_name' ) . ':'); ?>

            <?php echo Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => __( 'business.last_name' ) ]); ?>

        </div>
      </div>
      <div class="clearfix"></div>
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('email', __( 'business.email' ) . ':*'); ?>

            <?php echo Form::text('email', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'business.email' ) ]); ?>

        </div>
      </div>

      <div class="col-md-4">
        <div class="form-group">
          <div class="checkbox">
            <br/>
            <label>
                 <?php echo Form::checkbox('is_active', 'active', true, ['class' => 'input-icheck status']); ?> <?php echo e(__('lang_v1.status_for_user'), false); ?>

            </label>
            <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.tooltip_enable_user_active') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
          </div>
        </div>
      </div>
  <?php echo $__env->renderComponent(); ?>
  </div>
  <div class="col-md-12">
    <?php $__env->startComponent('components.widget', ['title' => __('lang_v1.roles_and_permissions')]); ?>
      <div class="col-md-4">
        <div class="form-group">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('allow_login', 1, true, 
                [ 'class' => 'input-icheck', 'id' => 'allow_login']); ?> <?php echo e(__( 'lang_v1.allow_login' ), false); ?>

              </label>
            </div>
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="user_auth_fields">
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('username', __( 'business.username' ) . ':'); ?>

          <?php if(!empty($username_ext)): ?>
            <div class="input-group">
              <?php echo Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ), 'id' => 'username' ]); ?>

              <span class="input-group-addon"><?php echo e($username_ext, false); ?></span>
            </div>
            <p class="help-block" id="show_username"></p>
          <?php else: ?>
              <?php echo Form::text('username', null, ['class' => 'form-control', 'placeholder' => __( 'business.username' ), 'id' => 'username' ]); ?>

          <?php endif; ?>
          <p class="help-block"><?php echo app('translator')->get('lang_v1.username_help'); ?></p>
          <div id="username_availability_status" class="help-block" style="display: none;"></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('password', __( 'business.password' ) . ':*'); ?>

            <?php echo Form::password('password', ['class' => 'form-control', 'required', 'placeholder' => __( 'business.password' ) ]); ?>

        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('confirm_password', __( 'business.confirm_password' ) . ':*'); ?>

            <?php echo Form::password('confirm_password', ['class' => 'form-control', 'required', 'placeholder' => __( 'business.confirm_password' ) ]); ?>

        </div>
      </div>
    </div>
      <div class="clearfix"></div>
      <div class="col-md-6">
        <div class="form-group">
          <?php echo Form::label('role', __( 'user.role' ) . ':*'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.admin_role_location_permission_help') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            <?php echo Form::select('role', $roles, null, ['class' => 'form-control select2']); ?>

        </div>
      </div>
      <div class="clearfix"></div>
      <div class="col-md-3">
          <h4><?php echo app('translator')->get( 'role.access_locations' ); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.access_locations_permission') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?></h4>
        </div>
        <div class="col-md-9">
          <div class="col-md-12">
            <div class="checkbox">
                <label>
                  <?php echo Form::checkbox('access_all_locations', 'access_all_locations', true, 
                ['class' => 'input-icheck']); ?> <?php echo e(__( 'role.all_locations' ), false); ?> 
                </label>
                <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.all_location_permission') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            </div>
          </div>
          <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="col-md-12">
            <div class="checkbox">
              <label>
                <?php echo Form::checkbox('location_permissions[]', 'location.' . $location->id, false, 
                [ 'class' => 'input-icheck']); ?> <?php echo e($location->name, false); ?> <?php if(!empty($location->location_id)): ?>(<?php echo e($location->location_id, false); ?>) <?php endif; ?>
              </label>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php echo $__env->renderComponent(); ?>
  </div>

  <div class="col-md-12">
    <?php $__env->startComponent('components.widget', ['title' => __('sale.sells')]); ?>
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('cmmsn_percent', __( 'lang_v1.cmmsn_percent' ) . ':'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.commsn_percent_help') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            <?php echo Form::text('cmmsn_percent', null, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.cmmsn_percent' ) ]); ?>

        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <?php echo Form::label('max_sales_discount_percent', __( 'lang_v1.max_sales_discount_percent' ) . ':'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.max_sales_discount_percent_help') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            <?php echo Form::text('max_sales_discount_percent', null, ['class' => 'form-control input_number', 'placeholder' => __( 'lang_v1.max_sales_discount_percent' ) ]); ?>

        </div>
      </div>
      <div class="clearfix"></div>
      
      <div class="col-md-4">
        <div class="form-group">
            <div class="checkbox">
            <br/>
              <label>
                <?php echo Form::checkbox('selected_contacts', 1, false, 
                [ 'class' => 'input-icheck', 'id' => 'selected_contacts']); ?> <?php echo e(__( 'lang_v1.allow_selected_contacts' ), false); ?>

              </label>
              <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.allow_selected_contacts_tooltip') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
            </div>
        </div>
      </div>
      <div class="col-sm-4 hide selected_contacts_div">
          <div class="form-group">
              <?php echo Form::label('user_allowed_contacts', __('lang_v1.selected_contacts') . ':'); ?>

              <div class="form-group">
                  <?php echo Form::select('selected_contact_ids[]', [], null, ['class' => 'form-control select2', 'multiple', 'style' => 'width: 100%;', 'id' => 'user_allowed_contacts' ]); ?>

              </div>
          </div>
      </div>

    <?php echo $__env->renderComponent(); ?>
  </div>

  </div>
    <?php echo $__env->make('user.edit_profile_form_part', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(!empty($form_partials)): ?>
      <?php $__currentLoopData = $form_partials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $partial): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $partial; ?>

      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
  <div class="row">
    <div class="col-md-12 text-center">
      <button type="submit" class="btn btn-primary btn-big" id="submit_user_button"><?php echo app('translator')->get( 'messages.save' ); ?></button>
    </div>
  </div>
<?php echo Form::close(); ?>

  <?php $__env->stopSection(); ?>
<?php $__env->startSection('javascript'); ?>
<script type="text/javascript">
  __page_leave_confirmation('#user_add_form');
  $(document).ready(function(){
    $('#selected_contacts').on('ifChecked', function(event){
      $('div.selected_contacts_div').removeClass('hide');
    });
    $('#selected_contacts').on('ifUnchecked', function(event){
      $('div.selected_contacts_div').addClass('hide');
    });

    $('#allow_login').on('ifChecked', function(event){
      $('div.user_auth_fields').removeClass('hide');
    });
    $('#allow_login').on('ifUnchecked', function(event){
      $('div.user_auth_fields').addClass('hide');
    });

    $('#user_allowed_contacts').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    all_contact: true
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

            return  template;
        },
        minimumInputLength: 1,
        escapeMarkup: function(markup) {
            return markup;
        },
    });
  });

  $('form#user_add_form').validate({
                rules: {
                    first_name: {
                        required: true,
                    },
                    email: {
                        email: true,
                        remote: {
                            url: "/business/register/check-email",
                            type: "post",
                            data: {
                                email: function() {
                                    return $( "#email" ).val();
                                }
                            }
                        }
                    },
                    password: {
                        required: true,
                        minlength: 5
                    },
                    confirm_password: {
                        equalTo: "#password"
                    },
                    username: {
                        minlength: 5,
                        remote: {
                            url: "/business/register/check-username",
                            type: "post",
                            data: {
                                username: function() {
                                    return $( "#username" ).val();
                                },
                                <?php if(!empty($username_ext)): ?>
                                  username_ext: "<?php echo e($username_ext, false); ?>"
                                <?php endif; ?>
                            },
                            beforeSend: function() {
                                $('#username_availability_status').show().html('<i class="fa fa-spinner fa-spin"></i> Checking username availability...');
                            },
                            complete: function(xhr) {
                                var response = xhr.responseText;
                                if (response === 'true') {
                                    $('#username_availability_status').html('<span style="color: green;"><i class="fa fa-check"></i> Username is available</span>');
                                } else {
                                    $('#username_availability_status').html('<span style="color: red;"><i class="fa fa-times"></i> Username is already taken</span>');
                                }
                            }
                        }
                    }
                },
                messages: {
                    first_name: {
                        required: 'First name is required'
                    },
                    password: {
                        required: 'Password is required',
                        minlength: 'Password must be at least 5 characters long'
                    },
                    confirm_password: {
                        equalTo: 'Password confirmation must match the password'
                    },
                    username: {
                        minlength: 'Username must be at least 5 characters long',
                        remote: 'This username is already taken. Please choose a different username.'
                    },
                    email: {
                        email: 'Please enter a valid email address',
                        remote: '<?php echo e(__("validation.unique", ["attribute" => __("business.email")]), false); ?>'
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "username") {
                        error.insertAfter("#username_availability_status");
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
  // Username availability checking with debounce
  var usernameCheckTimeout;
  $('#username').on('input', function(){
    var username = $(this).val().trim();
    
    // Clear previous timeout
    if (usernameCheckTimeout) {
        clearTimeout(usernameCheckTimeout);
    }
    
    // Show username preview if extension exists
    if($('#show_username').length > 0){
      if(username != ''){
        $('#show_username').html("<?php echo e(__('lang_v1.your_username_will_be'), false); ?>: <b>" + username + "<?php echo e($username_ext, false); ?></b>");
      } else {
        $('#show_username').html('');
      }
    }
    
    // Check username availability with debounce
    if (username.length >= 5) {
        usernameCheckTimeout = setTimeout(function() {
            checkUsernameAvailability(username);
        }, 500); // 500ms delay
    } else if (username.length > 0) {
        $('#username_availability_status').show().html('<span style="color: orange;"><i class="fa fa-exclamation-triangle"></i> Username must be at least 5 characters long</span>');
    } else {
        $('#username_availability_status').hide();
    }
  });
  
  function checkUsernameAvailability(username) {
    $('#username_availability_status').show().html('<i class="fa fa-spinner fa-spin"></i> Checking availability...');
    
    $.ajax({
        url: "/business/register/check-username",
        type: "post",
        data: {
            username: username,
            <?php if(!empty($username_ext)): ?>
            username_ext: "<?php echo e($username_ext, false); ?>",
            <?php endif; ?>
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response === 'true') {
                $('#username_availability_status').html('<span style="color: green;"><i class="fa fa-check"></i> Username is available</span>');
            } else {
                $('#username_availability_status').html('<span style="color: red;"><i class="fa fa-times"></i> Username is already taken</span>');
            }
        },
        error: function() {
            $('#username_availability_status').html('<span style="color: red;"><i class="fa fa-exclamation-triangle"></i> Error checking username availability</span>');
        }
    });
  }

  $('#username').change( function(){
    if($('#show_username').length > 0){
      if($(this).val().trim() != ''){
        $('#show_username').html("<?php echo e(__('lang_v1.your_username_will_be'), false); ?>: <b>" + $(this).val() + "<?php echo e($username_ext, false); ?></b>");
      } else {
        $('#show_username').html('');
      }
    }
  });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/manage_user/create.blade.php ENDPATH**/ ?>