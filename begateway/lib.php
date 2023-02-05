<?php

defined('MOODLE_INTERNAL') || die("Cannot be included");

require_once(__DIR__ . '/begateway-api-php/lib/BeGateway.php');

/**
 * begateway enrolment plugin implementation.
 * @author  eComCharge Ltd - based on code by Martin Dougiamas and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_begateway_plugin extends enrol_plugin {

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        return array(new pix_icon('icon', get_string('pluginname', 'enrol_begateway'), 'enrol_begateway'));
    }

    public function roles_protected() {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users manually - requires enrol/begateway:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status - requires enrol/begateway:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'begateway') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/begateway:config', $context)) {
            $managelink = new moodle_url('/enrol/begateway/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'begateway') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/begateway:config', $context)) {
            $editlink = new moodle_url("/enrol/begateway/edit.php", array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/begateway:config', $context)) {
            return NULL;
        }

        // multiple instances supported - different cost for different roles
        return new moodle_url('/enrol/begateway/edit.php', array('courseid'=>$courseid));
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    function enrol_page_hook(stdClass $instance, $options = array() ) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        ob_start();

        $options['buymoreattempts'] = (isset($options['buymoreattempts'])) ? $options['buymoreattempts'] : false;
        $options['quiz_id'] = (isset($options['quiz_id'])) ? $options['quiz_id'] : 0;

        if (!$options['buymoreattempts'] && $DB->record_exists('user_enrolments', array('userid'=>$USER->id, 'enrolid'=>$instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id'=>$instance->courseid));
        $context = context_course::instance($course->id);

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them
        if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                             '', '', '', '', false, true)) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ( (float) $instance->cost <= 0 ) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // no cost, other enrolment methods (instances) should be used
            echo '<p>'.get_string('nocost', 'enrol_begateway').'</p>';
        } else {

            if (isguestuser()) { // force login only for guest user, not real users with guest role
                if (empty($CFG->loginhttps)) {
                    $wwwroot = $CFG->wwwroot;
                } else {
                    // This actually is not so secure ;-), 'cause we're
                    // in unencrypted connection...
                    $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
                }
                echo '<div class="mdl-align"><p>'.get_string('paymentrequired').'</p>';
                echo '<p><b>'.get_string('cost').": $instance->currency $cost".'</b></p>';
                echo '<p><a href="'.$wwwroot.'/login/">'.get_string('loginsite').'</a></p>';
                echo '</div>';
            } else {
                //Sanitise some fields before building the begateway form
                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $courseshortname = $shortname;
                $userfullname    = fullname($USER);
                $userfirstname   = $USER->firstname;
                $userlastname    = $USER->lastname;
                $useraddress     = $USER->address;
                $usercity        = $USER->city;
                $instancename    = $this->get_instance_name($instance);

                \BeGateway\Settings::$shopId = $this->get_config('begatewayshop_id');
                \BeGateway\Settings::$shopKey = $this->get_config('begatewayshop_key');
                \BeGateway\Settings::$gatewayBase = 'https://' . $this->get_config('begatewaydomain_gateway');
                \BeGateway\Settings::$checkoutBase = 'https://' . $this->get_config('begatewaydomain_checkout');
                \BeGateway\Settings::$apiBase = 'https://' . $this->get_config('begatewaydomain_api');

                $transaction = new \BeGateway\GetPaymentToken;

                $transaction->money->setAmount($cost);
                $transaction->money->setCurrency($instance->currency);
                $transaction->setDescription($coursefullname);
                $transaction->setLanguage(current_language());
                $transaction->customer->setFirstName($userfirstname);
                $transaction->customer->setLastName($userlastname);
                $transaction->customer->setAddress($useraddress);
                $transaction->customer->setCity($usercity);
                $transaction->customer->setCountry($USER->country);
                if (isset($USER->profile['zip']))
                  $transaction->customer->setZip($USER->profile['zip']);

                if (in_array($USER->country, array('US', 'CA')))
                  $transaction->customer->setState($USER->profile['state']);

                $transaction->customer->setEmail($USER->email);

                if ($this->get_config('begatewaydomain_gateway') === true)
                  $transaction->setTestMode();

                $notification_url = "$CFG->wwwroot/enrol/begateway/ipn.php";
                $notification_url = str_replace('carts.local', 'webhook.begateway.com:8443', $notification_url);
                $transaction->setNotificationUrl($notification_url);

                $return_url = "$CFG->wwwroot/enrol/begateway/return.php?id=$course->id" . "&uniqid=" . time();
                $transaction->setSuccessUrl($return_url);
                $transaction->setDeclineUrl($return_url);
                $transaction->setFailUrl($return_url);

                $error = null;
                $tokens = array();

                $count = $options['buymoreattempts'] ? 6 : 2;

                for ($i=1; $i < $count; $i++) {
                  $token = clone $transaction;
                  $token->money->setAmount($cost*$i);
                  $token->setDescription($coursefullname . '. ' .
                    get_string("tries", "enrol_begateway", $i) . '. ' .
                    get_string("user", "enrol_begateway", $USER->username)
                  );

                  if ((int)$this->get_config('enable_card') == 1) {
                    $cc = new \BeGateway\PaymentMethod\CreditCard;
                    $token->addPaymentMethod($cc);
                  }

                  if ((int)$this->get_config('enable_erip') == 1) {
                    $erip_id = "{$USER->id}{$course->id}{$instance->id}{$i}" . rand(1000,9999);

                    $erip = new \BeGateway\PaymentMethod\Erip(array(
                      'order_id' => intval(substr($erip_id, 0, 12)),
                      'account_number' => $erip_id,
                      'service_no' => $this->get_config('begatewayerip_service_no'),
                      'service_info' => get_string("erip_service_info", "enrol_begateway", $coursefullname)
                    ));
                    $token->addPaymentMethod($erip);
                  }

                  $token->setTrackingId("{$USER->id}|{$course->id}|{$instance->id}|$i|{$options['quiz_id']}");
                  try{
                    $response = $token->submit();
                    if (!$response->isSuccess())
                      throw new \Exception(get_string('gettokenerror'));

                    $tokens[$i] = array(
                      'redirect_url' => $response->getRedirectUrlScriptName(),
                      'token' => $response->getToken()
                    );

                  }catch(Exception $e) {
                    $error = $e->getMessage();
                  }
                }

                include($CFG->dirroot.'/enrol/begateway/enrol.html');
            }

        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/begateway:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, array('class'=>'unenrollink', 'rel'=>$ue->id));
        }
        return $actions;
    }

    protected function _getToken() {
                //Sanitise some fields before building the begateway form
                $coursefullname  = format_string($course->fullname, true, array('context'=>$context));
                $courseshortname = $shortname;
                $userfullname    = fullname($USER);
                $userfirstname   = $USER->firstname;
                $userlastname    = $USER->lastname;
                $useraddress     = $USER->address;
                $usercity        = $USER->city;
                $instancename    = $this->get_instance_name($instance);

    }
    public function cron() {
        $trace = new text_progress_trace();
        $this->process_expirations($trace);
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace) {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/begateway:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/begateway:config', $context);
    }

}
