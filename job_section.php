<?php

if (!defined('ABSPATH')) {

    exit;

}



global $wpdb;

$jobs_table = $wpdb->prefix . 'jobs'; // Jobs table name



// Handle tab switching

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all_jobs';



// Handle delete job

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['job_id'])) {

    $job_id = intval($_GET['job_id']);

    $wpdb->delete($jobs_table, ['id' => $job_id]);

    echo '<div class="updated"><p>Job deleted successfully.</p></div>';

}



// Handle the country filter

$filter_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';



// Get all distinct countries for filter

$countries = $wpdb->get_col("SELECT DISTINCT country FROM $jobs_table");



?>

<h2><?php esc_html_e('Job Section', 'hrjobs'); ?></h2>



<h2 class="nav-tab-wrapper">

    <a href="?page=hrjobs&tab=all_jobs" class="nav-tab <?php echo $current_tab == 'all_jobs' ? 'nav-tab-active' : ''; ?>">All Jobs</a>

    <a href="?page=hrjobs&tab=add_job" class="nav-tab <?php echo $current_tab == 'add_job' ? 'nav-tab-active' : ''; ?>">Add Job</a>

    <a href="?page=hrjobs&tab=open_jobs" class="nav-tab <?php echo $current_tab == 'open_jobs' ? 'nav-tab-active' : ''; ?>">Open Jobs</a>

    <a href="?page=hrjobs&tab=closed_jobs" class="nav-tab <?php echo $current_tab == 'closed_jobs' ? 'nav-tab-active' : ''; ?>">Closed Jobs</a>

</h2>



<div class="tab-content">

    <?php

    switch ($current_tab) {

        case 'add_job':

            hrjobs_add_job_form($wpdb, $jobs_table);

            break;

        case 'open_jobs':

            hrjobs_list_jobs($wpdb, $jobs_table, 1, $filter_country);

            break;

        case 'closed_jobs':

            hrjobs_list_jobs($wpdb, $jobs_table, 0, $filter_country);

            break;

        case 'all_jobs':

        default:

            hrjobs_list_jobs($wpdb, $jobs_table, null, $filter_country);

            break;

    }

    ?>

</div>



<?php



// Function to display the "Add Job" form

function hrjobs_add_job_form($wpdb, $jobs_table)

{

    if (isset($_POST['submit_job'])) {

        $data = [

            'job_title' => sanitize_text_field($_POST['job_title']),

            'location' => sanitize_text_field($_POST['location']),

            'country' => sanitize_text_field($_POST['country']),

            'tagline' => sanitize_text_field($_POST['tagline']),

            'job_brief' => wp_kses_post($_POST['job_brief']),

            'responsibilities' => wp_kses_post($_POST['responsibilities']),

            'requirement' => wp_kses_post($_POST['requirement']),

            'date_of_application' => current_time('mysql'),

            'status' => 1

        ];

        $wpdb->insert($jobs_table, $data);

        echo '<div class="updated"><p>Job added successfully.</p></div>';

    }

?>


<form method="POST">
    <table class="form-table">
        <tr>
            <th><label for="job_title">Job Title</label></th>
            <td><input type="text" id="job_title" name="job_title" required></td>
        </tr>

        <tr>
            <th><label for="location">Location</label></th>
            <td><input type="text" id="location" name="location" required></td>
        </tr>

        <tr>
            <th><label for="country">Country</label></th>
            <td><input type="text" id="country" name="country" required></td>
        </tr>

        <tr>
            <th><label for="tagline">Tagline</label></th>
            <td><input type="text" id="tagline" name="tagline"></td>
        </tr>

        <tr>
            <th><label for="job_brief">Job Brief</label></th>
            <td>
            <?php
                // Apply the WordPress editor to the job_brief field
                wp_editor('', 'job_brief', [
                    'textarea_name' => 'job_brief',
                    'textarea_rows' => 6,  // You can adjust the number of rows if needed
                    'editor_class' => 'wp-editor-area',  // This is the class for WordPress editor
                    'media_buttons' => false, // Hide the media buttons
                    'quicktags' => true,  // Enable quick tags for basic formatting
                ]);
                ?>
            </td>
        </tr>

        <tr>
            <th><label for="responsibilities">Responsibilities</label></th>
            <td>
                <?php 
                wp_editor('', 'responsibilities', [
                    'textarea_name' => 'responsibilities',
                    'textarea_rows' => 6,
                    'editor_class' => 'wp-editor-area',
                    'media_buttons' => false,
                    'quicktags' => true,
                ]);
                ?>
            </td>
        </tr>

        <tr>
            <th><label for="requirement">Requirement</label></th>
            <td>
                <?php
                // Apply the WordPress editor to the requirement field
                wp_editor('', 'requirement', [
                    'textarea_name' => 'requirement',
                    'textarea_rows' => 6,
                    'editor_class' => 'wp-editor-area',
                    'media_buttons' => false,
                    'quicktags' => true,
                ]);
                ?>
            </td>
        </tr>
    </table>

    <p><input type="submit" name="submit_job" value="Add Job" class="button button-primary"></p>
</form>

<!-- Enqueue WordPress TinyMCE and Admin Scripts -->
<?php
// function enqueue_custom_admin_scripts($hook) {
//     // Make sure it's the correct admin page
//     if ('toplevel_page_hrjobs' != $hook) return;

//     // Enqueue WordPress TinyMCE scripts and styles
//     wp_enqueue_script('tiny_mce');
//     wp_enqueue_script('wp-tinymce');
//     wp_enqueue_script('jquery');
// }
// add_action('admin_enqueue_scripts', 'enqueue_custom_admin_scripts');
?>

<?php

}



