<?
if (!check_perms('admin_manage_applicants')) {
    error(403);
}
View::show_header(Lang::get('apply', 'applicant_administration'), 'bbcode');
$EDIT_ID = 0;
$Saved   = '';
if (isset($_POST['auth'])) {
    authorize();
    $edit = array_filter($_POST, function ($x) {
        return preg_match('/^edit-\d+$/', $x);
    }, ARRAY_FILTER_USE_KEY);
    if (is_array($edit) && count($edit) == 1) {
        $EDIT_ID = trim(array_keys($edit)[0], 'edit-');
        $AppRole = ApplicantRole::factory($EDIT_ID);
    } elseif (isset($_POST['edit']) && is_numeric($_POST['edit'])) {
        $EDIT_ID = intval($_POST['edit']);
        $AppRole = ApplicantRole::factory($EDIT_ID);
        if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            if ($user_id == $LoggedUser['ID']) {
                $AppRole->update(
                    $_POST['title'],
                    $_POST['description'],
                    (isset($_POST['status']) && is_numeric($_POST['status']) && $_POST['status'] == 1)
                );
            }
            $EDIT_ID = 0; /* return to list */
            $Saved = Lang::get('apply', 'updated');
        }
    } else {
        $AppRole = new ApplicantRole(
            $_POST['title'],
            $_POST['description'],
            (isset($_POST['status']) && is_numeric($_POST['status']) && $_POST['status'] == 1),
            $LoggedUser['ID']
        );
        $Saved = Lang::get('apply', 'saved');
    }
}
?>

<div class="thin">
    <div class="header">
        <h2><?= Lang::get('apply', 'manage_roles_at_before') ?><?= SITE_NAME ?><?= Lang::get('apply', 'manage_roles_at_after') ?></h2>
        <div class="linkbox">
            <a href="/apply.php" class="brackets"><?= Lang::get('apply', 'apply') ?></a>
            <a href="/apply.php?action=view" class="brackets"><?= Lang::get('apply', 'current_applications') ?></a>
            <a href="/apply.php?action=view&status=resolved" class="brackets"><?= Lang::get('apply', 'resolved_applications') ?></a>
        </div>
    </div>



    <form method="post" action="/apply.php?action=admin">

        <div class="box">
            <div class="head"><?= Lang::get('apply', 'current_roles') ?></div>
            <div class="pad">
                <? if ($Saved) { ?>
                    <p><?= Lang::get('apply', 'the_role_was_1') ?><?= $AppRole->title() ?><?= Lang::get('apply', 'the_role_was_2') ?><?= $Saved ?><?= Lang::get('apply', 'the_role_was_3') ?></p>
                <? } ?>

                <? if (!$EDIT_ID) {
                    $Roles = ApplicantRole::get_list(true);
                    if (count($Roles)) {
                ?>
                        <table>
                            <? foreach ($Roles as $title => $info) { ?>
                                <tr>
                                    <td>
                                        <div class="head">
                                            <div style="float: right;">
                                                <input style="margin-bottom: 10px;" type="submit" name="edit-<?= $info['id'] ?>" value="Edit" />
                                            </div>
                                            <?= $title ?> (<?= $info['published'] ? Lang::get('apply', 'published') : Lang::get('apply', 'archived') ?>)
                                            <br /><?= Lang::get('apply', 'role_created_by_1') ?><?= time_diff($info['created'], 2) ?><?= Lang::get('apply', 'role_created_by_2') ?>
                                            <?=
                                            Users::format_username($info['user_id']) . Lang::get('apply', 'role_created_by_3')
                                                . ($info['modified'] == $info['created'] ? '' : Lang::get('apply', 'role_created_by_4') . time_diff($info['modified'], 2))
                                            ?><?= Lang::get('apply', 'role_created_by_5') ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="pad"><?= Text::full_format($info['description']) ?></div>
                                    </td>
                                </tr>
                            <?      } /* foreach */ ?>
                        </table>
                    <?  } else { ?>
                        <p><?= Lang::get('apply', 'no_current_roles') ?></p>
                <?  }
                } /* !$EDIT_ID */ ?>
            </div>
        </div>

        <div class="box">
            <div class="head"><?= $EDIT_ID ? Lang::get('apply', 'edit_role') : Lang::get('apply', 'new_role') ?><?= Lang::get('apply', 'space_role') ?></div>
            <div class="pad">

                <?
                if (isset($App)) {
                    $checked_published = $AppRole->is_published() ? ' checked' : '';
                    $checked_archived  = $AppRole->is_published() ? '' : ' checked';
                } else {
                    $checked_published = '';
                    $checked_archived  = ' checked';
                }
                ?>
                <table>
                    <tr>
                        <td class="label"><?= Lang::get('apply', 'role_title') ?></td>
                        <td><input type="text" width="100" name="title" value="<?= $EDIT_ID ? $AppRole->title() : '' ?>" /></td>
                    </tr>
                    <tr>
                        <td class="label"><?= Lang::get('apply', 'visibility') ?></td>
                        <td>
                            <input type="radio" name="status" value="1" id="status-pub" <?= $checked_published ?> /><label for="status-pub"><?= Lang::get('apply', 'published') ?></label><br />
                            <input type="radio" name="status" value="0" id="status-arch" <?= $checked_archived ?> /><label for="status-arch"><?= Lang::get('apply', 'archived') ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td class="label"><?= Lang::get('apply', 'description') ?></td>
                        <td>
                            <?
                            $text = new TEXTAREA_PREVIEW('description', 'description', $EDIT_ID ? $AppRole->description() : '', 60, 8, true, false);
                            $id = $text->getID();
                            echo $text->preview();
                            ?>
                            <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                            <? if ($EDIT_ID) { ?>
                                <input type="hidden" name="edit" value="<?= $EDIT_ID ?>" />
                            <?  } ?>
                            <input type="hidden" name="user_id" value="<?= $LoggedUser['ID'] ?>" />
                            <input type="button" value="Preview" class="hidden button_preview_<?= $text->getId() ?>" />
                            <input type="submit" id="submit" value="Save Role" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </form>

</div>
<?
View::show_footer();
