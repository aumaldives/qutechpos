<script>
    $(document).ready(function() {
        var columnChartValues = [{
            y: <?php echo array_sum($due1to30Array); ?>,
            label: "1-30 Days",
            color: "#1f77b4"
        }, {
            y: <?php echo array_sum($due31to60Array); ?>,
            label: "31-60 Days",
            color: "#ff7f0e"
        }, {
            y: <?php echo array_sum($due61to90Array); ?>,
            label: "61-90 Days",
            color: " #ffbb78"
        }, {
            y: <?php echo array_sum($due91to120Array); ?>,
            label: "91-120 Days",
            color: "#d62728"
        }, {
            y: <?php echo array_sum($due121to150Array); ?>,
            label: "121-150 Days",
            color: "#98df8a"
        }, {
            y: <?php echo array_sum($due151to180Array); ?>,
            label: "151-180 Days",
            color: "#bcbd22"
        }, {
            y: <?php echo array_sum($due180plusArray); ?>,
            label: ">= 180 Days",
            color: "#f7b6d2"
        }];

        renderColumnChart(columnChartValues);

        function renderColumnChart(values) {

            var chart = new CanvasJS.Chart("columnChartsup", {
                backgroundColor: "white",
                colorSet: "colorSet3",
                title: {
                    text: "Supplier Balances - Days Outstanding Report",
                    fontFamily: "Verdana",
                    fontSize: 22,
                    fontWeight: "normal",
                },
                animationEnabled: true,
                legend: {
                    verticalAlign: "bottom",
                    horizontalAlign: "center"
                },
                theme: "theme2",
                data: [

                    {
                        indexLabelFontSize: 15,
                        indexLabelFontFamily: "Monospace",
                        indexLabelFontColor: "darkgrey",
                        indexLabelLineColor: "darkgrey",
                        indexLabelPlacement: "outside",
                        type: "column",
                        showInLegend: false,
                        legendMarkerColor: "grey",
                        dataPoints: values
                    }
                ]
            });

            chart.render();
        }
    });
