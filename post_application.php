<?php
ob_start(); // Start output buffering
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$jobs_table = $wpdb->prefix . 'jobs'; 
$jobs__application_table = $wpdb->prefix . 'job_applications';

// Handle single deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['application_id'])) {
    $application_id = intval($_GET['application_id']);
    $wpdb->delete($jobs__application_table, ['id' => $application_id]);
    wp_redirect(admin_url('admin.php?page=hrjobs&tab=applications'));
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify nonce
    if (!isset($_POST['hrjobs_nonce']) || !wp_verify_nonce($_POST['hrjobs_nonce'], 'bulk_applications')) {
        wp_die(__('Security check failed.', 'hrjobs'));
    }

    // Process bulk actions
    $bulk_action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($bulk_action === 'delete') {
        $application_ids = isset($_POST['application_ids']) ? array_map('intval', $_POST['application_ids']) : [];
        if (!empty($application_ids)) {
            foreach ($application_ids as $id) {
                $wpdb->delete($jobs__application_table, ['id' => $id]);
            }
            wp_redirect(admin_url('admin.php?page=hrjobs&tab=applications'));
            exit;
        }
    }
}

// Output HTML
?>
<h2><?php esc_html_e('Posted Applications', 'hrjobs'); ?></h2>

<div class="tab-content">
    <?php hrjobs_list_jobs($wpdb, $jobs__application_table); ?>
</div>

<?php
function hrjobs_list_jobs($wpdb, $jobs__application_table) {
    $selected_country = isset($_POST['filter_country']) ? sanitize_text_field($_POST['filter_country']) : '';
    $jobs_table = $wpdb->prefix . 'jobs';
    ?>
    <form method="post">
        <?php wp_nonce_field('bulk_applications', 'hrjobs_nonce'); ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="filter_country"><?php _e('Filter by Country:', 'hrjobs'); ?></label>
                <select name="filter_country" id="filter_country">
                    <option value=""><?php _e('All Countries', 'hrjobs'); ?></option>
                    <option value="kuwait" <?php selected($selected_country, 'kuwait'); ?>><?php _e('Kuwait', 'hrjobs'); ?></option>
                    <option value="qatar" <?php selected($selected_country, 'qatar'); ?>><?php _e('Qatar', 'hrjobs'); ?></option>
                    <option value="oman" <?php selected($selected_country, 'oman'); ?>><?php _e('Oman', 'hrjobs'); ?></option>
                    <option value="uae" <?php selected($selected_country, 'uae'); ?>><?php _e('UAE', 'hrjobs'); ?></option>
                </select>
                <input type="submit" name="filter_action" value="<?php _e('Filter', 'hrjobs'); ?>" class="button">
            </div>
            
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'hrjobs'); ?></option>
                    <option value="delete"><?php _e('Delete', 'hrjobs'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'hrjobs'); ?>">
            </div>
        </div>

        <table class="widefat">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="select_all"></th>
                    <th><?php _e('Job Title', 'hrjobs'); ?></th>
                    <th><?php _e('First Name', 'hrjobs'); ?></th>
                    <th><?php _e('Last Name', 'hrjobs'); ?></th>
                    <th><?php _e('Email', 'hrjobs'); ?></th>
                    <th><?php _e('Phone', 'hrjobs'); ?></th>
                    <th><?php _e('Birthday', 'hrjobs'); ?></th>
                    <th><?php _e('Applied On', 'hrjobs'); ?></th>
                    <th><?php _e('Document', 'hrjobs'); ?></th>
                    <th><?php _e('Action', 'hrjobs'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = $wpdb->prepare(
                    "SELECT a.*, j.job_title 
                    FROM $jobs__application_table AS a
                    LEFT JOIN $jobs_table AS j 
                    ON a.job_id = j.id
                    WHERE %s = '' OR j.country = %s",
                    $selected_country,
                    $selected_country
                );
                $applications = $wpdb->get_results($query);
                echo '<p><strong>Total Applications Found:</strong> ' . count($applications) . '</p>' ;
                if (!empty($applications)) : 
                    foreach ($applications as $application) : ?>
                        <tr>
                            <td><input type="checkbox" name="application_ids[]" value="<?php echo esc_attr($application->id); ?>"></td>
                            <td><?php echo esc_html($application->job_title ?? __('N/A', 'hrjobs')); ?></td>
                            <td><?php echo esc_html($application->first_name ?? ''); ?></td>
                            <td><?php echo esc_html($application->last_name ?? ''); ?></td>
                            <td><?php echo esc_html($application->email ?? ''); ?></td>
                            <td><?php echo esc_html($application->phone ?? ''); ?></td>
                            <td><?php echo esc_html($application->birthday ?? ''); ?></td>
                            <td><?php echo esc_html($application->date_applied ?? ''); ?></td>
                            <td>
                                <?php if (!empty($application->file)) : ?>
                                    <a href="<?php echo esc_url($application->file); ?>" target="_blank">
                                        <?php _e('Download', 'hrjobs'); ?>
                                    </a>
                                <?php else : ?>
                                    <?php _e('No file', 'hrjobs'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=hrjobs&tab=applications&action=delete&application_id=<?php echo esc_attr($application->id); ?>" 
                                   onclick="return confirm('<?php _e('Are you sure?', 'hrjobs'); ?>')">
                                    <?php _e('Delete', 'hrjobs'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; 
                else : ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">
                            <?php _e('No applications found.', 'hrjobs'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    
    <script>
        document.getElementById('select_all').addEventListener('change', function(e) {
            var checkboxes = document.querySelectorAll('input[name="application_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = e.target.checked;
            });
        });
    </script>
    <?php
}
ob_end_flush();
?>