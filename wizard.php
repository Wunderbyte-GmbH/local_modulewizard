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
 * @package    local_edusupport
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_modulewizard\modulewizard; 
require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');

$courseid = optional_param('courseid', '2', PARAM_INT);


$context = \context_system::instance();
$PAGE->set_context($context);
require_login();
$PAGE->set_url(new moodle_url('/local/modulewizard/wizard.php', array()));

$title = "Modul Wizard";
$PAGE->set_title($title);
$PAGE->set_heading($title);

$modulewizard = new modulewizard;


$cm = get_coursemodule_from_id("mooduell", "8");
$modulewizard->update_module($cm, ['name' => 'test', 'intro' => 'test123'] );





/*$allcourselink =
            (has_capability('moodle/course:update', context_system::instance())
            || empty($CFG->block_course_list_hideallcourseslink)) &&
            core_course_category::user_top();

        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and
          !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {    // Just print My Courses
            if ($courses = enrol_get_my_courses()) {
                foreach ($courses as $course) {
                    $coursecontext = context_course::instance($course->id);
                    $linkcss = $course->visible ? "" : " class=\"dimmed\" ";
                    $content->items[]="<a $linkcss title=\"" . format_string($course->shortname, true, array('context' => $coursecontext)) . "\" ".
                               "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">".$icon.format_string(get_course_display_name_for_list($course)). "</a>";
                }
                $title = get_string('mycourses');
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if ($allcourselink) {
                    $content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a> ...";
                }
            }
        
        }

        // User is not enrolled in any courses, show list of available categories or courses (if there is only one category).
        $topcategory = core_course_category::top();
        if ($topcategory->is_uservisible() && ($categories = $topcategory->get_children())) { // Check we have categories.
            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {     // Just print top level category links
                foreach ($categories as $category) {
                    $categoryname = $category->get_formatted_name();
                    $linkcss = $category->visible ? "" : " class=\"dimmed\" ";
                    $content->items[]="<a $linkcss href=\"$CFG->wwwroot/course/index.php?categoryid=$category->id\">".$icon . $categoryname . "</a>";
                }
            /// If we can update any course of the view all isn't hidden, show the view all courses link
                if ($allcourselink) {
                   $content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                }
                $title = get_string('categories');
            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = $category->get_courses();

                if ($courses) {
                    foreach ($courses as $course) {
                        $coursecontext = context_course::instance($course->id);
                        $linkcss = $course->visible ? "" : " class=\"dimmed\" ";

                        $content->items[]="<a $linkcss title=\""
                                   . s($course->get_formatted_shortname())."\" ".
                                   "href=\"$CFG->wwwroot/course/view.php?id=$course->id\">"
                                   .$icon. $course->get_formatted_name() . "</a>";
                    }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if ($allcourselink) {
                        $content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">".get_string('fulllistofcourses').'</a> ...';
                    }
                } else {

                    $content->icons[] = '';
                    $content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $content->footer = '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'.get_string("addnewcourse").'</a> ...';
                    }

                }
                $title = get_string('courses');
            }
        }
*/


echo $OUTPUT->header();

$category = ['name' => 'Kat1',
'description' => format_text($description, FORMAT_HTML),
'catid' => '1',];
$data['category'][] = $category;

$courses = [
    'name' => 'Lorem ipsum',
    'description' => format_text($description, FORMAT_HTML),
    'cid' => '1',
];

$data['category'][0]['course'][] = $courses;

$courses = [
    'name' => 'test ipsum',
    'description' => format_text($description, FORMAT_HTML),
    'cid' => '2',
];

$data['category'][0]['course'][] = $courses;
echo $OUTPUT->render_from_template('local_modulewizard/categorylist', $data);
/*echo $renderer->grid_start('course-category-listings', 'columns-2');
$modinfo = get_fast_modinfo($courseid);
echo '<ul>';
foreach ($modinfo->cms as $cm) {
    echo '<li>'.'<input id="categorylistitem1" type="checkbox"  data-action="select">'
    .$cm->id.' '.$cm->name.' '.$cm->modname.'</li>';
}
echo '</ul>';

if (true) {
    echo $renderer->grid_column_start(5, 'category-listing');
    echo $renderer->category_listing($category);
    echo $renderer->grid_column_end();
}
if (true) {
    echo $renderer->grid_column_start(2, 'course-listing');

    echo $renderer->grid_column_end();
    if (true) {
        echo $renderer->grid_column_start($detailssize, 'course-detail');
        echo $renderer->course_detail($courseid);
        echo $renderer->grid_column_end();
    }
}
echo $renderer->grid_end();*/

echo $OUTPUT->footer();
