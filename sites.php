<?php

global $wpdb;

if (isset($_POST['inputAddNewSiteName']) && !empty($_POST['inputAddNewSiteName'])) {
    $site_name = $_POST['inputAddNewSiteName'];
    $site_url = $_POST['inputAddNewSiteUrl'];
    $sql = "insert into $wpdb->prefix" . "sites (site_name, site_url) values (\"$site_name\",\"$site_url\")";
    $res = $wpdb->get_results($sql);
    echo 'Added ' . $site_name . ' - ' . $site_url;
}


if (isset($_POST['inputSiteId']) && !empty($_POST['inputSiteId'])) {
    foreach ($_POST['inputSiteId'] as $key => $value) {
        $site_id = $_POST['inputSiteId'][$key];
        $site_name = $_POST['inputSiteName'][$key];
        $site_url = $_POST['inputSiteUrl'][$key];
        $sql = "update $wpdb->prefix" . "sites set site_name=\"$site_name\", site_url=\"$site_url\" where site_id=$site_id";
        $res = $wpdb->get_results($sql);
    }
    echo 'Updated';

}

if (isset($_GET['del'])) {
    $del = $_GET['del'];
    $sql = 'delete from '. $wpdb->prefix . 'sites where site_id = "'. $del . '"';
    $res = $wpdb->get_results($sql);
}

$sql = 'SELECT site_id, site_name, site_url FROM ' . $wpdb->prefix . 'sites';
$rows = $wpdb->get_results($sql);
?>

<h1>Sites</h1>

<form method="POST" name="formSites">
    <?php 
        $site_count = 0;
        foreach ($rows as $row) {   ?>
            <input type="hidden" name="inputSiteId[]" value="<?php echo $row->site_id; ?>">
            <input type="text" size="40" name="inputSiteName[]" value="<?php echo $row->site_name; ?>" />
            <input type="text" size="60" name="inputSiteUrl[]" value="<?php echo $row->site_url; ?>" />
            <a href="?page=avss%2Fsites.php&del=<?php echo $row->site_id; ?>" onclick='return confirm("Delete?")''>Delete</a><br />
    <?php 
            $site_count++;
        }
    
        if ($site_count > 0) {
            echo '<input type="submit" value="Save changes" />';
        } else {
            echo 'Site list is empty';
        }
    ?>
</form>

<h1> Add new site </h1>
<p />
<form method="POST" name="formAddNewSite">
    <input name="inputAddNewSiteName" size="40" placeholder="Site name" type="text" required />
    <input name="inputAddNewSiteUrl" size="60" placeholder="Site URL" type="text" required />
    <input type="submit" name="btnAddNewSite" value="OK" />
</form>

