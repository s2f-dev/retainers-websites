<?php 

// Define the shortcode function
function calculate_shortcode( $atts ) {
    
    // Default Values
    $duplicate = 0.05;
    $validate_reconciliation = 0.1; // Last value .15
    $statement_recon = 0.1;
    $automated_workflow = 0.03; // Last value 
    $any_others = 0.03;
    $labour_cost_post_automation = 0.16;
    $contract_years = 3;
    $automation_benefit = 0.84;


    // Extract the attributes
    $atts = shortcode_atts( array(
        // General Details
        'miv' => 0,
        'erpSystem' => 0,

        //Roles within the finance team that touch the AP process
        'apc-noEmployees' => 0,        
        'apc-annual' => 0,        
        'apc-dataProcess' => 0,  
        'af-noEmployees' => 0,        
        'af-annual' => 0,        
        'af-dataProcess' => 0,    
        'mf-noEmployees' => 0,        
        'mf-annual' => 0,        
        'mf-dataProcess' => 0,  
        'cfo-noEmployees' => 0,        
        'cfo-annual' => 0,        
        'cfo-dataProcess' => 0,        
        
        // Additional tasks performed
        'atp_dy_erp' => false,
        'atp_dy_dup_invoice' => false,
        'atp_dy_spen_porders' => false,
        'atp_dy_monthly_recon' => false,
        'atp_dy_ap_process' => false,
    ), $atts );

    // Montly invoice volume
    $monthly_invoice_vol = intval($_GET['miv']);
    $erp_system = $_GET['erpSystem'];

    // AP Clerk
    $acp_no_of_employees = intval( $_GET['apc-noEmployees'] );
    $acp_annual_gross_salary = intval( $_GET['apc-annual'] );
    $acp_spent_on_data_entry = intval( $_GET['apc-dataProcess'] );
    
    // Finance Admin
    $af_no_of_employees = intval( $_GET['af-noEmployees'] );
    $af_annual_gross_salary = intval( $_GET['af-annual'] );
    $af_spent_on_data_entry = intval( $_GET['af-dataProcess'] );

    // Finance Manager
    $mf_no_of_employees = intval( $_GET['fm-noEmployees'] );
    $mf_annual_gross_salary = intval( $_GET['fm-annual'] );
    $mf_spent_on_data_entry = intval( $_GET['fm-dataProcess'] );

    // CFO
    $cfo_no_of_employees = intval( $_GET['cfo-noEmployees'] );
    $cfo_annual_gross_salary = intval( $_GET['cfo-annual'] );
    $cfo_spent_on_data_entry = intval( $_GET['cfo-dataProcess'] );

    // ADDITINAL TASKS PERFORMED
    
    // Does your ERP system have digital approvals configured?
    $atp_dy_erp = $_GET['atp_dy_erp'];
    
    //Do you spend time correcting/finding duplicate invoices?
    $atp_dy_dup_invoice = $_GET['atp_dy_dup_invoice'];
    
    // Do you spend time validating purchase orders manually?
    $atp_dy_spen_porders = $_GET['atp_dy_spen_porders'];
    
    // Do you spend time doing monthly statement reconciliation?
    $atp_dy_monthly_recon = $_GET['atp_dy_monthly_recon'];
    
    //Do you spend time doing any other tasks in relation to the AP process (could be frudulent invoices, etc)
    $atp_dy_ap_process = $_GET['atp_dy_ap_process'];


    // CALCULATION
    
    // Days taken to manually enter invoices
    $days_taken_to_manually_enter_invoices = (($monthly_invoice_vol * 8.3)/60)/8;

    // ERP System (Implementation Cost) Value
    $erp_system_val = 0;
    switch ( $erp_system ) {
        case 'XERO':
            $erp_system_val = 15000;            ;
            break;
        case 'Jonas':
            $erp_system_val = 15000;
            break;
        case 'NetSuite':
            $erp_system_val = 15000;
            break;
        case 'MYOB':
            $erp_system_val = 20000;
            break;
        case 'MS 365 BC':
            $erp_system_val = 30000;
            break;
        case 'Oracle':
            $erp_system_val = 30000;
            break;
        case 'SAP Biz1':
            $erp_system_val = 30000;
            break;
        case 'Sage':
            $erp_system_val = 30000;
            break;
        case 'Quickbooks':
            $erp_system_val = 30000;
            break;
        case 'Exact':
            $erp_system_val = 30000;
            break;
        default:
            $erp_system_val = 0;
    }

    // Montly invoice volume
    $monthly_invoice_vol_val = 0;
    switch ( $monthly_invoice_vol ) {
        case 500:
            $monthly_invoice_vol_val = 645;            ;
            break;
        case 1000:
            $monthly_invoice_vol_val = 1245;
            break;
        case 1500:
            $monthly_invoice_vol_val = 1765;
            break;
        case 2000:
            $monthly_invoice_vol_val = 2296;
            break;
        case 2500:
            $monthly_invoice_vol_val = 3005;
            break;
        case 3000:
            $monthly_invoice_vol_val = 3560;
            break;
        case 4000:
            $monthly_invoice_vol_val = 3657;
            break;
        case 5000:
            $monthly_invoice_vol_val = 4059;
    }

    // Automation cost
    // echo '<b> Automation cost <br> </b>';
    // Montly invoice volume + Automation Cost
    $ac_montly_invoice = round(($monthly_invoice_vol_val + (($erp_system_val*0.2)/12)));
    // echo 'Montly invoice volume + Automation Cost = '. $ac_montly_invoice;
    // echo '<br>ERP System (Implementation Cost) + Automation Cost = '. $erp_system_val;

    // Annual automation cost
    // echo '<br><br><b>Annual automation cost <br></b>';
    $ac_montly_invoice_annual = $ac_montly_invoice * 12;
    $ac_erp_sys_annual = round($erp_system_val / $contract_years);
    // echo 'Montly invoice volume + Automation Annual Cost = '. $ac_montly_invoice_annual;
    // echo '<br>ERP System (Implementation Cost) + Automation Annual Cost = '. $ac_erp_sys_annual;


    // echo '<br><hr><br> <b>Additional tasks performed</b>';
    // FORMULA - Does your ERP system have digital approvals configured? 
    $atp_dy_erp_bol = 0;
    if($atp_dy_erp == 'No'){
        $atp_dy_erp_bol = 20;
    }
    $atp_dy_erp_amount =  $atp_dy_erp_bol * ($monthly_invoice_vol * $automated_workflow);
    // echo '<br>Does your ERP system have digital approvals configured? - '.$atp_dy_erp_amount;
    
    // FORMULA - Do you spend time correcting/finding duplicate invoices?
    $atp_dy_dup_invoice_bol = 0;
    if($atp_dy_dup_invoice == 'Yes'){
        $atp_dy_dup_invoice_bol = 15;
    }
    $atp_dy_dup_invoice_amount = $atp_dy_dup_invoice_bol * ($monthly_invoice_vol * $duplicate);
    // echo '<br>Do you spend time correcting/finding duplicate invoices? - '.$atp_dy_dup_invoice_amount;

    // FORMULA - Do you spend time validating purchase orders manually?
    $atp_dy_spen_porders_bol = 0;
    if($atp_dy_spen_porders == 'Yes'){
        $atp_dy_spen_porders_bol = 15;
    }
    $atp_dy_spen_porders_amount = $atp_dy_spen_porders_bol * ($monthly_invoice_vol * $validate_reconciliation);
    // echo '<br>Do you spend time validating purchase orders manually? - '.$atp_dy_spen_porders_amount;

    // FORMULA - Do you spend time doing monthly statement reconciliation?
    $atp_dy_monthly_recon_bol = 0;
    if($atp_dy_monthly_recon == 'Yes'){
        $atp_dy_monthly_recon_bol = 15;
    }
    $atp_dy_monthly_recon_amount = $atp_dy_monthly_recon_bol * ($monthly_invoice_vol * $statement_recon);
    // echo '<br>Do you spend time doing monthly statement reconciliation? - '.$atp_dy_monthly_recon_amount;

    // FORMULA - Do you spend time doing any other tasks in relation to the AP process (could be frudulent invoices, etc)
    $atp_dy_ap_process_bol = 0;
    if($atp_dy_ap_process == 'Yes'){
        $atp_dy_ap_process_bol = 15;
    }
    $atp_dy_ap_process_amount = $atp_dy_ap_process_bol * ($monthly_invoice_vol * $any_others);
    // echo '<br>Do you spend time doing any other tasks in relation to the AP process (could be frudulent invoices, etc) - '.$atp_dy_ap_process_amount;

    
    // Total Cost (Other activites)
    $total_cost = $atp_dy_erp_amount + $atp_dy_dup_invoice_amount + $atp_dy_spen_porders_amount + $atp_dy_monthly_recon_amount + $atp_dy_ap_process_amount;
    // echo '<br>Total Cost: '.$total_cost.'<br><hr>';

    // Hours processing invoices
    $ap_clerk_hr = round(($acp_spent_on_data_entry / 100) * 38, 1);
    $finance_admin_hr = round(($af_spent_on_data_entry / 100) * 38, 1);
    $finance_manager_hr = round(($mf_spent_on_data_entry / 100) * 38, 1);
    $cfo_hr = round(($cfo_spent_on_data_entry / 100) * 38, 1);
    $total_process_invoices_hrs = $ap_clerk_hr + $finance_admin_hr + $finance_manager_hr + $cfo_hr;
    // echo'<br><b>Hours processing invoices</b><br>';
    // echo'AP Clerk - '. $ap_clerk_hr;
    // echo'<br> Finance Admin - '. $finance_admin_hr;
    // echo'<br> Finance Manager - '. $finance_manager_hr;
    // echo'<br> CFO - '. $cfo_hr;
    // echo'<br> Hours processing invoices Total: '. $total_process_invoices_hrs;
    // echo '<br><hr><br>';


    // Monthly salary
    $ap = round(($acp_annual_gross_salary * ($acp_spent_on_data_entry / 100)) / 12);
    $af = round(($af_annual_gross_salary * ($af_spent_on_data_entry / 100)) / 12);
    $mf = round(($mf_annual_gross_salary * ($mf_spent_on_data_entry / 100)) / 12);
    $cfo = round(($cfo_annual_gross_salary * ($cfo_spent_on_data_entry / 100)) / 12);
    $montly_total = $ap + $af + $mf + $cfo;
    // echo '<b>Monthly salary</b>';
    // echo '<br> AP Clerk - '.$ap;
    // echo '<br> Finance Admin - '.$af;
    // echo '<br> Finance Manager - '.$mf;
    // echo '<br> cfo - '.$cfo;
    // echo '<br> Monthly Salary total: '.$montly_total .'<br><br><hr><br>';



    // Annual cost of processing manually
    $annual_cost = round(($total_cost + $montly_total) * 12);

    //Labour cost once automated
    $labour_cost = $annual_cost * $labour_cost_post_automation;

    // Total annual cost (post automation)
    $total_annual_cost = $labour_cost + $ac_montly_invoice_annual + $ac_erp_sys_annual;

    // ROI
    $roi = round($annual_cost / $total_annual_cost, 2);

    // Operational cost saving per year
    $operational_cost_saving_per_year = $annual_cost - $total_annual_cost;

    // Time saving hours per year
    $time_saving_hours_per_year = round(($automation_benefit * $total_process_invoices_hrs) * 12,1);

    echo '
        <style>
            .c-boxes{
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                grid-gap: 20px;
                margin-bottom: 2em;
            }
            .c-box{
                padding: 1em;
                width: 340px;
                border: solid 1px;
                border-radius: 14px;
            }

            .c-box .c-value{
                font-weight: 900;
                font-size: 40px;
                color: #172c51;
            }
            .c-box span{
                font-size:12px;
            }

        
        </style>
        <div class="c-wrap">
            <div class="c-boxes">
                <div class="c-box">
                    <div class="c-value">$'.number_format($annual_cost, 2, '.', ',').'</div>
                    <span>Annual cost of processing manually</span>
                </div>
                <div class="c-box">
                    <div class="c-value"> $'.number_format($labour_cost, 2, '.', ',').'</div>
                    <span>Labour cost once automated</span>
                </div>
                <div class="c-box">
                    <div class="c-value"> $'.number_format($total_annual_cost, 2, '.', ',').'</div>
                    <span>Total annual cost (post automation)</span>
                </div>
            </div>  
            <div class="c-boxes">
                <div class="c-box">
                    <div class="c-value"> '.number_format($roi, 2, '.', ',').'</div>
                    <span>ROI</span>
                </div>
                <div class="c-box">
                    <div class="c-value"> $'.number_format($operational_cost_saving_per_year, 2, '.', ',').'</div>
                    <span>Operational cost saving per year</span>
                </div>
                <div class="c-box">
                    <div class="c-value"> '.number_format($time_saving_hours_per_year, 2, '.', ',').'</div>
                    <span>Time saving hours per year</span>
                </div>
            </div>  
        </div>  
    ';
}
// Register the shortcode
add_shortcode( 'calculate_roi', 'calculate_shortcode' );