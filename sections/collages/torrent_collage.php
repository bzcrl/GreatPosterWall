<?php
function compare($X, $Y) {
    return ($Y['count'] - $X['count']);
}

// Build the data for the collage and the torrent list
// TODO: Cache this
$DB->query("
	SELECT
		ct.GroupID,
		ct.UserID
	FROM collages_torrents AS ct
		JOIN torrents_group AS tg ON tg.ID = ct.GroupID
	WHERE ct.CollageID = '$CollageID'
	ORDER BY ct.Sort");

$GroupIDs = $DB->collect('GroupID');
$Contributors = $DB->to_pair('GroupID', 'UserID', false);
if (count($GroupIDs) > 0) {
    $TorrentList = Torrents::get_groups($GroupIDs);
    $UserVotes = Votes::get_user_votes($LoggedUser['ID']);
} else {
    $TorrentList = array();
}

$NumGroups = count($TorrentList);
$NumGroupsByUser = 0;
$TopArtists = array();
$UserAdditions = array();
$Number = 0;

// We loop through all groups building some basic statistics for them
// for the header of the collage page, and then we have to build the
// HTML inline instead of doing it all up here. Yeah, it's more complicated
// but the memory savings are a lot
foreach ($GroupIDs as $Idx => $GroupID) {
    if (!isset($TorrentList[$GroupID])) {
        unset($GroupIDs[$Idx]);
        continue;
    }
    $Group = $TorrentList[$GroupID];
    extract(Torrents::array_group($Group));
    $UserID = $Contributors[$GroupID];
    new Tags($TagList);

    // Handle stats and stuff
    $Number++;
    if ($UserID == $LoggedUser['ID']) {
        $NumGroupsByUser++;
    }

    if (
        !empty($ExtendedArtists[1])
        || !empty($ExtendedArtists[4])
        || !empty($ExtendedArtists[5])
        || !empty($ExtendedArtists[6])
    ) {
        $CountArtists = array_merge((array)$ExtendedArtists[1], (array)$ExtendedArtists[4], (array)$ExtendedArtists[5], (array)$ExtendedArtists[6]);
    } else {
        $CountArtists = $GroupArtists;
    }

    if ($CountArtists) {
        foreach ($CountArtists as $Artist) {
            if (!isset($TopArtists[$Artist['id']])) {
                $TopArtists[$Artist['id']] = array('name' => $Artist['name'], 'count' => 1);
            } else {
                $TopArtists[$Artist['id']]['count']++;
            }
        }
    }

    if (!isset($UserAdditions[$UserID])) {
        $UserAdditions[$UserID] = 0;
    }
    $UserAdditions[$UserID]++;
}

// Re-index the array so we can abuse that later to slice parts out of it
$GroupIDs = array_values($GroupIDs);

if ($CollageCategoryID === '0' && !check_perms('site_collages_delete')) {
    if (!check_perms('site_collages_personal') || $CreatorID !== $LoggedUser['ID']) {
        $PreventAdditions = true;
    }
}

if (
    !check_perms('site_collages_delete')
    && ($Locked
        || ($MaxGroups > 0 && $NumGroups >= $MaxGroups)
        || ($MaxGroupsPerUser > 0 && $NumGroupsByUser >= $MaxGroupsPerUser))
) {
    $PreventAdditions = true;
}

// Silly hack for people who are on the old setting
$CollageCovers = isset($LoggedUser['CollageCovers']) ? $LoggedUser['CollageCovers'] : 25 * (abs($LoggedUser['HideCollage'] - 1));

View::show_header($Name, 'browse,collage,bbcode,voting,recommend');
?>
<div class="thin">
    <div class="header">
        <h2><?= $Name ?></h2>
        <div class="linkbox">
            <a href="collages.php" class="brackets">
                <?= Lang::get('collages', 'collages_list') ?></a>
            <? if (check_perms('site_collages_create')) { ?>
                <a href="collages.php?action=new" class="brackets">
                    <?= Lang::get('collages', 'create_collages') ?></a>
            <?  } ?>
            <br /><br />
            <? if (check_perms('site_collages_subscribe')) { ?>
                <a href="#" id="subscribelink<?= $CollageID ?>" class="brackets" onclick="CollageSubscribe(<?= $CollageID ?>); return false;"><?= (in_array($CollageID, $CollageSubscriptions) ? Lang::get('collages', 'unsubscribe') : Lang::get('collages', 'subscribe')) ?></a>
            <?
            }
            if (check_perms('site_collages_delete') || (check_perms('site_edit_wiki') && !$Locked)) {
            ?>
                <a href="collages.php?action=edit&amp;collageid=<?= $CollageID ?>" class="brackets">
                    <?= Lang::get('collages', 'edit_description') ?></a>
            <?  } else { ?>
                <span class="brackets"><?= Lang::get('collages', 'locked') ?></span>
            <?
            }
            if (Bookmarks::has_bookmarked('collage', $CollageID)) {
            ?>
                <a href="#" id="bookmarklink_collage_<?= $CollageID ?>" class="brackets" onclick="Unbookmark('collage', <?= $CollageID ?>, 'Bookmark'); return false;">
                    <?= Lang::get('global', 'remove_bookmark') ?></a>
            <?  } else { ?>
                <a href="#" id="bookmarklink_collage_<?= $CollageID ?>" class="brackets" onclick="Bookmark('collage', <?= $CollageID ?>, 'Remove bookmark'); return false;">
                    <?= Lang::get('global', 'add_bookmark') ?></a>
            <?  } ?>
            <!-- <a href="#" id="recommend" class="brackets">Recommend</a> -->
            <?
            if (check_perms('site_collages_manage') && !$Locked) {
            ?>
                <a href="collages.php?action=manage&amp;collageid=<?= $CollageID ?>" class="brackets">
                    <?= Lang::get('collages', 'manage_torrents') ?></a>
            <?  } ?>
            <a href="reports.php?action=report&amp;type=collage&amp;id=<?= $CollageID ?>" class="brackets">
                <?= Lang::get('collages', 'report_collage') ?></a>
            <? if (check_perms('site_collages_delete') || $CreatorID == $LoggedUser['ID']) { ?>
                <a href="collages.php?action=delete&amp;collageid=<?= $CollageID ?>&amp;auth=<?= $LoggedUser['AuthKey'] ?>" class="brackets" onclick="return confirm('<?= Lang::get('collages', 'delete_confirm') ?>');">
                    <?= Lang::get('global', 'delete') ?></a>
            <?  } ?>
        </div>
    </div>
    <? /* Misc::display_recommend($CollageID, "collage"); */ ?>
    <div class="grid_container">
        <div class="sidebar">
            <div class="box box_category">
                <div class="head"><strong>
                        <?= Lang::get('collages', 'category') ?></strong></div>
                <div class="pad"><a href="collages.php?action=search&amp;cats[<?= (int)$CollageCategoryID ?>]=1"><?= $CollageCats[(int)$CollageCategoryID] ?></a>
                </div>
            </div>
            <div class="box box_description">
                <div class="head"><strong>
                        <?= Lang::get('collages', 'description') ?></strong></div>
                <div class="pad"><?= Text::full_format($Description) ?></div>
            </div>
            <?
            if (check_perms('zip_downloader')) {
                if (isset($LoggedUser['Collector'])) {
                    list($ZIPList, $ZIPPrefs) = $LoggedUser['Collector'];
                    $ZIPList = explode(':', $ZIPList);
                } else {
                    $ZIPList = array('00', '11');
                    $ZIPPrefs = 1;
                }
            ?>
                <div class="box box_zipdownload">
                    <div class="head colhead_dark"><strong>
                            <?= Lang::get('collages', 'collector') ?></strong></div>
                    <div class="pad">
                        <form class="download_form" name="zip" action="collages.php" method="post">
                            <input type="hidden" name="action" value="download" />
                            <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                            <input type="hidden" name="collageid" value="<?= $CollageID ?>" />
                            <ul id="list" class="nobullet">
                                <? foreach ($ZIPList as $ListItem) { ?>
                                    <li id="list<?= $ListItem ?>">
                                        <input type="hidden" name="list[]" value="<?= $ListItem ?>" />
                                        <span class="float_left"><?= $ZIPOptions[$ListItem]['2'] ?></span>
                                        <span class="remove remove_collector"><a href="#" onclick="remove_selection('<?= $ListItem ?>'); return false;" class="float_right brackets">X</a></span>
                                        <br style="clear: both;" />
                                    </li>
                                <? } ?>
                            </ul>
                            <select id="formats" style="width: 180px;">
                                <?
                                $OpenGroup = false;
                                $LastGroupID = -1;

                                foreach ($ZIPOptions as $Option) {
                                    list($GroupID, $OptionID, $OptName) = $Option;

                                    if ($GroupID != $LastGroupID) {
                                        $LastGroupID = $GroupID;
                                        if ($OpenGroup) {
                                ?>
                                            </optgroup>
                                        <?      } ?>
                                        <optgroup label="<?= $ZIPGroups[$GroupID] ?>">
                                        <?
                                        $OpenGroup = true;
                                    }
                                        ?>
                                        <option id="opt<?= $GroupID . $OptionID ?>" value="<?= $GroupID . $OptionID ?>" <? if (in_array($GroupID . $OptionID, $ZIPList)) {
                                                                                                                            echo ' disabled="disabled"';
                                                                                                                        } ?>><?= $OptName ?></option>
                                    <?
                                }
                                    ?>
                                        </optgroup>
                            </select>
                            <button type="button" onclick="add_selection();">+</button>
                            <select name="preference">
                                <option value="0" <? if ($ZIPPrefs == 0) {
                                                        echo ' selected="selected"';
                                                    } ?>><?= Lang::get('collages', 'prefer_original') ?>
                                </option>
                                <option value="1" <? if ($ZIPPrefs == 1) {
                                                        echo ' selected="selected"';
                                                    } ?>><?= Lang::get('collages', 'prefer_best_seeded') ?>
                                </option>
                                <option value="2" <? if ($ZIPPrefs == 2) {
                                                        echo ' selected="selected"';
                                                    } ?>><?= Lang::get('collages', 'prefer_bonus_tracks') ?>
                                </option>
                            </select>
                            <input type="submit" value="↓" />
                        </form>
                    </div>
                </div>
            <? } ?>
            <div class="box box_info box_statistics_collage_torrents">
                <div class="head"><strong>
                        <?= Lang::get('collages', 'statistics') ?></strong></div>
                <ul class="stats nobullet">
                    <li>
                        <?= Lang::get('global', 'torrents') ?>: <?= number_format($NumGroups) ?></li>
                    <? if (!empty($TopArtists)) { ?>
                        <li>
                            <?= Lang::get('global', 'artists') ?>: <?= number_format(count($TopArtists)) ?></li>
                    <? } ?>
                    <li>
                        <?= Lang::get('collages', 'subscribers') ?>: <?= number_format((int)$Subscribers) ?></li>
                    <li>
                        <?= Lang::get('collages', 'built_by') ?> <?= number_format(count($UserAdditions)) ?>
                        <?= Lang::get('collages', 'user') ?><?= (count($UserAdditions) > 1 ? Lang::get('collages', 'users') : '') ?></li>
                    <li>
                        <?= Lang::get('collages', 'last_updated') ?>: <?= time_diff($Updated) ?></li>
                </ul>
            </div>
            <div class="box box_tags">
                <div class="head"><strong>
                        <?= Lang::get('collages', 'top_tags') ?></strong></div>
                <div class="pad">
                    <ol>
                        <?
                        Tags::format_top(5, 'collages.php?action=search&amp;tags=');
                        ?>
                    </ol>
                </div>
            </div>
            <? if (!empty($TopArtists)) { ?>
                <div class="box box_artists">
                    <div class="head"><strong>
                            <?= Lang::get('collages', 'top_artists') ?></strong></div>
                    <div class="pad">
                        <ol>
                            <?
                            uasort($TopArtists, 'compare');
                            $i = 0;
                            foreach ($TopArtists as $ID => $Artist) {
                                $i++;
                                if ($i > 10) {
                                    break;
                                }
                            ?>
                                <li><a href="artist.php?id=<?= $ID ?>"><?= $Artist['name'] ?></a> (<?= number_format($Artist['count']) ?>)
                                </li>
                            <?
                            }
                            ?>
                        </ol>
                    </div>
                </div>
            <?  } ?>
            <div class="box box_contributors">
                <div class="head"><strong>
                        <?= Lang::get('collages', 'top_contributors') ?></strong></div>
                <div class="pad">
                    <ol>
                        <?
                        arsort($UserAdditions);
                        $i = 0;
                        foreach ($UserAdditions as $UserID => $Additions) {
                            $i++;
                            if ($i > 5) {
                                break;
                            }
                        ?>
                            <li><?= Users::format_username($UserID, false, false, false) ?> (<?= number_format($Additions) ?>)</li>
                        <?
                        }
                        ?>
                    </ol>
                </div>
            </div>
            <? if (check_perms('site_collages_manage') && !isset($PreventAdditions)) { ?>
                <div class="box box_addtorrent">
                    <div class="head"><strong>
                            <?= Lang::get('collages', 'add_torrent_group') ?></strong><span class="float_right"><a href="#" onclick="$('.add_torrent_container').toggle_class('hidden'); this.innerHTML = (this.innerHTML == 'Batch add' ? 'Individual add' : 'Batch add'); return false;" class="brackets">
                                <?= Lang::get('collages', 'batch_add') ?></a></span></div>
                    <div class="pad add_torrent_container">
                        <form class="add_form" name="torrent" action="collages.php" method="post">
                            <input type="hidden" name="action" value="add_torrent" />
                            <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                            <input type="hidden" name="collageid" value="<?= $CollageID ?>" />
                            <div class="field_div">
                                <input type="text" size="20" name="url" />
                            </div>
                            <div class="submit_div">
                                <input type="submit" value="Add" />
                            </div>
                            <span style="font-style: italic;">
                                <?= Lang::get('collages', 'add_torrent_group_note1') ?></span>
                        </form>
                    </div>
                    <div class="pad hidden add_torrent_container">
                        <form class="add_form" name="torrents" action="collages.php" method="post">
                            <input type="hidden" name="action" value="add_torrent_batch" />
                            <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                            <input type="hidden" name="collageid" value="<?= $CollageID ?>" />
                            <div class="field_div">
                                <textarea name="urls" rows="5" cols="25" style="white-space: pre; word-wrap: normal; overflow: auto;"></textarea>
                            </div>
                            <div class="submit_div">
                                <input type="submit" value="Add" />
                            </div>
                            <span style="font-style: italic;">
                                <?= Lang::get('collages', 'add_torrent_group_note2') ?></span>
                        </form>
                    </div>
                </div>
            <? } ?>
            <h3><?= Lang::get('collages', 'comments') ?></h3>
            <?
            if ($CommentList === null) {
                $DB->query("
		SELECT
			c.ID,
			c.Body,
			c.AuthorID,
			um.Username,
			c.AddedTime
		FROM comments AS c
			LEFT JOIN users_main AS um ON um.ID = c.AuthorID
		WHERE c.Page = 'collages'
			AND c.PageID = $CollageID
		ORDER BY c.ID DESC
		LIMIT 15");
                $CommentList = $DB->to_array(false, MYSQLI_NUM);
            }
            foreach ($CommentList as $Comment) {
                list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment;
            ?>
                <div class="box comment">
                    <div class="head">
                        <?= Users::format_username($UserID, false, false, false) ?> <?= time_diff($CommentTime) ?>
                        <br />
                        <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?= $CommentID ?>" class="brackets"><?= Lang::get('collages', 'report') ?></a>
                    </div>
                    <div class="pad"><?= Text::full_format($Body) ?></div>
                </div>
            <?
            }
            ?>
            <div class="box pad">
                <a href="collages.php?action=comments&amp;collageid=<?= $CollageID ?>" class="brackets">
                    <?= Lang::get('collages', 'view_all_comments') ?></a>
            </div>
            <?
            if (!$LoggedUser['DisablePosting']) {
            ?>
                <div class="box box_addcomment">
                    <div class="head"><strong>
                            <?= Lang::get('collages', 'add_comment') ?></strong></div>
                    <form class="send_form" name="comment" id="quickpostform" onsubmit="quickpostform.submit_button.disabled = true;" action="comments.php" method="post">
                        <input type="hidden" name="action" value="take_post" />
                        <input type="hidden" name="page" value="collages" />
                        <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                        <input type="hidden" name="pageid" value="<?= $CollageID ?>" />
                        <div class="pad">
                            <div class="field_div">
                                <textarea name="body" cols="24" rows="5"></textarea>
                            </div>
                            <div class="submit_div">
                                <input type="submit" id="submit_button" value="Add comment" />
                            </div>
                        </div>
                    </form>
                </div>
            <?
            }
            ?>
        </div>
        <div class="main_column">
            <?
            if ($CollageCovers != 0) { ?>
                <div id="coverart" class="box">
                    <div class="head" id="coverhead"><strong>
                            <?= Lang::get('collages', 'cover_art') ?></strong></div>
                    <ul class="collage_images" id="collage_page0">
                        <?
                        for ($Idx = 0; $Idx < min($NumGroups, $CollageCovers); $Idx++) {
                            echo Collages::collage_cover_row($TorrentList[$GroupIDs[$Idx]]);
                        }
                        ?>
                    </ul>
                </div>
                <? if ($NumGroups > $CollageCovers) { ?>
                    <div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
                        <span id="firstpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.page(0); return false;"><strong>&lt;&lt; First</strong></a> | </span>
                        <span id="prevpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.prevPage(); return false;"><strong>&lt; Prev</strong></a> | </span>
                        <? for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
                            <span id="pagelink<?= $i ?>" class="<?= (($i > 4) ? 'hidden' : '') ?><?= (($i == 0) ? 'selected' : '') ?>"><a href="#" class="pageslink" onclick="collageShow.page(<?= $i ?>, this); return false;"><strong><?= $CollageCovers * $i + 1 ?>-<?= min($NumGroups, $CollageCovers * ($i + 1)) ?></strong></a><?= (($i != ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '') ?></span>
                        <?      } ?>
                        <span id="nextbar" class="<?= ($NumGroups / $CollageCovers > 5) ? 'hidden' : '' ?>"> | </span>
                        <span id="nextpage"><a href="#" class="pageslink" onclick="collageShow.nextPage(); return false;"><strong>Next &gt;</strong></a></span>
                        <span id="lastpage" class="<?= (ceil($NumGroups / $CollageCovers) == 2 ? 'invisible' : '') ?>"> | <a href="#" class="pageslink" onclick="collageShow.page(<?= ceil($NumGroups / $CollageCovers) - 1 ?>); return false;"><strong>Last
                                    &gt;&gt;</strong></a></span>
                    </div>
                    <?php
                    $CollagePages = array();
                    for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
                        $Groups = array_slice($GroupIDs, $i * $CollageCovers, $CollageCovers);
                        $CollagePages[] = implode(
                            '',
                            array_map(
                                function ($GroupID) use ($TorrentList) {
                                    return Collages::collage_cover_row($TorrentList[$GroupID]);
                                },
                                $Groups
                            )
                        );
                    }
                    if ($NumGroups > $CollageCovers) {
                        for ($i = $NumGroups + 1; $i <= ceil($NumGroups / $CollageCovers) * $CollageCovers; $i++) {
                            $CollagePages[count($CollagePages) - 1] .= '<li></li>';
                        }
                    }
                    ?>
                    <script type="text/javascript">
                        //<![CDATA[
                        collageShow.init(<?= json_encode($CollagePages) ?>);
                        //]]>
                    </script>
            <?
                    unset($CollagePages);
                }
            }
            ?>
            <div class="table_container border">
                <table class="torrent_table grouping cats m_table" id="discog_table">
                    <tr class="colhead_dark">
                        <td>
                            <!-- expand/collapse -->
                        </td>
                        <td>
                            <!-- Category -->
                        </td>
                        <td class="m_th_left" width="70%"><strong>
                                <?= Lang::get('global', 'torrents') ?></strong></td>
                        <td><i class="fa fa-hdd tooltip" aria-hidden="true"></i></td>
                        <td class="sign snatches"><i class="fa fa-check tooltip" aria-hidden="true" alt="Snatches"></i></td>
                        <td class="sign seeders"><i class="fa fa-upload tooltip" aria-hidden="true" alt="Seeders"></i></td>
                        <td class="sign leechers"><i class="fa fa-download tooltip" aria-hidden="true" alt="Leechers"></i></td>
                    </tr>
                    <?php
                    $Number = 0;
                    foreach ($GroupIDs as $Idx => $GroupID) {
                        $Group = $TorrentList[$GroupID];
                        extract(Torrents::array_group($Group));
                        /**
                         * @var int $GroupID
                         * @var string $GroupName
                         * @var string $GroupYear
                         * @var int $GroupCategoryID
                         * @var string $GroupRecordLabel
                         * @var bool $GroupVanityHouse
                         * @var array $GroupFlags
                         * @var array $Artists
                         * @var array $ExtendedArtists
                         * @var string $TagList
                         * @var string $WikiImage
                         */

                        $TorrentTags = new Tags($TagList);
                        $Number++;
                        $DisplayName = "$Number - ";

                        if (
                            !empty($ExtendedArtists[1])
                            || !empty($ExtendedArtists[4])
                            || !empty($ExtendedArtists[5])
                            || !empty($ExtendedArtists[6])
                        ) {
                            unset($ExtendedArtists[2]);
                            unset($ExtendedArtists[3]);
                            $DisplayName .= Artists::display_artists($ExtendedArtists);
                        } elseif (count($GroupArtists) > 0) {
                            $DisplayName .= Artists::display_artists(array('1' => $GroupArtists));
                        }

                        $DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"" . Lang::get('global', 'view_torrent_group') . "\" dir=\"ltr\">$GroupName</a>";
                        if ($GroupYear > 0) {
                            $DisplayName = "$DisplayName [$GroupYear]";
                        }
                        if ($GroupVanityHouse) {
                            $DisplayName .= ' [<abbr class="tooltip" title="' . Lang::get('global', 'this_is_vh') . '">VH</abbr>]';
                        }
                        $SnatchedGroupClass = ($GroupFlags['IsSnatched'] ? ' snatched_group' : '');
                        $UserVote = isset($UserVotes[$GroupID]) ? $UserVotes[$GroupID]['Type'] : '';

                        if (count($Torrents) > 1 || $GroupCategoryID == 1) {
                            // Grouped torrents
                            $ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
                    ?>
                            <tr class="group discog<?= $SnatchedGroupClass ?>" id="group_<?= $GroupID ?>">
                                <td class="center td_collapse">
                                    <div id="showimg_<?= $GroupID ?>" class="<?= ($ShowGroups ? 'hide' : 'show') ?>_torrents">
                                        <a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?= $GroupID ?>, this, event);" title="<?= Lang::get('global', 'collapse_this_group_title') ?>"></a>
                                    </div>
                                </td>
                                <td class="center">
                                    <div title="<?= $TorrentTags->title() ?>" class="tooltip <?= Format::css_category($GroupCategoryID) ?> <?= $TorrentTags->css_name() ?>">
                                    </div>
                                </td>
                                <td colspan="5">
                                    <strong><?= $DisplayName ?></strong>
                                    <? if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
                                        <span class="remove_bookmark float_right">
                                            <a style="float: right;" href="#" id="bookmarklink_torrent_<?= $GroupID ?>" class="remove_bookmark brackets" onclick="Unbookmark('torrent', <?= $GroupID ?>, 'Bookmark'); return false;">
                                                <?= Lang::get('global', 'remove_bookmark') ?></a>
                                        </span>
                                    <? } else { ?>
                                        <span class="add_bookmark float_right">
                                            <a style="float: right;" href="#" id="bookmarklink_torrent_<?= $GroupID ?>" class="add_bookmark brackets" onclick="Bookmark('torrent', <?= $GroupID ?>, 'Remove bookmark']); return false;">
                                                <?= Lang::get('global', 'add_bookmark') ?></a>
                                        </span>
                                    <?
                                    }
                                    Votes::vote_link($GroupID, $UserVote);
                                    ?>
                                    <div class="tags"><?= $TorrentTags->format() ?></div>
                                </td>
                            </tr>
                            <?
                            $LastRemasterYear = '-';
                            $LastRemasterTitle = '';
                            $LastRemasterRecordLabel = '';
                            $LastRemasterCatalogueNumber = '';
                            $LastMedia = '';

                            $EditionID = 0;
                            unset($FirstUnknown);

                            foreach ($Torrents as $TorrentID => $Torrent) {

                                if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
                                    $FirstUnknown = !isset($FirstUnknown);
                                }
                                $SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';

                                if (
                                    $Torrent['RemasterTitle'] != $LastRemasterTitle
                                    || $Torrent['RemasterYear'] != $LastRemasterYear
                                    || $Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel
                                    || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber
                                    || $FirstUnknown
                                    || $Torrent['Media'] != $LastMedia
                                ) {
                                    $EditionID++;
                            ?>
                                    <tr class="group_torrent groupid_<?= $GroupID ?> edition<?= $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '') ?>">
                                        <td colspan="7" class="edition_info"><strong><a href="#" onclick="torrentTable.toggleEdition(event, <?= $GroupID ?>, <?= $EditionID ?>)" class="tooltip" title="<?= Lang::get('global', 'collapse_this_edition_title') ?>">&minus;</a>
                                                <?= Torrents::edition_string($Torrent, $Group) ?>
                                            </strong></td>
                                    </tr>
                                <?
                                }
                                $LastRemasterTitle = $Torrent['RemasterTitle'];
                                $LastRemasterYear = $Torrent['RemasterYear'];
                                $LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
                                $LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
                                $LastMedia = $Torrent['Media'];
                                ?>
                                <tr class="group_torrent torrent_row groupid_<?= $GroupID ?> edition_<?= $EditionID ?><?= $SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '') ?>">
                                    <td class="td_info" colspan="3">
                                        <span class="brackets">
                                            <a href="torrents.php?action=download&amp;id=<?= $TorrentID ?>&amp;authkey=<?= $LoggedUser['AuthKey'] ?>&amp;torrent_pass=<?= $LoggedUser['torrent_pass'] ?>" class="tooltip" title="<?= Lang::get('global', 'download') ?>">DL</a>
                                            <? if (Torrents::can_use_token($Torrent)) { ?>
                                                | <a href="torrents.php?action=download&amp;id=<?= $TorrentID ?>&amp;authkey=<?= $LoggedUser['AuthKey'] ?>&amp;torrent_pass=<?= $LoggedUser['torrent_pass'] ?>&amp;usetoken=1" class="tooltip" title="<?= Lang::get('global', 'use_fl_tokens') ?>" onclick="return confirm('<?= FL_confirmation_msg($Torrent['Seeders'], $Torrent['Size']) ?>');">FL</a>
                                            <? } ?>
                                            | <a href="reportsv2.php?action=report&amp;id=<?= $TorrentID ?>" class="tooltip" title="<?= Lang::get('collages', 'report') ?>">RP</a>
                                        </span>
                                        &nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?= $GroupID ?>&amp;torrentid=<?= $TorrentID ?>"><?= Torrents::torrent_info($Torrent) ?></a>
                                    </td>
                                    <td class="td_size number_column nobr"><?= Format::get_size($Torrent['Size']) ?></td>
                                    <td class="td_snatched m_td_right number_column"><?= number_format($Torrent['Snatched']) ?></td>
                                    <td class="td_seeders m_td_right number_column<?= (($Torrent['Seeders'] == 0) ? ' r00' : '') ?>">
                                        <?= number_format($Torrent['Seeders']) ?></td>
                                    <td class="td_leechers m_td_right number_column"><?= number_format($Torrent['Leechers']) ?></td>
                                </tr>
                            <?
                            }
                        } else {
                            // Viewing a type that does not require grouping

                            list($TorrentID, $Torrent) = each($Torrents);

                            $DisplayName = "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"" . Lang::get('global', 'view_torrent_group') . "\" dir=\"ltr\">$GroupName</a>";

                            if ($Torrent['IsSnatched']) {
                                $DisplayName .= ' ' . Format::torrent_label('Snatched!');
                            }
                            if ($Torrent['FreeTorrent'] == '1') {
                                $DisplayName .= ' ' . Format::torrent_label('Freeleech!');
                            } elseif ($Torrent['FreeTorrent'] == '2') {
                                $DisplayName .= ' ' . Format::torrent_label('Neutral Leech!');
                            } elseif ($Torrent['PersonalFL']) {
                                $DisplayName .= ' ' . Format::torrent_label('Personal Freeleech!');
                            }
                            $SnatchedTorrentClass = ($Torrent['IsSnatched'] ? ' snatched_torrent' : '');
                            ?>
                            <tr class="torrent torrent_row<?= $SnatchedTorrentClass . $SnatchedGroupClass ?>" id="group_<?= $GroupID ?>">
                                <td></td>
                                <td class="td_collage_category center">
                                    <div title="<?= $TorrentTags->title() ?>" class="tooltip <?= Format::css_category($GroupCategoryID) ?> <?= $TorrentTags->css_name() ?>">
                                    </div>
                                </td>
                                <td class="td_info">
                                    <span class="brackets">
                                        <a href="torrents.php?action=download&amp;id=<?= $TorrentID ?>&amp;authkey=<?= $LoggedUser['AuthKey'] ?>&amp;torrent_pass=<?= $LoggedUser['torrent_pass'] ?>" class="tooltip" title="<?= Lang::get('global', 'download') ?>">DL</a>
                                        <? if (Torrents::can_use_token($Torrent)) { ?>
                                            | <a href="torrents.php?action=download&amp;id=<?= $TorrentID ?>&amp;authkey=<?= $LoggedUser['AuthKey'] ?>&amp;torrent_pass=<?= $LoggedUser['torrent_pass'] ?>&amp;usetoken=1" class="tooltip" title="<?= Lang::get('global', 'use_fl_tokens') ?>" onclick="return confirm('<?= FL_confirmation_msg($Torrent['Seeders'], $Torrent['Size']) ?>');">FL</a>
                                        <? } ?>
                                        | <a href="reportsv2.php?action=report&amp;id=<?= $TorrentID ?>" class="tooltip" title="<?= Lang::get('collages', 'report') ?>">RP</a>
                                    </span>
                                    <strong><?= $DisplayName ?></strong>
                                    <? Votes::vote_link($GroupID, $UserVote); ?>
                                    <div class="tags"><?= $TorrentTags->format() ?></div>
                                </td>
                                <td class="td_size number_column nobr"><?= Format::get_size($Torrent['Size']) ?></td>
                                <td class="td_snatched m_td_right number_column"><?= number_format($Torrent['Snatched']) ?></td>
                                <td class="td_seeders m_td_right number_column<?= (($Torrent['Seeders'] == 0) ? ' r00' : '') ?>">
                                    <?= number_format($Torrent['Seeders']) ?></td>
                                <td class="td_leechers m_td_right number_column"><?= number_format($Torrent['Leechers']) ?></td>
                            </tr>
                    <?
                        }
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?
View::show_footer();