</script>
<div class="container-fluid p-relative">
    <div class="row">
        <div class="col-md-12">
            <div id="columnChartsup" style="height: 360px; width: 100%;">
            </div>
        </div>
    </div>
    <div class="canvasoverlay"></div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="supplier_ageing_report_tbl">
        <thead>
            <tr>
                <th><?php echo app('translator')->get('report.contact'); ?></th>
                <th>Current <i class="fa fa-info-circle text-info no-print" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="<?php echo e(__('messages.due_tooltip'), false); ?>" aria-hidden="true"></i></th>
                <th>1-30 Days</th>
                <th>31-60 Days</th>
                <th>61-90 Days</th>
                <th>91-120 Days</th>
                <th>121-150 Days</th>
                <th>151-180 Days</th>
                <th>>= 180 Days</th>
                <th>Total Due</th>
            </tr>
        </thead>
        <tbody>
  <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($currentArray[$key] != '0' || $due1to30Array[$key]!= '0' || $due31to60Array[$key]!= '0' || $due61to90Array[$key]!= '0' || $due91to120Array[$key]!= '0' || $due121to150Array[$key]!= '0' || $due151to180Array[$key]!= '0' || $due180plusArray[$key]!= '0' || $totalDueArray[$key]!= '0'): ?>
      <?php 
        $class = '';
        if ($due180plusArray[$key] > 0 || $due180plusArray[$key] < 0) {
       //   $class = 'bg-danger';
        } elseif ($due151to180Array[$key] > 0 || $due151to180Array[$key] < 0) {
       //   $class = 'bg-yellow';
        } elseif ($due121to150Array[$key] > 0 || $due121to150Array[$key] < 0) {
       //   $class = 'bg-yellow';
        } elseif ($due91to120Array[$key] > 0 || $due91to120Array[$key] < 0) {
       //   $class = 'bg-yellow';
        } elseif ($due61to90Array[$key] > 0 || $due61to90Array[$key] < 0) {
       //   $class = 'bg-yellow';
        } elseif ($due31to60Array[$key] > 0 || $due31to60Array[$key] < 0) {
       //   $class = 'bg-yellow';
        } elseif ($due1to30Array[$key] > 0 || $due1to30Array[$key] < 0) {
       //   $class = 'bg-blue';
        }
      ?>

      <tr class="<?php echo e($class, false); ?>">
        <?php 
        $name = $row->name;
        if (!empty($row->supplier_business_name)) {
            if (!empty($name)) {
                $name .= ', ' . $row->supplier_business_name;
            } else {
                $name = $row->supplier_business_name;
            }
        }
        ?>

        <td>
          <a href="<?php echo e(route('contact.show', [$row->id]), false); ?>" target="_blank" class="no-print"><?php echo e($name, false); ?></a>
          <span class="print_section"><?php echo e($name, false); ?></span>
        </td>

        <td>
          <?php if($currentArray[$key] == 0): ?>
            <span class="display_currency current" data-currency_symbol="true" data-orig-value="<?php echo e($currentArray[$key], false); ?>"><?php echo e($currentArray[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency current <?php if($currentArray[$key] < 0): ?> text-danger <?php endif; ?> <?php if($currentArray[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($currentArray[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="1"><?php echo e($currentArray[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due1to30Array[$key] == 0): ?>
            <span class="display_currency due_1to30" data-currency_symbol="true" data-orig-value="<?php echo e($due1to30Array[$key], false); ?>"><?php echo e($due1to30Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_1to30 <?php if($due1to30Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due1to30Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due1to30Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="2"><?php echo e($due1to30Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due31to60Array[$key] == 0): ?>
            <span class="display_currency due_31to60" data-currency_symbol="true" data-orig-value="<?php echo e($due31to60Array[$key], false); ?>"><?php echo e($due31to60Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_31to60 <?php if($due31to60Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due31to60Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due31to60Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="3"><?php echo e($due31to60Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due61to90Array[$key] == 0): ?>
            <span class="display_currency due_61to90" data-currency_symbol="true" data-orig-value="<?php echo e($due61to90Array[$key], false); ?>"><?php echo e($due61to90Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_61to90 <?php if($due61to90Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due61to90Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due61to90Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="4"><?php echo e($due61to90Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due91to120Array[$key] == 0): ?>
            <span class="display_currency due_91to120" data-currency_symbol="true" data-orig-value="<?php echo e($due91to120Array[$key], false); ?>"><?php echo e($due91to120Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_91to120 <?php if($due91to120Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due91to120Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due91to120Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="5"><?php echo e($due91to120Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due121to150Array[$key] == 0): ?>
            <span class="display_currency due_121to150" data-currency_symbol="true" data-orig-value="<?php echo e($due121to150Array[$key], false); ?>"><?php echo e($due121to150Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_121to150 <?php if($due121to150Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due121to150Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due121to150Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="6"><?php echo e($due121to150Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due151to180Array[$key] == 0): ?>
            <span class="display_currency due_151to180" data-currency_symbol="true" data-orig-value="<?php echo e($due151to180Array[$key], false); ?>"><?php echo e($due151to180Array[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_151to180 <?php if($due151to180Array[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due151to180Array[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due151to180Array[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="7"><?php echo e($due151to180Array[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($due180plusArray[$key] == 0): ?>
            <span class="display_currency due_180plus" data-currency_symbol="true" data-orig-value="<?php echo e($due180plusArray[$key], false); ?>"><?php echo e($due180plusArray[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency due_180plus <?php if($due180plusArray[$key] < 0): ?> text-danger <?php endif; ?> <?php if($due180plusArray[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($due180plusArray[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="8"><?php echo e($due180plusArray[$key], false); ?></a>
          <?php endif; ?>
        </td>

        <td>
          <?php if($totalDueArray[$key] == 0): ?>
            <span class="display_currency total_due" data-currency_symbol="true" data-orig-value="<?php echo e($totalDueArray[$key], false); ?>"><?php echo e($totalDueArray[$key], false); ?></span>
          <?php else: ?>
            <a class="getdetails display_currency total_due <?php if($totalDueArray[$key] < 0): ?> text-danger <?php endif; ?> <?php if($totalDueArray[$key] > 0): ?> text-success <?php endif; ?>" data-currency_symbol="true" data-orig-value="<?php echo e($totalDueArray[$key], false); ?>" data-contact_id="<?php echo e($row->id, false); ?>" data-col="9"><?php echo e($totalDueArray[$key], false); ?></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endif; ?>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tbody>

        <tfoot>
            <tr class="bg-gray font-17 footer-total text-center">
                <td><strong><?php echo app('translator')->get('sale.total'); ?>:</strong></td>
                <td><span class="display_currency" id="footer_total_current_sup" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_0_30" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_31_60" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_61_90" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_91_120" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_121_150" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_151_180" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup_180" data-currency_symbol="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sup" data-currency_symbol="true"></span></td>
            </tr>
        </tfoot>
    </table>
</div><?php /**PATH /var/www/html/Modules/AgeingReport/Providers/../Resources/views/partials/supplier_contact.blade.php ENDPATH**/ ?>