// Function to list jobs (can be filtered by status and country)
function hrjobs_list_jobs($wpdb, $jobs_table, $status = null, $filter_country = '') {
    // Check if the form has been submitted and capture the selected portfolio category
    $filter_portfolio_category = isset($_POST['filter_portfolio_category']) ? sanitize_text_field($_POST['filter_portfolio_category']) : '';

    // Country filter form
?>
<form method="POST" style="margin-bottom: 20px;">
    <label for="filter_portfolio_category">Filter by Portfolio Category:</label>
    <select name="filter_portfolio_category" id="filter_portfolio_category">
        <option value="">All Categories</option>
        <?php
        // Get all categories from the 'portfolio-types' taxonomy
        $portfolio_categories = get_terms(array(
            'taxonomy' => 'portfolio-types',
            'hide_empty' => false,
        ));

        if (!empty($portfolio_categories) && !is_wp_error($portfolio_categories)) {
            foreach ($portfolio_categories as $category) {
                $selected = ($filter_portfolio_category == $category->name) ? 'selected' : '';
                echo '<option value="' . esc_attr($category->name) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
            }
        } else {
            echo '<option value="">No categories found.</option>';
        }
        ?>
    </select>
    <input type="submit" value="Filter" class="button">
</form>

<?php
    // Debugging output
    echo '<p>Selected Portfolio Category: ' . esc_html($filter_portfolio_category) . '</p>';
    echo '<p>Selected Country: ' . esc_html($filter_country) . '</p>';

    // Query to fetch jobs
    $query = "SELECT * FROM $jobs_table WHERE 1=1";

    if ($status !== null) {
        $query .= " AND status = $status";
    }

    // Add condition for country filter
    if (!empty($filter_country)) {
        $query .= $wpdb->prepare(" AND country = %s", $filter_country);
    }

    // Add condition for portfolio category filter by name
    if (!empty($filter_portfolio_category)) {
        $query .= $wpdb->prepare(" AND portfolio_category = %s", $filter_portfolio_category);
    }

    // Debugging: Output the final query
    echo '<p>SQL Query: ' . esc_html($query) . '</p>';

    $jobs = $wpdb->get_results($query);

    // Debugging: Output the number of jobs found
    echo '<p>Jobs Found: ' . count($jobs) . '</p>';

    if ($jobs) {
?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Location</th>
                    <th>Country</th>
                    <th>Date of Application</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job) { ?>
                    <tr>
                        <td><?php echo esc_html($job->job_title); ?></td>
                        <td><?php echo esc_html($job->location); ?></td>
                        <td><?php echo esc_html($job->country); ?></td>
                        <td><?php echo esc_html($job->date_of_application); ?></td>
                        <td><?php echo $job->status ? 'Open' : 'Closed'; ?></td>
                        <td>
                            <a href="?page=hrjobs&tab=view_job&job_id=<?php echo $job->id; ?>">View</a> |
                            <a href="?page=hrjobs&tab=edit_job&job_id=<?php echo $job->id; ?>">Edit</a> |
                            <a href="?page=hrjobs&tab=all_jobs&action=delete&job_id=<?php echo $job->id; ?>" onclick="return confirm('Are you sure you want to delete this job?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
<?php
    } else {
        echo '<p>No jobs found.</p>';
    }
}

