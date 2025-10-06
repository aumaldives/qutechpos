<section class="no-print">
    <style type="text/css">
        #contacts_login_dropdown::after {
            display: inline-block;
            width: 0;
            height: 0;
            margin-left: 0.255em;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }
    </style>
    <nav class="navbar navbar-default bg-white m-4">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo e(action([\Modules\Crm\Http\Controllers\CrmDashboardController::class, 'index']), false); ?>"><i class="fas fa fa-broadcast-tower"></i> <?php echo e(__('crm::lang.crm'), false); ?></a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <?php if(auth()->user()->can('crm.access_all_leads') || auth()->user()->can('crm.access_own_leads')): ?>
                    <li <?php if(request()->segment(2) == 'leads'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Crm\Http\Controllers\LeadController::class, 'index']). '?lead_view=list_view', false); ?>"><?php echo app('translator')->get('crm::lang.leads'); ?></a></li>
                    <?php endif; ?>
                    <?php if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule')): ?>
                    <li <?php if(request()->segment(2) == 'follow-ups'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Crm\Http\Controllers\ScheduleController::class, 'index']), false); ?>"><?php echo app('translator')->get('crm::lang.follow_ups'); ?></a></li>
                    <?php endif; ?>
                    <?php if(auth()->user()->can('crm.access_all_campaigns') || auth()->user()->can('crm.access_own_campaigns')): ?>
                    <li <?php if(request()->segment(2) == 'campaigns'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Crm\Http\Controllers\CampaignController::class, 'index']), false); ?>"><?php echo app('translator')->get('crm::lang.campaigns'); ?></a></li>
                    <?php endif; ?>                    

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('crm.access_contact_login')): ?>
                        <li class="nav-item <?php if(request()->segment(2) == 'all-contacts-login' || request()->segment(2) == 'commissions'): ?> active <?php endif; ?>">
                            <a class="nav-link dropdown-toggle" href="#" id="contacts_login_dropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo app('translator')->get('crm::lang.contacts_login'); ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                              <a class="dropdown-item" href="<?php echo e(action([\Modules\Crm\Http\Controllers\ContactLoginController::class, 'allContactsLoginList']), false); ?>"> <?php echo app('translator')->get('crm::lang.contacts_login'); ?></a>
                              <a class="dropdown-item" href="<?php echo e(action([\Modules\Crm\Http\Controllers\ContactLoginController::class, 'commissions']), false); ?>"><?php echo app('translator')->get('crm::lang.commissions'); ?></a>
                            </div>
                        </li>
                    <?php endif; ?>
                    <?php if((auth()->user()->can('crm.view_all_call_log') || auth()->user()->can('crm.view_own_call_log')) && config('constants.enable_crm_call_log')): ?>
                    <li <?php if(request()->segment(2) == 'call-log'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Crm\Http\Controllers\CallLogController::class, 'index']), false); ?>"><?php echo app('translator')->get('crm::lang.call_log'); ?></a></li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('crm.view_reports')): ?>
                    <li <?php if(request()->segment(2) == 'reports'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Crm\Http\Controllers\ReportController::class, 'index']), false); ?>"><?php echo app('translator')->get('report.reports'); ?></a></li>
                    <?php endif; ?>
                    <li <?php if(request()->segment(2) == 'proposal-template'): ?> class="active" <?php endif; ?>>
                        <a href="<?php echo e(action([\Modules\Crm\Http\Controllers\ProposalTemplateController::class, 'index']), false); ?>">
                            <?php echo app('translator')->get('crm::lang.proposal_template'); ?>
                        </a>
                    </li>
                    <li <?php if(request()->segment(2) == 'proposals'): ?> class="active" <?php endif; ?>>
                        <a href="<?php echo e(action([\Modules\Crm\Http\Controllers\ProposalController::class, 'index']), false); ?>">
                            <?php echo app('translator')->get('crm::lang.proposals'); ?>
                        </a>
                    </li>
                    <?php if(auth()->user()->can('crm.access_b2b_marketplace') && config('constants.enable_b2b_marketplace')): ?>
                    <li <?php if(request()->segment(2) == 'b2b-marketplace'): ?> class="active" <?php endif; ?>>
                        <a href="<?php echo e(action([\Modules\Crm\Http\Controllers\CrmMarketplaceController::class, 'index']), false); ?>">
                            <?php echo app('translator')->get('crm::lang.b2b_marketplace'); ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('crm.access_sources')): ?>
                        <li <?php if(request()->get('type') == 'source'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\App\Http\Controllers\TaxonomyController::class, 'index']) . '?type=source', false); ?>"><?php echo app('translator')->get('crm::lang.sources'); ?></a></li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('crm.access_life_stage')): ?>
                        <li <?php if(request()->get('type') == 'life_stage'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\App\Http\Controllers\TaxonomyController::class, 'index']) . '?type=life_stage', false); ?>"><?php echo app('translator')->get('crm::lang.life_stage'); ?></a></li>

                        <li <?php if(request()->get('type') == 'followup_category'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\App\Http\Controllers\TaxonomyController::class, 'index']) . '?type=followup_category', false); ?>"><?php echo app('translator')->get('crm::lang.followup_category'); ?></a></li>
                    <?php endif; ?>
                    <li <?php if(request()->segment(2) == 'settings'): ?> class="active" <?php endif; ?>>
                        <a href="<?php echo e(action([\Modules\Crm\Http\Controllers\CrmSettingsController::class, 'index']), false); ?>">
                            <?php echo app('translator')->get('business.settings'); ?>
                        </a>
                    </li>
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section><?php /**PATH /var/www/html/Modules/Crm/Providers/../Resources/views/layouts/nav.blade.php ENDPATH**/ ?>