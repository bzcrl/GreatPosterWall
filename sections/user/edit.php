<?

use Gazelle\Manager\Donation;

$UserID = $_REQUEST['userid'];
if (!is_number($UserID)) {
    error(404);
}

$DB->query("
	SELECT
		m.Username,
		m.Email,
		m.IRCKey,
		m.Paranoia,
		m.2FA_Key,
		i.Info,
		i.Avatar,
		i.StyleID,
		i.StyleURL,
		i.SiteOptions,
		i.UnseededAlerts,
		i.ReportedAlerts,
		i.RequestsAlerts,
		i.DownloadAlt,
		p.Level AS Class,
		i.InfoTitle,
		i.NotifyOnDeleteSeeding,
		i.NotifyOnDeleteSnatched,
		i.NotifyOnDeleteDownloaded,
		i.Lang,
		i.TGID,
		right(m.torrent_pass,8)
	FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		LEFT JOIN permissions AS p ON p.ID = m.PermissionID
	WHERE m.ID = '" . db_string($UserID) . "'");
list($Username, $Email, $IRCKey, $Paranoia, $TwoFAKey, $Info, $Avatar, $StyleID, $StyleURL, $SiteOptions, $UnseededAlerts, $ReportedAlerts, $RequestsAlerts, $DownloadAlt, $Class, $InfoTitle, $NotifyOnDeleteSeeding, $NotifyOnDeleteSnatched, $NotifyOnDeleteDownloaded, $Lang, $TGID, $Right8Passkey) = $DB->next_record(MYSQLI_NUM, array(3, 9));

if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Class)) {
    error(403);
}

$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
    $Paranoia = array();
}

function paranoia_level($Setting) {
    global $Paranoia;
    // 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
    return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName) {
    $Level = paranoia_level($FieldName);
    print "\t\t\t\t\t<input id=\"input-p_{$FieldName}_c\" type=\"checkbox\" name=\"p_{$FieldName}_c\"" . checked($Level >= 1) . " onchange=\"AlterParanoia()\" />\n<label for=\"input-p_{$FieldName}_c\">" . Lang::get('user', 'show_count') . "</label>" . "&nbsp;&nbsp;\n";
    print "\t\t\t\t\t<input id=\"input-p_{$FieldName}_l\" type=\"checkbox\" name=\"p_{$FieldName}_l\"" . checked($Level >= 2) . " onchange=\"AlterParanoia()\" />\n<label for=\"input-p_{$FieldName}_l\">" . Lang::get('user', 'show_list') . "</label>" . "\n";
}

function checked($Checked) {
    return ($Checked ? ' checked="checked"' : '');
}

$SiteOptions = unserialize_array($SiteOptions);
$SiteOptions = array_merge(Users::default_site_options(), $SiteOptions);

View::show_header("$Username &gt; " . Lang::get('user', 'setting'), 'user,jquery-ui,release_sort,password_validate,validate,cssgallery,preview_paranoia,bbcode,user_settings,donor_titles,img_upload');

$donation = new Donation();

$DonorRank = $donation->rank($UserID);
$DonorIsVisible = $donation->isVisible($UserID);

if ($DonorIsVisible === null) {
    $DonorIsVisible = true;
}

extract($donation->enabledRewards($UserID));
$Rewards = $donation->rewards($UserID);
$ProfileRewards = $donation->profileRewards($UserID);
$DonorTitles = $donation->titles($UserID);


