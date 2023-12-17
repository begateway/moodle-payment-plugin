<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class enrol_begateway_edit_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_begateway'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_begateway'), $options);
        $mform->setDefault('status', $plugin->get_config('status'));
        $mform->setType('status', PARAM_TEXT);

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_begateway'), array('size'=>4));
        $mform->setDefault('cost', $plugin->get_config('cost'));
        $mform->setType('cost', PARAM_RAW);
        $mform->setType('name', PARAM_TEXT);

        $begatewaycurrencies = array('USD' => 'USD',
                              'GBP' => 'GBP',
                              'EUR' => 'EUR',
                              'RUB' => 'RUB',
                              'BYN' => 'BYN'
                             );
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_begateway'), $begatewaycurrencies);
        $mform->setDefault('currency', $plugin->get_config('currency'));
        $mform->setType('currency', PARAM_TEXT);

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_begateway'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));
        $mform->setType('roleid', PARAM_INT);

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_begateway'), array('optional' => true, 'defaultunit' => 86400));
        $mform->setDefault('enrolperiod', $plugin->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_begateway');
        $mform->setType('enrolperiod', PARAM_INT);

        $mform->addElement('date_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_begateway'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_begateway');
        $mform->setType('enrolstartdate', PARAM_INT);

        $mform->addElement('date_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_begateway'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_begateway');
        $mform->setType('enrolenddate', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_begateway');
            }

            if (!is_numeric($data['cost'])) {
                $errors['cost'] = get_string('costerror', 'enrol_begateway');

            }
        }

        return $errors;
    }
}
