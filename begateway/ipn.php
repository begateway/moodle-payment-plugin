<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Listens for Instant Payment Notification from begateway
 *
 * This script waits for Payment notification from begateway,
 * then double checks that data by sending it back to begateway.
 * If begateway verifies this then it sets up the enrolment for that
 * user.
 *
 * @package    enrol_begateway
 * @copyright  2017 eComCharge Ltd
 * @author     beGateway - based on code by others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
//define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

// begateway does not like when we return error messages here,
// the custom handler just logs exceptions and stops.
set_exception_handler('enrol_begateway_ipn_exception_handler');

$data = new stdClass();

$webhook = new \BeGateway\Webhook;

$custom = explode('|', $webhook->getTrackingId());

if (!$custom[0])
  die('Empty internal data');

$data->userid           = (int)$custom[0];
$data->courseid         = (int)$custom[1];
$data->instanceid       = (int)$custom[2];
$data->attempts         = (int)$custom[3];
$data->quizid           = (int)$custom[4];
$data->uid              = (string)$webhook->getUid();

$money = new \BeGateway\Money;
$money->setCents($webhook->getResponse()->transaction->amount);
$money->setCurrency($webhook->getResponse()->transaction->currency);

$data->payment_gross    = $money->getAmount();
$data->payment_currency = $money->getCurrency();
$data->timeupdated      = time();

/// get the user and course records

if (! $user = $DB->get_record("user", array("id"=>$data->userid))) {
    die("Not a valid user id");
}

if (! $course = $DB->get_record("course", array("id"=>$data->courseid))) {
    die("Not a valid course id");
}

if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
    die("Not a valid context id");
}

if (! $plugin_instance = $DB->get_record("enrol", array("id"=>$data->instanceid, "status"=>0))) {
    die("Not a valid instance id");
}

$plugin = enrol_get_plugin('begateway');

\BeGateway\Settings::$shopId = $plugin->get_config('begatewayshop_id');
\BeGateway\Settings::$shopKey = $plugin->get_config('begatewayshop_key');

if (! $webhook->isAuthorized()) {
    die("Not authorized notification");
}
// If status is pending and reason is other than echeck then we are on hold until further notice
// Email user to let them know. Email admin.

if ($webhook->isPending()) {
    die("Payment pending");
}

if (! $webhook->isSuccess()) {
    die("Status not successful. User unenrolled from course");
}

if ($webhook->isSuccess()) {          // VALID PAYMENT!

  // If currency is incorrectly set then someone maybe trying to cheat the system
  if ($data->payment_currency != $plugin_instance->currency) {
      die("Currency does not match course settings, received: ".$data->payment_currency);
  }

  // At this point we only proceed with a status of completed or pending with a reason of echeck
  if ($existing = $DB->get_record("enrol_begateway", array("uid"=>$webhook->getUid()))) {   // Make sure this transaction doesn't exist already
      die("Transaction {$webhook->getUid()} is being repeated!");
  }

  if (!$user = $DB->get_record('user', array('id'=>$data->userid))) {   // Check that user exists
      die("User $data->userid doesn't exist");
  }

  if (!$course = $DB->get_record('course', array('id'=>$data->courseid))) { // Check that course exists
      die("Course $data->courseid doesn't exist");
  }

  $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

  // Check that amount paid is the correct amount
  if ( (float) $plugin_instance->cost <= 0 ) {
      $cost = (float) $plugin->get_config('cost');
  } else {
      $cost = (float) $plugin_instance->cost;
  }

  $money2 = new \BeGateway\Money;
  $money2->setAmount($cost);
  $money2->setCurrency($plugin_instance->currency);

  $paid_attempt_cost = $data->payment_gross / $data->attempts;
  if ($paid_attempt_cost < $money2->getAmount()) {
      die("Amount paid is not enough ($paid_attempt_cost  < $cost))");
  }

  // Use the queried course's full name for the item_name field.
  $data->item_name = $course->fullname;

  // ALL CLEAR !

  if ($plugin_instance->enrolperiod) {
      $timestart = time();
      $timeend   = $timestart + $plugin_instance->enrolperiod;
  } else {
      $timestart = 0;
      $timeend   = 0;
  }

  // Enrol user
  $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

  // Pass $view=true to filter hidden caps if the user cannot see them
  if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                       '', '', '', '', false, true)) {
      $users = sort_by_roleassignment_authority($users, $context);
      $teacher = array_shift($users);
  } else {
      $teacher = false;
  }

  if ((int)$data->quizid > 0) {
    quiz_begateway_update($webhook->getTrackingId(), $context);
    $DB->insert_record("enrol_begateway", $data);
  }

  $mailstudents = $plugin->get_config('mailstudents');
  $mailteachers = $plugin->get_config('mailteachers');
  $mailadmins   = $plugin->get_config('mailadmins');
  $shortname = format_string($course->shortname, true, array('context' => $context));

  if ((int)$mailstudents == 1) {
      $a = new stdClass();
      $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
      $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

      $eventdata = new \core\message\message();
      $eventdata->modulename        = 'moodle';
      $eventdata->component         = 'enrol_begateway';
      $eventdata->name              = 'begateway_enrolment';
      $eventdata->userfrom          = empty($teacher) ? core_user::get_support_user() : $teacher;
      $eventdata->userto            = $user;
      $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
      $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
      $eventdata->fullmessageformat = FORMAT_PLAIN;
      $eventdata->fullmessagehtml   = '';
      $eventdata->smallmessage      = '';
      message_send($eventdata);

  }

  if ((int)$mailteachers == 1 && (int)$teacher == 1) {
      $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
      $a->user = fullname($user);

      $eventdata = new \core\message\message();
      $eventdata->modulename        = 'moodle';
      $eventdata->component         = 'enrol_begateway';
      $eventdata->name              = 'begateway_enrolment';
      $eventdata->userfrom          = $user;
      $eventdata->userto            = $teacher;
      $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
      $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
      $eventdata->fullmessageformat = FORMAT_PLAIN;
      $eventdata->fullmessagehtml   = '';
      $eventdata->smallmessage      = '';
      message_send($eventdata);
  }

  if ((int)$mailadmins == 1) {
      $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
      $a->user = fullname($user);
      $admins = get_admins();
      foreach ($admins as $admin) {
          $eventdata = new \core\message\message();
          $eventdata->modulename        = 'moodle';
          $eventdata->component         = 'enrol_begateway';
          $eventdata->name              = 'begateway_enrolment';
          $eventdata->userfrom          = $user;
          $eventdata->userto            = $admin;
          $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
          $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
          $eventdata->fullmessageformat = FORMAT_PLAIN;
          $eventdata->fullmessagehtml   = '';
          $eventdata->smallmessage      = '';
          message_send($eventdata);
      }
  }

}
echo "OK";
exit;

