<?php
/**
 *  Created by Justin Mazzi (http://r00tshell.com)
 *  This script was originally found on (http://www.nocblog.com/software/2006/01/23/migrator-b2evolution-wordpress/)
 *  Modified by TuMahler (http://www.tumahler.com) 4/3/2006
 *  Modified by Nic Jansma (http://www.nicj.net) 3/1/2009
 *
 * Steps:
 *  1) Install Wordpress.  Does not have to use the same DB as your b2evolution blog.
 *  2) Edit the connection info below (username, password, database) for both of your databases
 *  3) Run migrate-b2evolution-to-wordpress.php (this script)
 *  4) Check over your posts, users, categories etc.
 *  5) Enjoy your new Wordpress blog
 *
 * @author    Nic Jansma <nic@nicj.net>
 * @copyright 2012 Nic Jansma
 * @link      http://www.nicj.net
 */
//

//
// *** modify these ***
//
$b2serv     = '';
$b2user     = '';
$b2pass     = '';
$b2database = '';

$wpserv     = '';
$wpuser     = '';
$wppass     = '';
$wpdatabase = '';

// ---------------
// Start migration
//
$b2db = mysql_connect($b2serv, $b2user, $b2pass);
mysql_select_db($b2database, $b2db);

$wpdb = mysql_connect($wpserv, $wpuser, $wppass, true);
mysql_select_db($wpdatabase, $wpdb);

//
// Remove WordPress content
//
print "Removing WordPress Comments\n";
mysql_query('DELETE FROM wp_comments', $wpdb);
mysql_query('ALTER TABLE wp_comments AUTO_INCREMENT=1', $wpdb);

print "Removing WordPress Posts\n";
mysql_query('DELETE FROM wp_posts', $wpdb);
mysql_query('ALTER TABLE wp_posts AUTO_INCREMENT=1', $wpdb);

print "Removing Misc tables\n";
mysql_query('DELETE FROM wp_postmeta', $wpdb);

// delete all term relationships that are not for link_category
mysql_query('DELETE FROM wp_term_relationships WHERE term_taxonomy_id != 2', $wpdb);

//
// Migrate posts
//
print "Migrating Posts\n\n";

$query = mysql_query('  SELECT  ID,
                                post_author,
                                post_issue_date,
                                post_mod_date,
                                post_status,
                                post_content,
                                post_title,
                                post_urltitle,
                                post_category
                        FROM    evo_posts
                        ORDER BY ID ASC', $b2db);


while ($b2Post = mysql_fetch_object($query)) {

    //
    // comment count
    //
    $commentCount = mysql_fetch_object(mysql_query("SELECT count(*) AS commentCount FROM `evo_comments` WHERE comment_post_ID = {$b2Post->ID}", $b2db))->commentCount;

    //
    // WP category ID
    //
    $b2CatName = mysql_fetch_object(mysql_query("SELECT cat_name FROM `evo_categories` WHERE cat_ID = {$b2Post->post_category}", $b2db))->cat_name;
    $wpCatId   = mysql_fetch_object(mysql_query("SELECT term_id FROM `wp_terms` WHERE name = '{$b2CatName}'", $wpdb))->term_id;

    //
    // get WP user ID
    //
    $b2UserNick = mysql_fetch_object(mysql_query("SELECT user_nickname FROM `evo_users` WHERE ID = {$b2Post->post_author}", $b2db))->user_nickname;
    $wpUserId   = mysql_fetch_object(mysql_query("SELECT ID FROM `wp_users` WHERE user_nicename = '$b2UserNick'", $wpdb))->ID;

    //
    // escape all strings
    //
    foreach ($b2Post as $key => $val) {
        $b2Post->$key = mysql_escape_string($val);
    }

    //
    // convert to utf-8
    //
    $b2Post->post_content = mb_convert_encoding($b2Post->post_content, 'UTF-8');

    if (empty($b2Post->post_title)) {
        $b2Post->post_title = $b2Post->post_urltitle;
    }

    //
    // post status
    //
    $wpPostStatus = 'publish';
    if ($b2Post->post_status === 'private') {
        $wpPostStatus = 'private';
    }

    //
    // add post
    //
    $insertQuery = "INSERT INTO wp_posts
                        (ID, post_author, post_date, post_date_gmt, post_modified_gmt, post_modified,
                        post_content, post_title, post_category, post_status, post_name, comment_count)
                    VALUES
                        ('{$b2Post->ID}', '$wpUserId', '{$b2Post->post_issue_date}', '{$b2Post->post_issue_date}',
                        '{$b2Post->post_mod_date}', '{$b2Post->post_mod_date}', '{$b2Post->post_content}',
                        '{$b2Post->post_title}', '$wpCatId', '$wpPostStatus', '{$b2Post->post_urltitle}',
                        '{$commentCount}')";
    mysql_query($insertQuery, $wpdb);
    $wpPostId = mysql_insert_id();

    if (mysql_error($wpdb)) {
        print 'ERROR: ' . mysql_error($wpdb) . "\n";
    }

    //
    // wp post status
    //
    if ($b2Post->post_status === 'private') {

        $timeArray     = explode(' ', microtime());
        $timeSince1970 = $timeArray[1];

        mysql_query("INSERT INTO wp_postmeta
                        (post_id, meta_key, meta_value)
                        VALUES
                        ($wpPostId, '_edit_lock', $timeSince1970)", $wpdb);
        mysql_query("INSERT INTO wp_postmeta
                        (post_id, meta_key, meta_value)
                        VALUES
                        ($wpPostId, '_edit_last', 2)", $wpdb);
    }

    //
    // insert term (category) relationship
    //
    mysql_query("INSERT INTO wp_term_relationships
                        (object_id, term_taxonomy_id)
                        VALUES
                        ($wpPostId, $wpCatId)", $wpdb);

    print "Imported: {$b2Post->post_title} ({$b2Post->post_urltitle})\n";
}

//
// Comments
//
print "Migrating Comments\n\n";

$query = mysql_query('SELECT comment_post_ID,
                             comment_author,
                             comment_author_email,
                             comment_author_url,
                             comment_author_IP,
                             comment_date,
                             comment_content,
                             comment_karma
                        FROM evo_comments', $b2db);

while ($b2comment = mysql_fetch_object($query)) {

    //
    // escape all strings
    //
    foreach ($b2comment as $key => $val) {
        $b2comment->$key = mysql_escape_string($val);
    }

    $b2comment->comment_content = mb_convert_encoding($b2comment->comment_content, 'UTF-8');

    $insertQuery = "INSERT INTO wp_comments
                        (comment_post_ID, comment_author, comment_author_email, comment_author_url,
                        comment_author_IP, comment_date, comment_content, comment_karma)
                    VALUES
                        ('{$b2comment->comment_post_ID}', '{$b2comment->comment_author}',
                        '{$b2comment->comment_author_email}', '{$b2comment->comment_author_url}',
                        '{$b2comment->comment_author_IP}', '{$b2comment->comment_date}',
                        '{$b2comment->comment_content}', '{$b2comment->comment_karma}')";

    mysql_query($insertQuery, $wpdb);
}

print 'Done!';

?>