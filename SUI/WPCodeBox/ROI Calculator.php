<?php 

// Define the shortcode function

// [calculate_roi code="<CODES>"]

// CODES
// - annual-cost
// - labour-cost
// - roi
// - operational-cost-saving-per-year
// - time-saving-hours-per-year

function calculate_shortcode( $atts ) {

    $shortcode_atts = shortcode_atts(
        array(
            'code' => 'none',
        ),
        $atts,
        'calculate_roi'
    );
    $shortcode_code = $shortcode_atts['code'];

    
    // Default Values
    $duplicate = 0.05;
    $validate_reconciliation = 0.11; // Last value .15
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
        'apc-communication' => 0,  
        'apc-findingIncorrect' => 0,  
        'apc-otherTask' => 0,  
        
        'af-noEmployees' => 0,        
        'af-annual' => 0,        
        'af-dataProcess' => 0,    
        'af-communication' => 0,  
        'af-findingIncorrect' => 0,  
        'af-otherTask' => 0,  
        
        'fm-noEmployees' => 0,        
        'fm-annual' => 0,        
        'fm-dataProcess' => 0,  
        'fm-communication' => 0,  
        'fm-findingIncorrect' => 0,  
        'fm-otherTask' => 0,  

        'cfo-noEmployees' => 0,        
        'cfo-annual' => 0,        
        'cfo-dataProcess' => 0,
        'cfo-communication' => 0,  
        'cfo-findingIncorrect' => 0,  
        'cfo-otherTask' => 0,          
        
        // Additional tasks performed
        'atp_dy_erp' => false,
        'atp_dy_dup_invoice' => false,
        'atp_dy_spen_porders' => false,
        'atp_dy_monthly_recon' => false,
        'atp_dy_ap_process' => false,
    ), $atts );

    if(!isset( $atts['miv'] )){
        return '';
    }

    // Montly invoice volume
    $monthly_invoice_vol = intval($_GET['miv']);
    $erp_system = $_GET['erpSystem'];

    // AP Clerk
    $acp_no_of_employees = intval( $_GET['apc-noEmployees'] );
    $apc_annual_gross_salary = intval( $_GET['apc-annual'] );
    $apc_spent_on_data_entry = intval( $_GET['apc-dataProcess'] );
    $apc_communication = intval( $_GET['apc-communication'] );
    $apc_findingIncorrect= intval( $_GET['apc-findingIncorrect'] );
    $apc_otherTask= intval( $_GET['apc-otherTask'] );
    
    // Finance Admin
    $af_no_of_employees = intval( $_GET['af-noEmployees'] );
    $af_annual_gross_salary = intval( $_GET['af-annual'] );
    $af_spent_on_data_entry = intval( $_GET['af-dataProcess'] );
    $af_communication = intval( $_GET['af-communication'] );
    $af_findingIncorrect= intval( $_GET['af-findingIncorrect'] );
    $af_otherTask= intval( $_GET['af-otherTask'] );

    // Finance Manager
    $fm_no_of_employees = intval( $_GET['fm-noEmployees'] );
    $fm_annual_gross_salary = intval( $_GET['fm-annual'] );
    $fm_spent_on_data_entry = intval( $_GET['fm-dataProcess'] );
    $fm_communication = intval( $_GET['fm-communication'] );
    $fm_findingIncorrect= intval( $_GET['fm-findingIncorrect'] );
    $fm_otherTask= intval( $_GET['fm-otherTask'] );

    // CFO
    $cfo_no_of_employees = intval( $_GET['cfo-noEmployees'] );
    $cfo_annual_gross_salary = intval( $_GET['cfo-annual'] );
    $cfo_spent_on_data_entry = intval( $_GET['cfo-dataProcess'] );
    $cfo_communication = intval( $_GET['cfo-communication'] );
    $cfo_findingIncorrect= intval( $_GET['cfo-findingIncorrect'] );
    $cfo_otherTask= intval( $_GET['cfo-otherTask'] );

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

    if( $monthly_invoice_vol){
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

        // JONAS no PS
        if($erp_system == 'Jonas'){
            switch ( $monthly_invoice_vol ) {
                case 500:
                    $monthly_invoice_vol_val = 1695;            ;
                    break;
                case 1000:
                    $monthly_invoice_vol_val = 2320;
                    break;
                case 1500:
                    $monthly_invoice_vol_val = 2864.5;
                    break;
                case 2000:
                    $monthly_invoice_vol_val = 3421.034;
                    break;
                case 2500:
                    $monthly_invoice_vol_val = 4154.712;
                    break;
                case 3000:
                    $monthly_invoice_vol_val = 4734.5;
                    break;
                case 4000:
                    $monthly_invoice_vol_val = 5299.9;
                    break;
                case 5000:
                    $monthly_invoice_vol_val = 5882.9;
            }
        }

        // Automation cost
        // echo '<b> Automation cost <br> </b>';
        // Montly invoice volume + Automation Cost
        $ac_montly_invoice = $erp_system == 'Jonas' ? $monthly_invoice_vol_val : round(($monthly_invoice_vol_val + (($erp_system_val*0.2)/12)));
        // echo 'Montly invoice volume + Automation Cost = '. $ac_montly_invoice;
        // echo '<br>ERP System (Implementation Cost) + Automation Cost = '. $erp_system_val;

        // Annual automation cost
        // echo '<br><br><b>Annual automation cost <br></b>';
        $ac_montly_invoice_annual = $ac_montly_invoice * 12;
        $ac_erp_sys_annual = $erp_system == 'Jonas' ? 0 : round($erp_system_val / $contract_years);
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
        $ap_clerk_hr = round(($apc_spent_on_data_entry / 100) * 38, 1);
        $finance_admin_hr = round(($af_spent_on_data_entry / 100) * 38, 1);
        $finance_manager_hr = round(($fm_spent_on_data_entry / 100) * 38, 1);
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
        $ap = round(($apc_annual_gross_salary * ($apc_spent_on_data_entry / 100)) / 12) + round(($apc_annual_gross_salary * ($apc_communication / 100)) / 12) + round(($apc_annual_gross_salary * ($apc_findingIncorrect / 100)) / 12) + round(($apc_annual_gross_salary * ($apc_otherTask / 100)) / 12);
        $af = round(($af_annual_gross_salary * ($af_spent_on_data_entry / 100)) / 12) + round(($af_annual_gross_salary * ($af_communication / 100)) / 12) + round(($af_annual_gross_salary * ($af_findingIncorrect / 100)) / 12) + round(($af_annual_gross_salary * ($af_otherTask / 100)) / 12);
        $fm = round(($fm_annual_gross_salary * ($fm_spent_on_data_entry / 100)) / 12) + round(($fm_annual_gross_salary * ($fm_communication / 100)) / 12) + round(($fm_annual_gross_salary * ($fm_findingIncorrect / 100)) / 12) + round(($fm_annual_gross_salary * ($fm_otherTask / 100)) / 12);
        $cfo = round(($cfo_annual_gross_salary * ($cfo_spent_on_data_entry / 100)) / 12) + round(($cfo_annual_gross_salary * ($cfo_communication / 100)) / 12) + round(($cfo_annual_gross_salary * ($cfo_findingIncorrect / 100)) / 12) + round(($cfo_annual_gross_salary * ($cfo_otherTask / 100)) / 12);
        $montly_total = $ap + $af + $fm + $cfo;
        // echo '<b>Monthly salary</b>';
        // echo '<br>'.$apc_spent_on_data_entry;
        // echo '<br>'.$apc_communication;
        // echo '<br>'.$apc_findingIncorrect;
        // echo '<br>'.$apc_otherTask;
        // echo '<br> AP Clerk - '.$ap;
        // echo '<br> Finance Admin - '.$af;
        // echo '<br> Finance Manager - '.$fm;
        // echo '<br> cfo - '.$cfo;
        // echo '<br> Monthly Salary total: '.$montly_total .'<br><br><hr><br>';



        // Annual cost of processing manually
        $annual_cost = round(($total_cost + $montly_total) * 12);

        //Labour cost once automated
        $labour_cost = $annual_cost * $labour_cost_post_automation;

        // Total annual cost (post automation)
        $total_annual_cost = $labour_cost + $ac_montly_invoice_annual + $ac_erp_sys_annual;
        // echo '<br>'.$labour_cost .'+ '. $ac_montly_invoice_annual .'+'. $ac_erp_sys_annual;

        // ROI
        $roi = round($annual_cost / $total_annual_cost, 2);

        // Operational cost saving per year
        $operational_cost_saving_per_year = $annual_cost - $total_annual_cost;

        // Time saving hours per year
        $time_saving_hours_per_year = round(($automation_benefit * $total_process_invoices_hrs) * 12,1);


        switch ($shortcode_code) {
            case "annual-cost":
                echo '$'.number_format($annual_cost, 0, '.', ',');
                break;
            case "labour-cost":
                echo '$'.number_format($labour_cost, 0, '.', ',');
                break;
            case "roi":
                echo number_format($roi, 0, '.', ',');
                break;
            case "operational-cost-saving-per-year":
                echo '$'.number_format($operational_cost_saving_per_year, 0, '.', ',');
                break;
            case "time-saving-hours-per-year":
                echo number_format($time_saving_hours_per_year, 0, '.', ',');
                break;
            default:
                echo "N/A";
        }
    }else{
        echo '';
    }
    
}
// Register the shortcode
add_shortcode( 'calculate_roi', 'calculate_shortcode' );