echo $Val->GenerateJS('userform');
?>
<div class="thin">
    <div class="header">
        <h2><?= Users::format_username($UserID, false, false, false) ?> &gt; <?= Lang::get('user', 'setting') ?></h2>
    </div>
    <form class="edit_form" name="user" id="userform" action="" method="post" autocomplete="off">
        <div class="grid_container">
            <div class="sidebar settings_sidebar">
                <div class="box box2" id="settings_sections">
                    <div class="head">
                        <strong><?= Lang::get('user', 'menu') ?></strong>
                    </div>
                    <ul class="body nobullet">
                        <li data-gazelle-section-id="all_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_all_title') ?>"><?= Lang::get('user', 'st_all') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="site_appearance_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_style_title') ?>"><?= Lang::get('user', 'st_style') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="site_language_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_language_title') ?>"><?= Lang::get('user', 'st_language') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="torrent_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_torrents_title') ?>"><?= Lang::get('user', 'st_torrents') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="community_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_community_title') ?>"><?= Lang::get('user', 'st_community') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="notification_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_notification_title') ?>"><?= Lang::get('user', 'st_notification') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="personal_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_personal_title') ?>"><?= Lang::get('user', 'st_personal') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="paranoia_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_paranoia_title') ?>"><?= Lang::get('user', 'st_paranoia') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="access_settings">
                            <h2><a href="#" class="tooltip" title="<?= Lang::get('user', 'st_access_title') ?>"><?= Lang::get('user', 'st_access') ?></a></h2>
                        </li>
                        <li data-gazelle-section-id="live_search">
                            <input type="text" id="settings_search" placeholder="<?= Lang::get('user', 'st_search') ?>" />
                        </li>
                        <li>
                            <input type="submit" id="submit" value="<?= Lang::get('user', 'st_save') ?>" />
                        </li>
                    </ul>
                </div>
            </div>
            <div class="main_column">
                <div>
                    <input type="hidden" name="action" value="take_edit" />
                    <input type="hidden" name="userid" value="<?= $UserID ?>" />
                    <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                </div>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="site_appearance_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_style') ?></strong>
                        </td>
                    </tr>
                    <tr id="site_style_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'stylesheet_title') ?>"><strong><?= Lang::get('user', 'style') ?></strong></td>
                        <td>
                            <select name="stylesheet" id="stylesheet">
                                <? foreach ($Stylesheets as $Style) { ?>
                                    <option value="<?= ($Style['ID']) ?>" <?= $Style['ID'] == $StyleID ? ' selected="selected"' : '' ?>><?= ($Style['ProperName']) ?></option>
                                <?  } ?>
                            </select>&nbsp;&nbsp;
                            <a href="#" id="toggle_css_gallery" class="brackets"><?= Lang::get('user', 'gallery') ?></a>
                            <div id="css_gallery">
                                <? foreach ($Stylesheets as $Style) { ?>
                                    <div class="preview_wrapper">
                                        <div class="preview_image" name="<?= ($Style['Name']) ?>">
                                            <a href="<?= STATIC_SERVER . 'stylespreview/full_' . $Style['Name'] . '.png' ?>" target="_blank">
                                                <img src="<?= STATIC_SERVER . 'stylespreview/thumb_' . $Style['Name'] . '.png' ?>" alt="<?= $Style['Name'] ?>" />
                                            </a>
                                        </div>
                                        <p class="preview_name">
                                            <input id="input-stylesheet_gallery" type="radio" name="stylesheet_gallery" value="<?= ($Style['ID']) ?>" />
                                            <label for="input-stylesheet_gallery"> <?= ($Style['ProperName']) ?></label>
                                        </p>
                                    </div>
                                <?  } ?>
                            </div>
                        </td>
                    </tr>
                    <tr id="site_extstyle_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'ex_style_title') ?>"><strong><?= Lang::get('user', 'ex_style') ?></strong></td>
                        <td>
                            <input type="text" size="40" name="styleurl" id="styleurl" value="<?= display_str($StyleURL) ?>" />
                        </td>
                    </tr>
                    <tr id="site_opendyslexic_tr">
                        <td class="label tooltip_interactive" title="<?= Lang::get('user', 'opendyslexic_title') ?>"><strong><?= Lang::get('user', 'opendyslexic') ?></strong></td>
                        <td>
                            <div class="field_div">
                                <input type="checkbox" name="useopendyslexic" id="useopendyslexic" <?= !empty($SiteOptions['UseOpenDyslexic']) ? ' checked="checked"' : '' ?> />
                                <label for="useopendyslexic"><?= Lang::get('user', 'enable_opendyslexic') ?></label>
                            </div>
                            <p class="min_padding"><?= Lang::get('user', 'enable_opendyslexic_title') ?></p>
                        </td>
                    </tr>
                    <tr id="site_tooltips_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'style_tool_title') ?>"><strong><?= Lang::get('user', 'style_tool') ?></strong></td>
                        <td>
                            <input type="checkbox" name="usetooltipster" id="usetooltipster" <?= !isset($SiteOptions['Tooltipster']) || $SiteOptions['Tooltipster'] ? ' checked="checked"' : '' ?> />
                            <label for="usetooltipster"><?= Lang::get('user', 'enabled') ?></label>
                        </td>
                    </tr>
                    <? if (check_perms('users_mod')) { ?>
                        <tr id="site_autostats_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'base_stats_title') ?>"><strong><?= Lang::get('user', 'base_stats') ?></strong></td>
                            <td><input id="input-autoload_comm_stats" type="checkbox" name="autoload_comm_stats" <? Format::selected('AutoloadCommStats', 1, 'checked', $SiteOptions); ?> />
                                <label for="input-autoload_comm_stats"><?= Lang::get('user', 'base_stats_note') ?></label>
                            </td>
                        </tr>
                    <?  } ?>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="site_language_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_language') ?></strong>
                        </td>
                    </tr>
                    <tr id="site_lang_tr">
                        <td class="label tooltip" title=""><strong><?= Lang::get('user', 'st_language_head') ?></strong></td>
                        <td>
                            <select name="language" id="language">
                                <option value="chs" <?= $Lang == 'chs' ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'st_chinese') ?></option>
                                <?
                                if (true) {
                                ?>
                                    <option value="en" <?= $Lang == 'en' ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'st_english') ?></option>
                                <?
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="torrent_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_torrents') ?></strong>
                        </td>
                    </tr>
                    <? if (check_perms('site_advanced_search')) { ?>
                        <tr id="tor_searchtype_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'default_search_title') ?>"><strong><?= Lang::get('user', 'default_search') ?></strong></td>
                            <td>
                                <ul class="options_list nobullet">
                                    <li>
                                        <input type="radio" name="searchtype" id="search_type_simple" value="0" <?= $SiteOptions['SearchType'] == 0 ? ' checked="checked"' : '' ?> />
                                        <label for="search_type_simple"><?= Lang::get('user', 'base') ?></label>
                                    </li>
                                    <li>
                                        <input type="radio" name="searchtype" id="search_type_advanced" value="1" <?= $SiteOptions['SearchType'] == 1 ? ' checked="checked"' : '' ?> />
                                        <label for="search_type_advanced"><?= Lang::get('user', 'advanced') ?></label>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    <?  } ?>
                    <tr id="tor_group_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_group_title') ?>"><strong><?= Lang::get('user', 'torrents_group') ?></strong></td>
                        <td>
                            <div class="option_group">
                                <input type="checkbox" name="disablegrouping" id="disablegrouping" <?= $SiteOptions['DisableGrouping2'] == 0 ? ' checked="checked"' : '' ?> />
                                <label for="disablegrouping"><?= Lang::get('user', 'torrents_group_tool') ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr id="tor_gdisp_search_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_group_display_title') ?>"><strong><?= Lang::get('user', 'torrents_group_display') ?></strong></td>
                        <td>
                            <div class="option_group">
                                <ul class="options_list nobullet">
                                    <li>
                                        <input type="radio" name="torrentgrouping" id="torrent_grouping_open" value="0" <?= $SiteOptions['TorrentGrouping'] == 0 ? ' checked="checked"' : '' ?> />
                                        <label for="torrent_grouping_open"><?= Lang::get('user', 'enabled') ?></label>
                                    </li>
                                    <li>
                                        <input type="radio" name="torrentgrouping" id="torrent_grouping_closed" value="1" <?= $SiteOptions['TorrentGrouping'] == 1 ? ' checked="checked"' : '' ?> />
                                        <label for="torrent_grouping_closed"><?= Lang::get('user', 'disabled') ?></label>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <tr id="tor_gdisp_artist_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_artists_display_title') ?>"><strong><?= Lang::get('user', 'torrents_artists_display') ?></strong></td>
                        <td>
                            <ul class="options_list nobullet">
                                <li>
                                    <input type="radio" name="discogview" id="discog_view_open" value="0" <?= $SiteOptions['DiscogView'] == 0 ? ' checked="checked"' : '' ?> />
                                    <label for="discog_view_open"><?= Lang::get('user', 'enabled') ?></label>
                                </li>
                                <li>
                                    <input type="radio" name="discogview" id="discog_view_closed" value="1" <?= $SiteOptions['DiscogView'] == 1 ? ' checked="checked"' : '' ?> />
                                    <label for="discog_view_closed"><?= Lang::get('user', 'disabled') ?></label>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr id="tor_reltype_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_artists_display_type_title') ?>"><strong><?= Lang::get('user', 'torrents_artists_display_type') ?></strong></td>
                        <td>
                            <a href="#" id="toggle_sortable" class="brackets"><?= Lang::get('user', 'expand') ?></a>
                            <div id="sortable_container" style="display: none;">
                                <a href="#" id="reset_sortable" class="brackets"><?= Lang::get('user', 'reset_to_default') ?></a>
                                <p class="min_padding"><?= Lang::get('user', 'drag_and_drop_change_order') ?></p>
                                <ul class="sortable_list" id="sortable">
                                    <? Users::release_order($SiteOptions) ?>
                                </ul>
                                <script type="text/javascript" id="sortable_default">
                                    //<![CDATA[
                                    var sortable_list_default = <?= Users::release_order_default_js($SiteOptions) ?>;
                                    //]]>
                                </script>
                            </div>
                            <input type="hidden" id="sorthide" name="sorthide" value="" />
                        </td>
                    </tr>
                    <tr id="tor_snatched_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_snatched_title') ?>"><strong><?= Lang::get('user', 'torrents_snatched') ?></strong></td>
                        <td>
                            <input type="checkbox" name="showsnatched" id="showsnatched" <?= !empty($SiteOptions['ShowSnatched']) ? ' checked="checked"' : '' ?> />
                            <label for="showsnatched"><?= Lang::get('user', 'enabled') ?></label>
                        </td>
                    </tr>
                    <!--            <tr>
                <td class="label"><strong>Collage album art view</strong></td>
                <td>
                    <select name="hidecollage" id="hidecollage">
                        <option value="0"<?= $SiteOptions['HideCollage'] == 0 ? ' selected="selected"' : '' ?>>Show album art</option>
                        <option value="1"<?= $SiteOptions['HideCollage'] == 1 ? ' selected="selected"' : '' ?>>Hide album art</option>
                    </select>
                </td>
            </tr>-->
                    <tr id="tor_cover_tor_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'torrents_cover_title') ?>"><strong><?= Lang::get('user', 'torrents_cover') ?></strong></td>
                        <td>
                            <ul class="options_list nobullet">
                                <li>
                                    <input type="hidden" name="coverart" value="" />
                                    <input type="checkbox" name="coverart" id="coverart" <?= $SiteOptions['CoverArt'] ? ' checked="checked"' : '' ?> />
                                    <label for="coverart"><?= Lang::get('user', 'enabled') ?></label>
                                </li>

                            </ul>
                        </td>
                    </tr>

                    <tr id="tor_cover_coll_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'cover_coll_title') ?>"><strong><?= Lang::get('user', 'cover_coll') ?></strong></td>
                        <td>
                            <select name="collagecovers" id="collagecovers">
                                <option value="10" <?= $SiteOptions['CollageCovers'] == 10 ? ' selected="selected"' : '' ?>>10</option>
                                <option value="25" <?= ($SiteOptions['CollageCovers'] == 25 || !isset($SiteOptions['CollageCovers'])) ? ' selected="selected"' : '' ?>>25 (<?= Lang::get('user', 'default') ?>)</option>
                                <option value="50" <?= $SiteOptions['CollageCovers'] == 50 ? ' selected="selected"' : '' ?>>50</option>
                                <option value="100" <?= $SiteOptions['CollageCovers'] == 100 ? ' selected="selected"' : '' ?>>100</option>
                                <option value="1000000" <?= $SiteOptions['CollageCovers'] == 1000000 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'collage_covers_all') ?></option>
                                <option value="0" <?= ($SiteOptions['CollageCovers'] === 0 || (!isset($SiteOptions['CollageCovers']) && $SiteOptions['HideCollage'])) ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'collage_covers_none') ?></option>
                            </select>
                            <?= Lang::get('user', 'cover_coll_number') ?>
                        </td>
                    </tr>
                    <tr id="tor_showfilt_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'filt_tr_title') ?>"><strong><?= Lang::get('user', 'filt_tr') ?></strong></td>
                        <td>
                            <ul class="options_list nobullet">
                                <li>
                                    <input type="checkbox" name="showtfilter" id="showtfilter" <?= (!isset($SiteOptions['ShowTorFilter']) || $SiteOptions['ShowTorFilter'] ? ' checked="checked"' : '') ?> />
                                    <label for="showtfilter"><?= Lang::get('user', 'filt_tr_show') ?></label>
                                </li>
                                <li>
                                    <input type="checkbox" name="showtags" id="showtags" <? Format::selected('ShowTags', 1, 'checked', $SiteOptions); ?> />
                                    <label for="showtags"><?= Lang::get('user', 'filt_tr_show_tags') ?></label>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr id="tor_autocomp_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'autocomp_title') ?>"><strong><?= Lang::get('user', 'autocomp') ?></strong></td>
                        <td>
                            <select name="autocomplete">
                                <option value="0" <?= empty($SiteOptions['AutoComplete']) ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'autocomp_0') ?></option>
                                <option value="2" <?= $SiteOptions['AutoComplete'] === 2 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'autocomp_2') ?></option>
                                <option value="1" <?= $SiteOptions['AutoComplete'] === 1 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'autocomp_1') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="tor_voting_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'voting_title') ?>"><strong><?= Lang::get('user', 'voting') ?></strong></td>
                        <td>
                            <input type="checkbox" name="novotelinks" id="novotelinks" <?= !empty($SiteOptions['NoVoteLinks']) ? ' checked="checked"' : '' ?> />
                            <label for="novotelinks"><?= Lang::get('user', 'voting_disable') ?></label>
                        </td>
                    </tr>
                    <tr id="tor_dltext_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'dltext_title') ?>"><strong><?= Lang::get('user', 'dltext') ?></strong></td>
                        <td>
                            <input type="checkbox" name="downloadalt" id="downloadalt" <?= $DownloadAlt ? ' checked="checked"' : '' ?> />
                            <label for="downloadalt"><?= Lang::get('user', 'dltext_tr') ?></label>
                        </td>
                    </tr>
                    <tr id="tor_https_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'https_title') ?>"><strong><?= Lang::get('user', 'https') ?></strong></td>
                        <td>
                            <input type="checkbox" name="httpstracker" id="httpstracker" <?= $SiteOptions['HttpsTracker'] ? ' checked="checked"' : '' ?> />
                            <label for="httpstracker"><?= Lang::get('user', 'https_note') ?></label>
                        </td>
                    </tr>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="community_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_community') ?></strong>
                        </td>
                    </tr>
                    <tr id="comm_ppp_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'ppp_title') ?>"><strong><?= Lang::get('user', 'ppp') ?></strong></td>
                        <td>
                            <select name="postsperpage" id="postsperpage">
                                <option value="25" <?= $SiteOptions['PostsPerPage'] == 25 ? ' selected="selected"' : '' ?>>25 (<?= Lang::get('user', 'default') ?>)</option>
                                <option value="50" <?= $SiteOptions['PostsPerPage'] == 50 ? ' selected="selected"' : '' ?>>50</option>
                                <option value="100" <?= $SiteOptions['PostsPerPage'] == 100 ? ' selected="selected"' : '' ?>>100</option>
                            </select>
                            <?= Lang::get('user', 'ppp_number') ?>
                        </td>
                    </tr>
                    <tr id="comm_inbsort_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'inbsort_title') ?>"><strong><?= Lang::get('user', 'inbsort') ?></strong></td>
                        <td>
                            <input type="checkbox" name="list_unread_pms_first" id="list_unread_pms_first" <?= !empty($SiteOptions['ListUnreadPMsFirst']) ? ' checked="checked"' : '' ?> />
                            <label for="list_unread_pms_first"><?= Lang::get('user', 'inbsort_un') ?></label>
                        </td>
                    </tr>
                    <tr id="comm_emot_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'emot_title') ?>"><strong><?= Lang::get('user', 'emot') ?></strong></td>
                        <td>
                            <input type="checkbox" name="disablesmileys" id="disablesmileys" <?= !empty($SiteOptions['DisableSmileys']) ? ' checked="checked"' : '' ?> />
                            <label for="disablesmileys"><?= Lang::get('user', 'emot_disable') ?></label>
                        </td>
                    </tr>
                    <tr id="comm_mature_tr">
                        <td class="label tooltip_interactive" title="<?= Lang::get('user', 'mature_title') ?>"><strong><?= Lang::get('user', 'mature') ?></strong></td>
                        <td>
                            <input type="checkbox" name="enablematurecontent" id="enablematurecontent" <?= !empty($SiteOptions['EnableMatureContent']) ? ' checked="checked"' : '' ?> />
                            <label for="enablematurecontent"><?= Lang::get('user', 'mature_show') ?></label>
                        </td>
                    </tr>
                    <tr id="comm_avatars_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'avatars_title') ?>"><strong><?= Lang::get('user', 'avatars') ?></strong></td>
                        <td>
                            <select name="disableavatars" id="disableavatars" onchange="ToggleIdenticons();">
                                <option value="1" <?= $SiteOptions['DisableAvatars'] == 1 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'disabled') ?></option>
                                <option value="0" <?= $SiteOptions['DisableAvatars'] == 0 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'avatars_0') ?></option>
                                <option value="2" <?= $SiteOptions['DisableAvatars'] == 2 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'avatars_2') ?></option>
                                <option value="3" <?= $SiteOptions['DisableAvatars'] == 3 ? ' selected="selected"' : '' ?>><?= Lang::get('user', 'avatars_3') ?></option>
                            </select>
                            <select name="identicons" id="identicons">
                                <option value="0" <?= $SiteOptions['Identicons'] == 0 ? ' selected="selected"' : '' ?>>Identicon</option>
                                <option value="1" <?= $SiteOptions['Identicons'] == 1 ? ' selected="selected"' : '' ?>>MonsterID</option>
                                <option value="2" <?= $SiteOptions['Identicons'] == 2 ? ' selected="selected"' : '' ?>>Wavatar</option>
                                <option value="3" <?= $SiteOptions['Identicons'] == 3 ? ' selected="selected"' : '' ?>>Retro</option>
                                <option value="4" <?= $SiteOptions['Identicons'] == 4 ? ' selected="selected"' : '' ?>>Robots 1</option>
                                <option value="5" <?= $SiteOptions['Identicons'] == 5 ? ' selected="selected"' : '' ?>>Robots 2</option>
                                <option value="6" <?= $SiteOptions['Identicons'] == 6 ? ' selected="selected"' : '' ?>>Robots 3</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="comm_autosave_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'autosave_title') ?>"><strong><?= Lang::get('user', 'autosave') ?></strong></td>
                        <td>
                            <input type="checkbox" name="disableautosave" id="disableautosave" <?= !empty($SiteOptions['DisableAutoSave']) ? ' checked="checked"' : '' ?> />
                            <label for="disableautosave"><?= Lang::get('user', 'disabled') ?></label>
                        </td>
                    </tr>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="notification_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_notification') ?></strong>
                        </td>
                    </tr>
                    <tr id="notif_autosubscribe_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'autosubscribe_title') ?>"><strong><?= Lang::get('user', 'autosubscribe') ?></strong></td>
                        <td>
                            <input type="checkbox" name="autosubscribe" id="autosubscribe" <?= !empty($SiteOptions['AutoSubscribe']) ? ' checked="checked"' : '' ?> />
                            <label for="autosubscribe"><?= Lang::get('user', 'enabled') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_requests_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'autosubscribe_your_request_title') ?>"><strong><?= Lang::get('user', 'autosubscribe_your_request') ?></strong></td>
                        <td>
                            <input type="checkbox" name="requestsalerts" id="requestsalerts" <?= checked($RequestsAlerts) ?> />
                            <label for="requestsalerts"><?= Lang::get('user', 'enabled') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_notifyondeleteseeding_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'notifyondeleteseeding_title') ?>"><strong><?= Lang::get('user', 'notifyondeleteseeding') ?></strong></td>
                        <td>
                            <input type="checkbox" name="notifyondeleteseeding" id="notifyondeleteseeding" <?= !empty($NotifyOnDeleteSeeding) ? ' checked="checked"' : '' ?> />
                            <label for="notifyondeleteseeding"><?= Lang::get('user', 'notifyondeleteseeding_checked') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_notifyondeletesnatched_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'notifyondeletesnatched_title') ?>"><strong><?= Lang::get('user', 'notifyondeletesnatched') ?></strong></td>
                        <td>
                            <input type="checkbox" name="notifyondeletesnatched" id="notifyondeletesnatched" <?= !empty($NotifyOnDeleteSnatched) ? ' checked="checked"' : '' ?> />
                            <label for="notifyondeletesnatched"><?= Lang::get('user', 'notifyondeletesnatched_checked') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_notifyondeletedownloaded_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'notifyondeletedownloaded_title') ?>"><strong><?= Lang::get('user', 'notifyondeletedownloaded') ?></strong></td>
                        <td>
                            <input type="checkbox" name="notifyondeletedownloaded" id="notifyondeletedownloaded" <?= !empty($NotifyOnDeleteDownloaded) ? ' checked="checked"' : '' ?> />
                            <label for="notifyondeletedownloaded"><?= Lang::get('user', 'notifyondeletedownloaded_checked') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_unseeded_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'unseeded_title') ?>"><strong><?= Lang::get('user', 'unseeded') ?></strong></td>
                        <td>
                            <input type="checkbox" name="unseededalerts" id="unseededalerts" <?= checked($UnseededAlerts) ?> />
                            <label for="unseededalerts"><?= Lang::get('user', 'unseeded_checked') ?></label>
                        </td>
                    </tr>
                    <tr id="notif_reported_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'reported_title') ?>"><strong><?= Lang::get('user', 'reported') ?></strong></td>
                        <td>
                            <input type="checkbox" name="reportedalerts" id="reportedalerts" <?= checked($ReportedAlerts) ?> />
                            <label for="reportedalerts"><?= Lang::get('user', 'reported_checked') ?></label>
                        </td>
                    </tr>
                    <? NotificationsManagerView::render_settings(NotificationsManager::get_settings($UserID)); ?>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="personal_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_personal') ?></strong>
                        </td>
                    </tr>
                    <script>
                        function avatar_upload(url) {
                            $("#avatar").val(url)
                        }

                        function avatar_2_upload(url) {
                            $("#second_avatar").val(url)
                        }
                    </script>
                    <tr id="pers_avatar_tr">
                        <td class="label tooltip_interactive" title="<?= Lang::get('user', 'st_avatar_title') ?>"><strong><?= Lang::get('user', 'st_avatar') ?></strong></td>
                        <td>
                            <input type="text" size="50" name="avatar" id="avatar" value="<?= display_str($Avatar) ?>" readonly />
                            <input type="button" onclick="UploadImage(false, avatar_upload)" value="上传">
                        </td>
                    </tr>
                    <? if ($HasSecondAvatar) { ?>
                        <tr id="pers_avatar2_tr">
                            <td class="label tooltip_interactive" title="<?= Lang::get('user', 'st_avatar_2_title') ?>"><strong><?= Lang::get('user', 'st_avatar_2') ?></strong></td>
                            <td>
                                <input type="text" size="50" name="second_avatar" id="second_avatar" value="<?= $Rewards['SecondAvatar'] ?>" readonly />
                                <input type="button" onclick="UploadImage(false, avatar_2_upload)" value="上传">
                            </td>
                        </tr>
                    <?  }
                    if ($HasAvatarMouseOverText) { ?>
                        <tr id="pers_avatarhover_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_avatarhover_title') ?>"><strong><?= Lang::get('user', 'st_avatarhover') ?></strong></td>
                            <td>
                                <input type="text" size="50" name="avatar_mouse_over_text" id="avatar_mouse_over_text" value="<?= $Rewards['AvatarMouseOverText'] ?>" />
                            </td>
                        </tr>
                    <?  }
                    if ($HasDonorIconMouseOverText) { ?>
                        <tr id="pers_donorhover_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_donorhover_title') ?>"><strong><?= Lang::get('user', 'st_donorhover') ?></strong></td>
                            <td>
                                <input type="text" size="50" name="donor_icon_mouse_over_text" id="donor_icon_mouse_over_text" value="<?= $Rewards['IconMouseOverText'] ?>" />
                            </td>
                        </tr>
                    <?  }
                    if ($HasDonorIconLink) { ?>
                        <tr id="pers_donorlink_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_donorlink_title') ?>"><strong><?= Lang::get('user', 'st_donorlink') ?></strong></td>
                            <td>
                                <input type="text" size="50" name="donor_icon_link" id="donor_icon_link" value="<?= $Rewards['CustomIconLink'] ?>" />
                            </td>
                        </tr>
                    <?  }
                    if ($HasCustomDonorIcon) { ?>
                        <tr id="pers_donoricon_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_donoricon_title') ?>"><strong><?= Lang::get('user', 'st_donoricon') ?></strong></td>
                            <td>
                                <input type="text" size="50" name="donor_icon_custom_url" id="donor_icon_custom_url" value="<?= $Rewards['CustomIcon'] ?>" />
                            </td>
                        </tr>
                    <?  }
                    if ($HasDonorForum) { ?>
                        <tr id="pers_donorforum_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_donorforum_title') ?>"><strong><?= Lang::get('user', 'st_donorforum') ?></strong></td>
                            <td>
                                <div class="field_div">
                                    <label>
                                        <strong><?= Lang::get('user', 'donorforum_1') ?>:</strong> <input id="input-donor_title_prefix" type="text" size="30" maxlength="30" name="donor_title_prefix" id="donor_title_prefix" value="<?= $DonorTitles['Prefix'] ?>" /></label>
                                </div>
                                <div class="field_div">
                                    <label for="input-donor_title_suffix"><strong><?= Lang::get('user', 'donorforum_2') ?>:</strong> <input id="input-donor_title_suffix" type="text" size="30" maxlength="30" name="donor_title_suffix" id="donor_title_suffix" value="<?= $DonorTitles['Suffix'] ?>" /></label>
                                </div>
                                <div class="field_div">
                                    <label for="input-donor_title_comma"><strong><?= Lang::get('user', 'donorforum_3') ?>:</strong> <input id="input-donor_title_comma" type="checkbox" id="donor_title_comma" name="donor_title_comma" <?= !$DonorTitles['UseComma'] ? ' checked="checked"' : '' ?> /></label>
                                </div>
                                <strong><?= Lang::get('user', 'donorforum_4') ?>:</strong> <span id="donor_title_prefix_preview"></span><?= $Username ?><span id="donor_title_comma_preview">, </span><span id="donor_title_suffix_preview"></span>
                            </td>
                        </tr>
                    <?  } ?>

                    <tr id="pers_proftitle_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'st_proftitle1_title') ?>"><strong><?= Lang::get('user', 'st_proftitle1') ?></strong></td>
                        <td><input type="text" size="50" name="profile_title" id="profile_title" value="<?= display_str($InfoTitle) ?>" />
                        </td>
                    </tr>
                    <tr id="pers_profinfo_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'st_profinfo1_title') ?>"><strong><?= Lang::get('user', 'st_profinfo1') ?></strong></td>
                        <td><?php $textarea = new TEXTAREA_PREVIEW('info', 'info', display_str($Info), 40, 8); ?></td>
                    </tr>
                    <!-- Excuse this numbering confusion, we start numbering our profile info/titles at 1 in the donor_rewards table -->
                    <? if ($HasProfileInfo1) { ?>
                        <tr id="pers_proftitle2_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_proftitle2_title') ?>"><strong><?= Lang::get('user', 'st_proftitle2') ?></strong></td>
                            <td><input type="text" size="50" name="profile_title_1" id="profile_title_1" value="<?= display_str($ProfileRewards['ProfileInfoTitle1']) ?>" />
                            </td>
                        </tr>
                        <tr id="pers_profinfo2_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_profinfo2_title') ?>"><strong><?= Lang::get('user', 'st_profinfo2') ?></strong></td>
                            <td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_1', 'profile_info_1', display_str($ProfileRewards['ProfileInfo1']), 40, 8); ?></td>
                        </tr>
                    <?  }
                    if ($HasProfileInfo2) { ?>
                        <tr id="pers_proftitle3_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_proftitle3_title') ?>"><strong><?= Lang::get('user', 'st_proftitle3') ?></strong></td>
                            <td><input type="text" size="50" name="profile_title_2" id="profile_title_2" value="<?= display_str($ProfileRewards['ProfileInfoTitle2']) ?>" />
                            </td>
                        </tr>
                        <tr id="pers_profinfo3_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_profinfo3_title') ?>"><strong><?= Lang::get('user', 'st_profinfo3') ?></strong></td>
                            <td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_2', 'profile_info_2', display_str($ProfileRewards['ProfileInfo2']), 40, 8); ?></td>
                        </tr>
                    <?  }
                    if ($HasProfileInfo3) { ?>
                        <tr id="pers_proftitle4_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_proftitle4_title') ?>"><strong><?= Lang::get('user', 'st_proftitle4') ?></strong></td>
                            <td><input type="text" size="50" name="profile_title_3" id="profile_title_3" value="<?= display_str($ProfileRewards['ProfileInfoTitle3']) ?>" />
                            </td>
                        </tr>
                        <tr id="pers_profinfo4_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_profinfo4_title') ?>"><strong><?= Lang::get('user', 'st_profinfo4') ?></strong></td>
                            <td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_3', 'profile_info_3', display_str($ProfileRewards['ProfileInfo3']), 40, 8); ?></td>
                        </tr>
                    <?  }
                    if ($HasProfileInfo4) { ?>
                        <tr id="pers_proftitle5_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_proftitle5_title') ?>"><strong><?= Lang::get('user', 'st_proftitle5') ?></strong></td>
                            <td><input type="text" size="50" name="profile_title_4" id="profile_title_4" value="<?= display_str($ProfileRewards['ProfileInfoTitle4']) ?>" />
                            </td>
                        </tr>
                        <tr id="pers_profinfo5_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_profinfo5_title') ?>"><strong><?= Lang::get('user', 'st_profinfo5') ?></strong></td>
                            <td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_4', 'profile_info_4', display_str($ProfileRewards['ProfileInfo4']), 40, 8); ?></td>
                        </tr>
                    <?  }
                    if ($HasUnlimitedColor) { ?>
                        <tr id="pers_unlimitedcolor_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'unlimitedcolor_title') ?>"><strong><?= Lang::get('user', 'unlimitedcolor') ?></strong></td>
                            <td><input onkeyup="previewColorUsername()" type="text" size="50" name="unlimitedcolor" placeholder="<?= Lang::get('user', 'unlimitedcolor_placeholder') ?>" id="unlimitedcolor" value="<?= display_str($Rewards['ColorUsername']) ?>" />
                            </td>
                        </tr>
                    <?  } else if ($HasLimitedColorName) {
                        $LimitedColors = [
                            "#ed5a65" => Lang::get('user', 'limitedcolor_red'),
                            "#2474b5" => Lang::get('user', 'limitedcolor_blue'),
                            "#428675" => Lang::get('user', 'limitedcolor_green'),
                            "#f2ce2b" => Lang::get('user', 'limitedcolor_yellow'),
                            "#fb8b05" => Lang::get('user', 'limitedcolor_orange'),
                            "#8b2671" => Lang::get('user', 'limitedcolor_purple')
                        ];
                    ?>
                        <tr id="pers_limitedcolorname_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'limitedcolor_title') ?>"><strong><?= Lang::get('user', 'limitedcolor') ?></strong></td>
                            <td>
                                <select name="limitedcolor" id="limitedcolor" onchange="previewColorUsername()">
                                    <?
                                    foreach ($LimitedColors as $LimitedColor => $ColorName) {
                                        echo "<option value=\"$LimitedColor\"" . ($Rewards['ColorUsername'] == $LimitedColor ? ' selected="selected"' : '') . ">$ColorName</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    <?  }
                    if ($HasGradientsColor) { ?>
                        <tr id="pers_gradientscolor_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'gradientscolor_title') ?>"><strong><?= Lang::get('user', 'gradientscolor') ?></strong></td>
                            <td><input onkeyup="previewColorUsername()" type="text" size="50" name="gradientscolor" placeholder="<?= Lang::get('user', 'gradientscolor_placeholder') ?>" id="gradientscolor" value="<?= display_str($Rewards['GradientsColor']) ?>" />
                            </td>
                        </tr>
                    <?  }
                    if ($HasGradientsColor || $HasLimitedColorName || $HasUnlimitedColor) { ?>
                        <tr id="pers_colornamepreview_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'colornamepreview_title') ?>"><strong><?= Lang::get('user', 'colornamepreview') ?></strong></td>
                            <td><a id="preview_color_username" href="user.php?id=<?= $UserID ?>"><?= $Username ?></a></td>
                        </tr>
                    <?  } ?>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="paranoia_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_paranoia') ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">&nbsp;</td>
                        <td>
                            <?= Lang::get('user', 'st_paranoia_note') ?>
                        </td>
                    </tr>
                    <tr id="para_lastseen_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'st_lastseen_title') ?>"><strong><?= Lang::get('user', 'st_lastactivity') ?></strong></td>
                        <td>
                            <input id="input-p_lastseen" type="checkbox" name="p_lastseen" <?= checked(!in_array('lastseen', $Paranoia)) ?> />
                            <label for="input-p_lastseen"> <?= Lang::get('user', 'st_lastseen') ?></label>
                        </td>
                    </tr>
                    <tr id="para_presets_tr">
                        <td class="label"><strong><?= Lang::get('user', 'st_presets') ?></strong></td>
                        <td>
                            <input type="button" onclick="ParanoiaResetOff();" value="<?= Lang::get('user', 'st_presets_0') ?>" />
                            <input type="button" onclick="ParanoiaResetStats();" value="<?= Lang::get('user', 'st_presets_1') ?>" />
                            <input type="button" onclick="ParanoiaResetOn();" value="<?= Lang::get('user', 'st_presets_2') ?>" />
                        </td>
                    </tr>
                    <tr id="para_donations_tr">
                        <td class="label"><strong><?= Lang::get('user', 'st_donations') ?></strong></td>
                        <td>
                            <input type="checkbox" id="p_donor_stats" name="p_donor_stats" onchange="AlterParanoia();" <?= $DonorIsVisible ? ' checked="checked"' : '' ?> />
                            <label for="p_donor_stats"><?= Lang::get('user', 'st_donations_0') ?></label>
                            <input type="checkbox" id="p_donor_heart" name="p_donor_heart" onchange="AlterParanoia();" <?= checked(!in_array('hide_donor_heart', $Paranoia)) ?> />
                            <label for="p_donor_heart"><?= Lang::get('user', 'st_donations_1') ?></label>
                        </td>
                    </tr>
                    <tr id="para_stats_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'para_stats_title') ?>"><strong><?= Lang::get('user', 'para_stats') ?></strong></td>
                        <td>
                            <?
                            $UploadChecked = checked(!in_array('uploaded', $Paranoia));
                            $DownloadChecked = checked(!in_array('downloaded', $Paranoia));
                            $RatioChecked = checked(!in_array('ratio', $Paranoia));
                            $BonusCheched = checked(!in_array('bonuspoints', $Paranoia));
                            ?>
                            <input id="input-p_uploaded" type="checkbox" name="p_uploaded" onchange="AlterParanoia();" <?= $UploadChecked ?> />
                            <label for="input-p_uploaded"> <?= Lang::get('user', 'para_uploaded') ?></label>&nbsp;&nbsp;
                            <input id="input-p_downloaded" type="checkbox" name="p_downloaded" onchange="AlterParanoia();" <?= $DownloadChecked ?> />
                            <label for="input-p_downloaded"> <?= Lang::get('user', 'para_downloaded') ?></label>&nbsp;&nbsp;
                            <input id="input-p_ratio" type="checkbox" name="p_ratio" onchange="AlterParanoia();" <?= $RatioChecked ?> />
                            <label for="input-p_ratio"> <?= Lang::get('user', 'para_ratio') ?></label>&nbsp;&nbsp;
                            <input id="input-p_bonuspoints" type="checkbox" name="p_bonuspoints" <?= $BonusCheched ?> />
                            <label for="input-p_bonuspoints"> <?= Lang::get('user', 'para_bonus') ?></label>
                        </td>
                    </tr>
                    <tr id="para_reqratio_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_reratio') ?></strong></td>
                        <td>
                            <input id="input-p_requiredratio" type="checkbox" name="p_requiredratio" <?= checked(!in_array('requiredratio', $Paranoia)) ?> />
                            <label for="input-p_requiredratio"> <?= Lang::get('user', 'para_reratio') ?></label>
                        </td>
                    </tr>
                    <tr id="para_comments_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_comments') ?></strong></td>
                        <td>
                            <? display_paranoia('torrentcomments'); ?>
                        </td>
                    </tr>
                    <tr id="para_collstart_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_collstart') ?></strong></td>
                        <td>
                            <? display_paranoia('collages'); ?>
                        </td>
                    </tr>
                    <tr id="para_collcontr_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_collcontr') ?></strong></td>
                        <td>
                            <? display_paranoia('collagecontribs'); ?>
                        </td>
                    </tr>
                    <tr id="para_reqfill_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_reqfill') ?></strong></td>
                        <td>
                            <?
                            $RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
                            $RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
                            $RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
                            ?>
                            <input id="input-p_requestsfilled_count" type="checkbox" name="p_requestsfilled_count" onchange="AlterParanoia();" <?= $RequestsFilledCountChecked ?> />
                            <label for="input-p_requestsfilled_count"> <?= Lang::get('user', 'show_count') ?></label>&nbsp;&nbsp;
                            <input id="input-p_requestsfilled_bounty" type="checkbox" name="p_requestsfilled_bounty" onchange="AlterParanoia();" <?= $RequestsFilledBountyChecked ?> />
                            <label for="input-p_requestsfilled_bounty"> <?= Lang::get('user', 'show_bounty') ?></label>&nbsp;&nbsp;
                            <input id="input-p_requestsfilled_list" type="checkbox" name="p_requestsfilled_list" onchange="AlterParanoia();" <?= $RequestsFilledListChecked ?> />
                            <label for="input-p_requestsfilled_list"> <?= Lang::get('user', 'show_list') ?></label>
                        </td>
                    </tr>
                    <tr id="para_reqvote_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_reqvote') ?></strong></td>
                        <td>
                            <?
                            $RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
                            $RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
                            $RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
                            ?>
                            <input id="input-p_requestsvoted_count" type="checkbox" name="p_requestsvoted_count" onchange="AlterParanoia();" <?= $RequestsVotedCountChecked ?> />
                            <label for="input-p_requestsvoted_count"> <?= Lang::get('user', 'show_count') ?></label>&nbsp;&nbsp;
                            <input id="input-p_requestsvoted_bounty" type="checkbox" name="p_requestsvoted_bounty" onchange="AlterParanoia();" <?= $RequestsVotedBountyChecked ?> />
                            <label for="input-p_requestsvoted_bounty"> <?= Lang::get('user', 'show_bounty') ?></label>&nbsp;&nbsp;
                            <input id="input-p_requestsvoted_list" type="checkbox" name="p_requestsvoted_list" onchange="AlterParanoia();" <?= $RequestsVotedListChecked ?> />
                            <label for="input-p_requestsvoted_list"> <?= Lang::get('user', 'show_list') ?></label>
                        </td>
                    </tr>
                    <tr id="para_upltor_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_upltor') ?></strong></td>
                        <td>
                            <? display_paranoia('uploads'); ?>
                        </td>
                    </tr>
                    <tr id="para_original_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_original') ?></strong></td>
                        <td>
                            <? display_paranoia('originals'); ?>
                        </td>
                    </tr>
                    <tr id="para_uplunique_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_uplunique') ?></strong></td>
                        <td>
                            <? display_paranoia('uniquegroups'); ?>
                        </td>
                    </tr>
                    <tr id="para_torseed_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_torseed') ?></strong></td>
                        <td>
                            <? display_paranoia('seeding'); ?>
                        </td>
                    </tr>
                    <tr id="para_torleech_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_torleech') ?></strong></td>
                        <td>
                            <? display_paranoia('leeching'); ?>
                        </td>
                    </tr>
                    <tr id="para_torsnatch_tr">
                        <td class="label"><strong><?= Lang::get('user', 'para_torsnatch') ?></strong></td>
                        <td>
                            <? display_paranoia('snatched'); ?>
                        </td>
                    </tr>
                    <!--
            <tr id="para_torsubscr_tr">
                <td class="label tooltip" title="This option allows other users to subscribe to your torrent uploads."><strong><?= Lang::get('user', 'para_torsubscr') ?></strong></td>
                <td>
                    <input id="input-p_notifications" type="checkbox" name="p_notifications"<?= checked(!in_array('notifications', $Paranoia)) ?> />
                    <label for="input-p_notifications"> <?= Lang::get('user', 'para_torsubscr_note') ?></label>
                </td>
            </tr>
            -->
                    <tr id="para_emailshowtotc_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'para_emailshowtotc_title') ?>"><strong><?= Lang::get('user', 'para_emailshowtotc') ?></strong></td>
                        <td>
                            <input id="input-p_emailshowtotc" type="checkbox" name="p_emailshowtotc" <?= checked(in_array('emailshowtotc', $Paranoia)) ?> />
                            <label for="input-p_emailshowtotc"> <?= Lang::get('user', 'para_emailshowtotc_label') ?></label>
                        </td>
                    </tr>
                    <?
                    $DB->query("
	SELECT COUNT(UserID)
	FROM users_info
	WHERE Inviter = '$UserID'");
                    list($Invited) = $DB->next_record();
                    ?>
                    <tr id="para_invited_tr">
                        <td class="label tooltip" title="This option controls the display of your <?= SITE_NAME ?> invitees."><strong><?= Lang::get('user', 'para_invited') ?></strong></td>
                        <td>
                            <input id="input-p_invitedcount" type="checkbox" name="p_invitedcount" <?= checked(!in_array('invitedcount', $Paranoia)) ?> />
                            <label for="input-p_invitedcount"> <?= Lang::get('user', 'show_count') ?></label>
                        </td>
                    </tr>
                    <?
                    $DB->query("
	SELECT COUNT(ArtistID)
	FROM torrents_artists
	WHERE UserID = $UserID");
                    list($ArtistsAdded) = $DB->next_record();
                    ?>
                    <tr id="para_artistsadded_tr">
                        <td class="label tooltip" title="<?= Lang::get('user', 'para_artistsadded_title') ?>"><strong><?= Lang::get('user', 'para_artistsadded') ?></strong></td>
                        <td>
                            <input id="input-p_artistsadded" type="checkbox" name="p_artistsadded" <?= checked(!in_array('artistsadded', $Paranoia)) ?> />
                            <label for="input-p_artistsadded"> <?= Lang::get('user', 'show_count') ?></label>
                        </td>
                    </tr>
                    <?
                    if (ENABLE_BADGE) {
                    ?>
                        <tr id="para_badgedisplay_tr">
                            <td class="label tooltip" title="para_badgedisplay_title"><strong><?= Lang::get('user', 'para_badgedisplay') ?></strong></td>
                            <td>
                                <input id="input-p_badgedisplay" type="checkbox" name="p_badgedisplay" <?= checked(!in_array('badgedisplay', $Paranoia)) ?> />
                                <label for="input-p_badgedisplay"> <?= Lang::get('user', 'para_badgedisplay_label') ?></label>
                            </td>
                        </tr>
                    <?
                    }
                    ?>
                    <tr id="para_preview_tr">
                        <td></td>
                        <td><a href="#" id="preview_paranoia" class="brackets"><?= Lang::get('user', 'para_preview') ?></a></td>
                    </tr>
                </table>
                <table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="access_settings">
                    <tr class="colhead_dark">
                        <td colspan="2">
                            <strong><?= Lang::get('user', 'st_access') ?></strong>
                        </td>
                    </tr>
                    <tr id="acc_resetpk_tr">
                        <td class="label tooltip_interactive" title="<?= Lang::get('user', 'resetpk_title') ?>"><strong><?= Lang::get('user', 'resetpk') ?></strong></td>
                        <td>
                            <div class="field_div">
                                <input id="input-resetpasskey" type="checkbox" name="resetpasskey" id="resetpasskey" />
                                <label for="input-resetpasskey"><?= Lang::get('user', 'resetpk_note') ?></label>
                            </div>

                        </td>
                    </tr>
                    <tr id="acc_irckey_tr">
                        <td class="label"><strong><?= Lang::get('user', 'irckey') ?></strong></td>
                        <td>
                            <div class="field_div">
                                <input type="text" size="50" name="irckey" id="irckey" value="<?= display_str($IRCKey) ?>" />
                                <input type="button" onclick="RandomIRCKey();" value="<?= Lang::get('user', 'irckey_title') ?>" />
                            </div>
                            <?= Lang::get('user', 'irckey_note_1') ?> <?= BOT_NICK ?> <?= Lang::get('user', 'irckey_note_2') ?>
                        </td>
                    </tr>
                    <tr id="acc_tg_tr">
                        <td class="label"><strong><?= Lang::get('user', 'tg_binding') ?></strong></td>
                        <td>
                            <div class="field_div">
                                <span><?= Lang::get('user', 'tg_binding_span') ?></span>
                                <ul>
                                    <li><?= Lang::get('user', 'tg_binding_key') ?><code><?= $Right8Passkey ?></code><?= Lang::get('user', 'tg_binding_right8') ?></li>
                                    <li><?= Lang::get('user', 'tg_binding_status') ?><span id="tg_bind"><?= $TGID ? Lang::get('user', 'tg_binding_binded') : Lang::get('user', 'tg_binding_unbind') ?></span> <input id="tg_unbind_button" type="button" onclick="Unbind_tg(<?= $UserID ?>);" value="解绑" style="<?= $TGID ? "" : "display: none;" ?>" /></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?
                    if (check_perms('users_edit_profiles', $Class)) {
                    ?>
                        <tr id="acc_email_tr">
                            <td class="label tooltip" title="<?= Lang::get('user', 'st_email_title') ?>"><strong><?= Lang::get('user', 'st_email') ?></strong></td>
                            <td>
                                <div class="field_div">
                                    <input type="email" size="50" name="email" id="email" value="<?= display_str($Email) ?>" />
                                </div>
                                <p class="min_padding"><?= Lang::get('user', 'st_email_note') ?></p>
                            </td>
                        </tr>
                    <?
                    } else {
                    ?>
                        <input type="hidden" name="email" id="email" value="<?= display_str($Email) ?>" />
                    <?
                    }
                    ?>
                    <tr id="acc_password_tr">
                        <td class="label"><strong><?= Lang::get('user', 'st_password') ?></strong></td>
                        <td>
                            <div class="field_div">
                                <label><?= Lang::get('user', 'st_password_old') ?>:
                                    <!-- <br /> -->
                                    <input type="password" size="40" name="cur_pass" id="cur_pass" value="" />
                                </label>
                            </div>
                            <div class="field_div">
                                <label><?= Lang::get('user', 'st_password_new') ?>:
                                    <!-- <br /> -->
                                    <input type="password" size="40" name="new_pass_1" id="new_pass_1" value="" /> <strong id="pass_strength"></strong>
                                </label>
                            </div>
                            <div class="field_div">
                                <label><?= Lang::get('user', 'st_password_re') ?>:
                                    <!-- <br /> -->
                                    <input type="password" size="40" name="new_pass_2" id="new_pass_2" value="" /> <strong id="pass_match"></strong>
                                </label>
                            </div>
                            <div class="setting_description">
                                <?= Lang::get('user', 'st_password_note') ?>
                            </div>
                        </td>
                    </tr>

                    <tr id="acc_2fa_tr">
                        <td class="label"><strong><?= Lang::get('user', '2fa') ?></strong></td>
                        <td>
                            <?= Lang::get('user', 'st_2fa_note1') ?> <strong class="<?= $TwoFAKey ? 'r99' : 'warning'; ?>"><?= $TwoFAKey ? Lang::get('user', 'st_2fa_enabled') : Lang::get('user', 'st_2fa_disabled'); ?></strong> <?= Lang::get('user', 'st_2fa_period') ?><a href="user.php?action=2fa&do=<?= $TwoFAKey ? 'disable' : 'enable'; ?>&userid=<?= G::$LoggedUser['ID'] ?>"><?= Lang::get('user', 'st_2fa_note3') . ($TwoFAKey ? Lang::get('user', 'st_2fa_disable') : Lang::get('user', 'st_2fa_enable')) . Lang::get('user', 'st_2fa_after') ?></a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </form>
</div>
<? View::show_footer(); ?>