//--- HELPER FUNCTIONS --------------------------------------------------------------------------------------

function quiz_begateway_update($tracking_id, $context) {
  global $DB;
  list($user_id, $course_id, $instance_id, $attempts, $quiz_id) = explode('|', $tracking_id);
  $fromform = new stdClass;

  $course = $DB->get_record('course', array('id'=>(int)$course_id), '*', MUST_EXIST);
  if (!$course) {
      throw new coding_exception('coursedoesnotexists');
  }

  $instance = $DB->get_record('enrol', array(
    'id'=>(int)$instance_id,
    'courseid' => (int)$course_id,
    'enrol'=>'begateway'), '*', MUST_EXIST);
  if (!$instance) {
    throw new coding_exception('invalid enrol instance!');
  }

  $quiz = $DB->get_record('quiz', array('course' => $course->id, 'id' => $quiz_id), '*', MUST_EXIST);
  if (!$quiz) {
    throw new coding_exception('invalidquizid');
  }

  $override = $DB->get_record('quiz_overrides', array(
    'quiz' => $quiz->id,
    'userid' => $user_id
  ));
  $params = array(
      'context' => $context,
      'other' => array(
          'quizid' => $quiz->id
      )
  );

  if (isset($override->id) && (int)$override->id > 0) {
      $fromform->id = $override->id;
      $fromform->quiz = $quiz->id;
      $fromform->attempts = $override->attempts + $attempts;
      $fromform->userid = $user_id;

      $DB->update_record('quiz_overrides', $fromform);

      $params['objectid'] = $override->id;
      $params['relateduserid'] = $fromform->userid;

      $event = \mod_quiz\event\user_override_updated::create($params);

      // Trigger the override updated event.
     $event->trigger();
  } else {
      unset($fromform->id);
      $fromform->attempts = $attempts + 1; // to count an attempt assigned upon course enrollment
      $fromform->userid = $user_id;
      $fromform->quiz = $quiz->id;
      $fromform->id = $DB->insert_record('quiz_overrides', $fromform);

      $params['relateduserid'] = $fromform->userid;
      $event = \mod_quiz\event\user_override_created::create($params);

      // Trigger the override created event.
      $event->trigger();
  }
}

function message_begateway_error_to_admin($subject, $data) {
    echo $subject;
    $admin = get_admin();
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }

    $eventdata = new \core\message\message();
    $eventdata->modulename        = 'moodle';
    $eventdata->component         = 'enrol_begateway';
    $eventdata->name              = 'begateway_enrolment';
    $eventdata->userfrom          = $admin;
    $eventdata->userto            = $admin;
    $eventdata->subject           = "begateway ERROR: ".$subject;
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';
    $eventdata->smallmessage      = '';
    message_send($eventdata);
}

/**
 * Silent exception handler.
 *
 * @param Exception $ex
 * @return void - does not return. Terminates execution!
 */
function enrol_begateway_ipn_exception_handler($ex) {
    $info = get_exception_info($ex);

    $logerrmsg = "enrol_begateway IPN exception handler: ".$info->message;
    if (debugging('', DEBUG_NORMAL)) {
        $logerrmsg .= ' Debug: '.$info->debuginfo."\n".format_backtrace($info->backtrace, true);
    }
    error_log($logerrmsg);

    exit(0);
}
