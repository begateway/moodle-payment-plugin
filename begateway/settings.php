<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_begateway_settings', '', get_string('pluginname_desc', 'enrol_begateway')));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewayshop_id', get_string('shop_id', 'enrol_begateway'), get_string('shop_id_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewayshop_key', get_string('shop_key', 'enrol_begateway'), get_string('shop_key_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewaydomain_checkout', get_string('domain_checkout', 'enrol_begateway'), get_string('domain_checkout_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewaydomain_gateway', get_string('domain_gateway', 'enrol_begateway'), get_string('domain_gateway_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewaydomain_api', get_string('domain_api', 'enrol_begateway'), get_string('domain_api_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/enable_card', get_string('enable_card', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/enable_erip', get_string('enable_erip', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_begateway/begatewayerip_service_no', get_string('erip_service_no', 'enrol_begateway'), get_string('erip_service_no_desc', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/mode', get_string('mode', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/mailstudents', get_string('mailstudents', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/mailteachers', get_string('mailteachers', 'enrol_begateway'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_begateway/mailadmins', get_string('mailadmins', 'enrol_begateway'), '', 0));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_begateway_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_begateway/status',
        get_string('status', 'enrol_begateway'), get_string('status_desc', 'enrol_begateway'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_begateway/cost', get_string('cost', 'enrol_begateway'), '', 0, PARAM_FLOAT, 4));

        $begatewaycurrencies = array('USD' => 'USD',
                              'GBP' => 'GBP',
                              'EUR' => 'EUR',
                              'RUB' => 'RUB',
                              'BYN' => 'BYN'
                             );
    $settings->add(new admin_setting_configselect('enrol_begateway/currency', get_string('currency', 'enrol_begateway'), '', 'USD', $begatewaycurrencies));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_begateway/roleid',
            get_string('defaultrole', 'enrol_begateway'), get_string('defaultrole_desc', 'enrol_begateway'), $student->id, $options));
    }

    $settings->add(new admin_setting_configduration('enrol_begateway/enrolperiod',
        get_string('enrolperiod', 'enrol_begateway'), get_string('enrolperiod_desc', 'enrol_begateway'), 0));
}
